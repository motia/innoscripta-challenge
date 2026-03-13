<?php

namespace App\Validation\Fields;

class NameField extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            'name',
            ['required', 'string', 'max:255']
        );
    }
}
