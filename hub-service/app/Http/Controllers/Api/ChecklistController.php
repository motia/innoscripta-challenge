<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChecklistResource;
use App\Services\ChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function __construct(
        private readonly ChecklistService $checklistService
    ) {}

    /**
     * GET /api/checklists
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string', 'in:USA,Germany'],
        ]);

        $country = $request->input('country');
        $checklist = $this->checklistService->getChecklist($country);

        return response()->json(new ChecklistResource($checklist));
    }
}
