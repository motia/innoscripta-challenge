<?php

namespace App\Validation\Fields;

class LastNameField extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            'last_name',
            ['required', 'string', 'max:255']
        );
    }
}
