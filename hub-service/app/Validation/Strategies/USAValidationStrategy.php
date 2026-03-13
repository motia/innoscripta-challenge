<?php

namespace App\Validation\Strategies;

use App\Validation\Fields\AddressField;
use App\Validation\Fields\CountryField;
use App\Validation\Fields\LastNameField;
use App\Validation\Fields\NameField;
use App\Validation\Fields\SalaryField;
use App\Validation\Fields\SsnField;

class USAValidationStrategy extends AbstractCountryValidationStrategy
{
    public function getCountryCode(): string
    {
        return 'USA';
    }

    protected function defineFields(): array
    {
        return [
            new NameField(),
            new LastNameField(),
            new SalaryField(),
            new SsnField(),
            new AddressField(),
            new CountryField('USA'),
        ];
    }
}
