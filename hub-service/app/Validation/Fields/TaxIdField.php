<?php

namespace App\Validation\Fields;

class TaxIdField extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            'tax_id',
            ['required', 'string', 'regex:/^DE\d{9}$/'],
            ['tax_id.regex' => 'Tax ID must be in format DE + 9 digits (e.g., DE123456789)'],
            'required|regex:/^DE\d{9}$/',
            'Tax ID',
            'Tax ID is required in format DE + 9 digits',
            true
        );
    }
}
