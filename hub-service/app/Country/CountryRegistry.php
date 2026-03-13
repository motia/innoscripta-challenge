<?php

namespace App\Country;

use App\Country\Schema\CountrySchema;
use App\Country\Schema\GermanySchema;
use App\Country\Schema\USASchema;
use App\Validation\Strategies\CountryValidationStrategy;
use App\Validation\Strategies\GermanyValidationStrategy;
use App\Validation\Strategies\USAValidationStrategy;
use InvalidArgumentException;

class CountryRegistry
{
    /** @var array<string, array{validation: CountryValidationStrategy, schema: CountrySchema}> */
    private array $countries = [];

    private static ?CountryRegistry $instance = null;

    public function __construct()
    {
        $this->registerDefaults();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function registerDefaults(): void
    {
        $this->registerCountry('USA', new USAValidationStrategy(), new USASchema());
        $this->registerCountry('Germany', new GermanyValidationStrategy(), new GermanySchema());
    }

    public function registerCountry(
        string $code,
        CountryValidationStrategy $validation,
        CountrySchema $schema
    ): self {
        $this->countries[strtoupper($code)] = [
            'code' => $code,
            'validation' => $validation,
            'schema' => $schema,
        ];

        return $this;
    }

    /**
     * Get validation strategy for a country.
     *
     * @throws InvalidArgumentException
     */
    public function getValidation(string $country): CountryValidationStrategy
    {
        $key = strtoupper($country);

        if (!isset($this->countries[$key])) {
            throw new InvalidArgumentException("Unsupported country: {$country}");
        }

        return $this->countries[$key]['validation'];
    }

    /**
     * Get schema for a country.
     *
     * @throws InvalidArgumentException
     */
    public function getSchema(string $country): CountrySchema
    {
        $key = strtoupper($country);

        if (!isset($this->countries[$key])) {
            throw new InvalidArgumentException("Unsupported country: {$country}");
        }

        return $this->countries[$key]['schema'];
    }

    /**
     * Get all supported country codes.
     *
     * @return string[]
     */
    public function supportedCountries(): array
    {
        return array_column($this->countries, 'code');
    }

    /**
     * Get comma-separated list of supported countries for validation rules.
     */
    public function supportedCountriesString(): string
    {
        return implode(',', $this->supportedCountries());
    }

    /**
     * Check if a country is supported.
     */
    public function isSupported(string $country): bool
    {
        return isset($this->countries[strtoupper($country)]);
    }
}
