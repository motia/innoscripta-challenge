<?php

namespace App\Validation\Strategies;

interface CountryValidationStrategy
{
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
     * Returns an array of field => rule pairs for the checklist system.
     */
    public function checklistRules(): array;
}
