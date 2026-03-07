<?php

namespace App\Validation\Strategies;

interface CountryValidationStrategy
{
    /**
     * Get the country code.
     */
    public function getCountryCode(): string;

    /**
     * Get validation rules for employee input.
     */
    public function rules(): array;

    /**
     * Get custom validation messages.
     */
    public function messages(): array;

    /**
     * Get checklist rules for completeness validation.
     */
    public function checklistRules(): array;

    /**
     * Get country-specific field names for this country.
     */
    public function customFields(): array;

    /**
     * Get columns to display in employee list.
     */
    public function listColumns(): array;

    /**
     * Extract country-specific fields from employee data for API response.
     */
    public function extractCustomFields(array $employeeData): array;
}
