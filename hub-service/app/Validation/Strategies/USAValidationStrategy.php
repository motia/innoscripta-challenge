<?php

namespace App\Validation\Strategies;

class USAValidationStrategy implements CountryValidationStrategy
{
    public function getCountryCode(): string
    {
        return 'USA';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
            'ssn' => ['required', 'string', 'regex:/^\d{3}-\d{2}-\d{4}$/'],
            'address' => ['required', 'string', 'min:1'],
            'country' => ['required', 'string', 'in:USA'],
        ];
    }

    public function messages(): array
    {
        return [
            'ssn.regex' => 'SSN must be in format XXX-XX-XXXX',
            'salary.min' => 'Salary must be greater than 0',
            'address.min' => 'Address is required',
        ];
    }

    public function checklistRules(): array
    {
        return [
            'ssn' => [
                'field' => 'ssn',
                'label' => 'Social Security Number',
                'rule' => 'required|regex:/^\d{3}-\d{2}-\d{4}$/',
                'message' => 'SSN is required in format XXX-XX-XXXX',
            ],
            'salary' => [
                'field' => 'salary',
                'label' => 'Salary',
                'rule' => 'required|numeric|gt:0',
                'message' => 'Salary must be greater than 0',
            ],
            'address' => [
                'field' => 'address',
                'label' => 'Address',
                'rule' => 'required|string|min:1',
                'message' => 'Address is required',
            ],
        ];
    }

    public function customFields(): array
    {
        return ['ssn', 'address'];
    }

    public function listColumns(): array
    {
        return ['id', 'name', 'last_name', 'salary', 'country', 'ssn', 'address'];
    }

    public function extractCustomFields(array $employeeData): array
    {
        return [
            'ssn' => $employeeData['ssn'] ?? null,
            'address' => $employeeData['address'] ?? null,
        ];
    }
}
