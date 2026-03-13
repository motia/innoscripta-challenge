<?php

namespace App\Validation\Fields;

class SalaryField extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            'salary',
            ['required', 'numeric', 'min:0'],
            ['salary.min' => 'Salary must be greater than 0'],
            'required|numeric|gt:0',
            'Salary',
            'Salary must be greater than 0'
        );
    }
}
