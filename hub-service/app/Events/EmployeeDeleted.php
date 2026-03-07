<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $country,
        public readonly int $employeeId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("country.{$this->country}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'employee.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'country' => $this->country,
        ];
    }
}
