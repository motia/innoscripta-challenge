<?php

namespace App\Http\Requests;

use App\Validation\CountryValidationFactory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $country = $this->input('country', $employee?->country ?? 'USA');

        try {
            $strategy = CountryValidationFactory::make($country);
            $rules = $strategy->rules();

            foreach ($rules as $field => $fieldRules) {
                $rules[$field] = array_map(function ($rule) {
                    return $rule === 'required' ? 'sometimes' : $rule;
                }, $fieldRules);
            }

            return $rules;
        } catch (\InvalidArgumentException $e) {
            return [
                'name' => ['sometimes', 'string', 'max:255'],
                'last_name' => ['sometimes', 'string', 'max:255'],
                'salary' => ['sometimes', 'numeric', 'min:0'],
                'country' => ['sometimes', 'string', 'in:USA,Germany'],
            ];
        }
    }

    public function messages(): array
    {
        $employee = $this->route('employee');
        $country = $this->input('country', $employee?->country ?? 'USA');

        try {
            $strategy = CountryValidationFactory::make($country);
            return $strategy->messages();
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }
}
