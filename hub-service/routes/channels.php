<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('country.{country}', function () {
    return true;
});

Broadcast::channel('country.{country}.checklists', function () {
    return true;
});

Broadcast::channel('employee.{country}.{id}', function () {
    return true;
});
