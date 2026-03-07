<?php

namespace App\Validation;

use App\Validation\Strategies\CountryValidationStrategy;
use App\Validation\Strategies\GermanyValidationStrategy;
use App\Validation\Strategies\USAValidationStrategy;
use InvalidArgumentException;

class CountryValidationFactory
{
    /**
     * Create a validation strategy for the given country.
     *
     * @throws InvalidArgumentException
     */
    public static function make(string $country): CountryValidationStrategy
    {
        return match (strtoupper($country)) {
            'USA' => new USAValidationStrategy(),
            'GERMANY' => new GermanyValidationStrategy(),
            default => throw new InvalidArgumentException("Unsupported country: {$country}"),
        };
    }

    /**
     * Get all supported countries.
     */
    public static function supportedCountries(): array
    {
        return ['USA', 'Germany'];
    }
}
