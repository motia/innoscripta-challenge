<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StepsController extends Controller
{
    private const CACHE_TTL_HOURS = 24;

    /**
     * GET /api/steps
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string', 'in:USA,Germany'],
        ]);

        $country = $request->input('country');
        $cacheKey = "steps:{$country}";

        $steps = Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($country) {
            return $this->getStepsForCountry($country);
        });

        return response()->json([
            'data' => $steps,
            'meta' => [
                'country' => $country,
            ],
        ]);
    }

    private function getStepsForCountry(string $country): array
    {
        $baseSteps = [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'home',
                'path' => '/dashboard',
                'order' => 1,
            ],
            [
                'id' => 'employees',
                'label' => 'Employees',
                'icon' => 'users',
                'path' => '/employees',
                'order' => 2,
            ],
        ];

        if (strtoupper($country) === 'GERMANY') {
            $baseSteps[] = [
                'id' => 'documentation',
                'label' => 'Documentation',
                'icon' => 'file-text',
                'path' => '/documentation',
                'order' => 3,
            ];
        }

        return $baseSteps;
    }
}
