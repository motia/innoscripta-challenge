<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $country,
        public readonly array $employee,
        public readonly array $changedFields = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("country.{$this->country}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'employee.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'employee' => $this->employee,
            'changed_fields' => $this->changedFields,
        ];
    }
}
