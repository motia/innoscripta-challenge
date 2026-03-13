<?php

namespace App\Validation\Fields;

class AddressField extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            'address',
            ['required', 'string', 'min:1'],
            ['address.min' => 'Address is required'],
            'required|string|min:1',
            'Address',
            'Address is required',
            true
        );
    }
}
