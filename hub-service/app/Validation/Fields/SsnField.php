<?php

namespace App\Validation\Fields;

class SsnField extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            'ssn',
            ['required', 'string', 'regex:/^\d{3}-\d{2}-\d{4}$/'],
            ['ssn.regex' => 'SSN must be in format XXX-XX-XXXX'],
            'required|regex:/^\d{3}-\d{2}-\d{4}$/',
            'Social Security Number',
            'SSN is required in format XXX-XX-XXXX',
            true
        );
    }
}
