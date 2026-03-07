<?php

namespace App\Validation\Strategies;

class GermanyValidationStrategy implements CountryValidationStrategy
{
    public function getCountryCode(): string
    {
        return 'Germany';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
            'goal' => ['required', 'string', 'min:1'],
            'tax_id' => ['required', 'string', 'regex:/^DE\d{9}$/'],
            'country' => ['required', 'string', 'in:Germany'],
        ];
    }

    public function messages(): array
    {
        return [
            'tax_id.regex' => 'Tax ID must be in format DE + 9 digits (e.g., DE123456789)',
            'salary.min' => 'Salary must be greater than 0',
            'goal.min' => 'Goal is required',
        ];
    }

    public function checklistRules(): array
    {
        return [
            'salary' => [
                'field' => 'salary',
                'label' => 'Salary',
                'rule' => 'required|numeric|gt:0',
                'message' => 'Salary must be greater than 0',
            ],
            'goal' => [
                'field' => 'goal',
                'label' => 'Goal',
                'rule' => 'required|string|min:1',
                'message' => 'Goal is required',
            ],
            'tax_id' => [
                'field' => 'tax_id',
                'label' => 'Tax ID',
                'rule' => 'required|regex:/^DE\d{9}$/',
                'message' => 'Tax ID is required in format DE + 9 digits',
            ],
        ];
    }

    public function customFields(): array
    {
        return ['goal', 'tax_id'];
    }

    public function listColumns(): array
    {
        return ['id', 'name', 'last_name', 'salary', 'country', 'goal', 'tax_id'];
    }

    public function extractCustomFields(array $employeeData): array
    {
        return [
            'goal' => $employeeData['goal'] ?? null,
            'tax_id' => $employeeData['tax_id'] ?? null,
        ];
    }
}
