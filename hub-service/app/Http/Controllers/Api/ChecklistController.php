<?php

namespace App\Http\Controllers\Api;

use App\Country\CountryRegistry;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChecklistResource;
use App\Services\ChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function __construct(
        private readonly ChecklistService $checklistService,
        private readonly CountryRegistry $registry
    ) {}

    /**
     * GET /api/checklists
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string', 'in:' . $this->registry->supportedCountriesString()],
        ]);

        $country = $request->input('country');
        $checklist = $this->checklistService->getChecklist($country);

        return response()->json(new ChecklistResource($checklist));
    }
}
