<?php

namespace App\Validation\Fields;

interface Field
{
    /**
     * Name of the field (used as array key).
     */
    public function name(): string;

    /**
     * Validation rules used by Form Requests.
     */
    public function validationRules(): array;

    /**
     * Custom validation messages keyed by rule name (optional).
     */
    public function validationMessages(): array;

    /**
     * Should this field be part of the completeness checklist?
     */
    public function contributesToChecklist(): bool;

    /**
     * Human readable label for checklist output (only if contributesToChecklist is true).
     */
    public function checklistLabel(): ?string;

    /**
     * Checklist validation rule string (e.g. required|string).
     */
    public function checklistRule(): ?string;

    /**
     * Checklist failure message (optional).
     */
    public function checklistMessage(): ?string;

    /**
     * Whether this field is country-specific/custom (used in customFields()).
     */
    public function isCustomField(): bool;
}
