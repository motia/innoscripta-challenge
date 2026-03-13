<?php

namespace App\Validation\Strategies;

use App\Validation\Fields\CountryField;
use App\Validation\Fields\GoalField;
use App\Validation\Fields\LastNameField;
use App\Validation\Fields\NameField;
use App\Validation\Fields\SalaryField;
use App\Validation\Fields\TaxIdField;

class GermanyValidationStrategy extends AbstractCountryValidationStrategy
{
    public function getCountryCode(): string
    {
        return 'Germany';
    }

    protected function defineFields(): array
    {
        return [
            new NameField(),
            new LastNameField(),
            new SalaryField(),
            new GoalField(),
            new TaxIdField(),
            new CountryField('Germany'),
        ];
    }
}
