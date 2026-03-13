<?php

namespace App\Country\Schema;

interface CountrySchema
{
    /**
     * Get the country code this schema applies to.
     */
    public function getCountryCode(): string;

    /**
     * Resolve schema configuration for a given step identifier.
     */
    public function getStepSchema(string $stepId): array;

    /**
     * Get navigation steps configuration.
     */
    public function getSteps(): array;
}
