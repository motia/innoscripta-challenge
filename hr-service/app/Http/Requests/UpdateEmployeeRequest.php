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
        $country = $this->input('country', $employee?->country);

        if (!$country) {
            return $this->baseRulesWithCountryRequired();
        }

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
            return $this->baseRulesWithCountryRequired();
        }
    }

    public function messages(): array
    {
        $employee = $this->route('employee');
        $country = $this->input('country', $employee?->country);

        if (!$country) {
            return ['country.required' => 'Country is required.'];
        }

        try {
            $strategy = CountryValidationFactory::make($country);
            return $strategy->messages();
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    private function baseRulesWithCountryRequired(): array
    {
        $supported = implode(',', CountryValidationFactory::supportedCountries());

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'salary' => ['sometimes', 'numeric', 'min:0'],
            'country' => ['required', 'string', "in:{$supported}"],
        ];
    }
}
