<?php

namespace App\Http\Resources;

use App\Validation\CountryValidationFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'salary' => $this->salary,
            'country' => $this->country,
        ];

        try {
            $strategy = CountryValidationFactory::make($this->country);
            $customFields = $strategy->extractCustomFields($this->resource->toArray());
            $data = array_merge($data, $customFields);
        } catch (\InvalidArgumentException $e) {
        }

        return $data;
    }
}
