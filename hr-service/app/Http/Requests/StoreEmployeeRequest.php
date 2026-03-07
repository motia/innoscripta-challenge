<?php

namespace App\Http\Requests;

use App\Validation\CountryValidationFactory;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $country = $this->input('country');

        if (!$country) {
            return $this->baseRulesWithCountryRequired();
        }

        try {
            $strategy = CountryValidationFactory::make($country);
            return $strategy->rules();
        } catch (\InvalidArgumentException $e) {
            return $this->baseRulesWithCountryRequired();
        }
    }

    public function messages(): array
    {
        $country = $this->input('country');

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
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
            'country' => ['required', 'string', "in:{$supported}"],
        ];
    }
}
