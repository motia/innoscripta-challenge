<?php

namespace App\Http\Resources;

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

        if ($this->country === 'USA') {
            $data['ssn'] = $this->ssn;
            $data['address'] = $this->address;
        }

        if ($this->country === 'Germany') {
            $data['goal'] = $this->goal;
            $data['tax_id'] = $this->tax_id;
        }

        return $data;
    }
}
