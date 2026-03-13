<?php

namespace App\Validation\Fields;

class CountryField extends AbstractField
{
    public function __construct(string $countryCode)
    {
        parent::__construct(
            'country',
            ['required', 'string', 'in:' . $countryCode]
        );
    }
}
