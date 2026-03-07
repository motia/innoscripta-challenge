<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'country' => $this->resource['country'] ?? null,
            'summary' => $this->resource['summary'] ?? [],
            'employees' => $this->resource['employees'] ?? [],
        ];
    }
}
