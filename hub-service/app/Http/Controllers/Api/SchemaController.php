<?php

namespace App\Http\Controllers\Api;

use App\Country\CountryRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SchemaController extends Controller
{
    private const CACHE_TTL_HOURS = 24;

    public function __construct(
        private readonly CountryRegistry $registry
    ) {}

    /**
     * GET /api/schema/{step_id}
     */
    public function show(Request $request, string $stepId): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string', 'in:' . $this->registry->supportedCountriesString()],
        ]);

        $country = $request->input('country');
        $cacheKey = "schema:{$stepId}:{$country}";

        $schema = Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($stepId, $country) {
            return $this->registry->getSchema($country)->getStepSchema($stepId);
        });

        if (empty($schema)) {
            return response()->json([
                'error' => 'Schema not found',
                'message' => "No schema configuration found for step '{$stepId}' in country '{$country}'",
            ], 404);
        }

        return response()->json([
            'data' => $schema,
            'meta' => [
                'step_id' => $stepId,
                'country' => $country,
            ],
        ]);
    }

}
