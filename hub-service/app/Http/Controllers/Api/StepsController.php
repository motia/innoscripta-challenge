<?php

namespace App\Http\Controllers\Api;

use App\Country\CountryRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StepsController extends Controller
{
    private const CACHE_TTL_HOURS = 24;

    public function __construct(
        private readonly CountryRegistry $registry
    ) {}

    /**
     * GET /api/steps
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string', 'in:' . $this->registry->supportedCountriesString()],
        ]);

        $country = $request->input('country');
        $cacheKey = "steps:{$country}";

        $steps = Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($country) {
            return $this->registry->getSchema($country)->getSteps();
        });

        return response()->json([
            'data' => $steps,
            'meta' => [
                'country' => $country,
            ],
        ]);
    }
}
