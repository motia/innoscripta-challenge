<?php

return [
    'employee_consumer' => [
        'exchange' => env('EMPLOYEE_EVENTS_EXCHANGE', 'employee_events'),
        'queue' => env('EMPLOYEE_EVENTS_QUEUE', 'hub_service_events'),
        'routing_key' => env('EMPLOYEE_EVENTS_ROUTING', 'employee.#'),
        'connection' => [
            'host' => env('RABBITMQ_HOST', 'rabbitmq'),
            'port' => env('RABBITMQ_PORT', 5672),
            'user' => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost' => env('RABBITMQ_VHOST', '/'),
        ],
    ],
];
