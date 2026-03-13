<?php

namespace App\Validation\Fields;

class GoalField extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            'goal',
            ['required', 'string', 'min:1'],
            ['goal.min' => 'Goal is required'],
            'required|string|min:1',
            'Goal',
            'Goal is required',
            true
        );
    }
}
