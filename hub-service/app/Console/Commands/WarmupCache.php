<?php

namespace App\Console\Commands;

use App\Services\ChecklistService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WarmupCache extends Command
{
    protected $signature = 'cache:warmup 
                            {--country=* : Specific countries to warm up (default: all supported)}
                            {--force : Force refresh even if cache exists}';

    protected $description = 'Warm up the HubService cache by fetching employees from HR Service';

    private const EMPLOYEE_CACHE_TTL_HOURS = 1;

    public function __construct(
        private readonly ChecklistService $checklistService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $hrServiceUrl = config('services.hr_service.url', 'http://hr-service:80');
        $countries = $this->option('country') ?: ['USA', 'Germany'];
        $force = $this->option('force');

        $this->info('Starting cache warmup...');
        $this->info("HR Service URL: {$hrServiceUrl}");

        $totalEmployees = 0;
        $errors = [];

        foreach ($countries as $country) {
            $this->line('');
            $this->info("Processing country: {$country}");

            $cacheKey = "employees:{$country}:list";

            if (!$force && Cache::has($cacheKey)) {
                $existing = Cache::get($cacheKey, []);
                $this->warn("  Cache already exists with " . count($existing) . " employees. Use --force to refresh.");
                continue;
            }

            try {
                $employees = $this->fetchEmployeesFromHrService($hrServiceUrl, $country);

                if (empty($employees)) {
                    $this->warn("  No employees found for {$country}");
                    continue;
                }

                $this->cacheEmployees($country, $employees);

                $this->info("  Cached " . count($employees) . " employees");
                $totalEmployees += count($employees);

                $checklist = $this->checklistService->getChecklist($country);
                $this->info("  Checklist warmed: {$checklist['summary']['complete']}/{$checklist['summary']['total_employees']} complete");

            } catch (\Throwable $e) {
                $this->error("  Failed: {$e->getMessage()}");
                $errors[] = "{$country}: {$e->getMessage()}";
                Log::error('Cache warmup failed for country', [
                    'country' => $country,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->line('');
        $this->info("Warmup complete. Total employees cached: {$totalEmployees}");

        if (!empty($errors)) {
            $this->error('Errors occurred:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function fetchEmployeesFromHrService(string $baseUrl, string $country): array
    {
        $allEmployees = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = Http::timeout(30)->get("{$baseUrl}/api/employees", [
                'country' => $country,
                'page' => $page,
                'per_page' => $perPage,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("HR Service returned {$response->status()}");
            }

            $data = $response->json();
            $employees = $data['data'] ?? [];
            $allEmployees = array_merge($allEmployees, $employees);

            $hasMore = isset($data['meta']['current_page'], $data['meta']['last_page'])
                && $data['meta']['current_page'] < $data['meta']['last_page'];

            $page++;

        } while ($hasMore && $page <= 100);

        return $allEmployees;
    }

    private function cacheEmployees(string $country, array $employees): void
    {
        $listKey = "employees:{$country}:list";

        Cache::put($listKey, $employees, now()->addHours(self::EMPLOYEE_CACHE_TTL_HOURS));

        foreach ($employees as $employee) {
            $employeeId = $employee['id'] ?? null;
            if ($employeeId) {
                Cache::put(
                    "employees:{$country}:{$employeeId}",
                    $employee,
                    now()->addHours(self::EMPLOYEE_CACHE_TTL_HOURS)
                );
            }
        }
    }
}
