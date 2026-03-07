<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class EmployeeEventPublisher
{
    private ?AMQPStreamConnection $connection = null;

    public function publishCreated(Employee $employee, Carbon $timestamp): void
    {
        $this->publish('EmployeeCreated', $employee->country, [
            'employee_id' => $employee->id,
            'employee' => $employee->toArray(),
        ], $timestamp);
    }

    public function publishUpdated(Employee $employee, array $changedFields = [], Carbon $timestamp): void
    {
        $this->publish('EmployeeUpdated', $employee->country, [
            'employee_id' => $employee->id,
            'changed_fields' => $changedFields,
            'employee' => $employee->toArray(),
        ], $timestamp);
    }

    public function publishDeleted(array $employeeData, Carbon $timestamp): void
    {
        $this->publish('EmployeeDeleted', $employeeData['country'], [
            'employee_id' => $employeeData['id'],
            'employee' => $employeeData,
        ], $timestamp);
    }

    private function publish(string $eventType, string $country, array $data, Carbon $timestamp): void
    {
        try {
            $payload = [
                'event_type' => $eventType,
                'event_id' => (string) Str::uuid(),
                'timestamp' => $timestamp->toIso8601String(),
                'country' => $country,
                'data' => $data,
            ];

            $routingKey = 'employee.' . strtolower($country) . '.' . strtolower(
                str_replace('Employee', '', $eventType)
            );

            $this->publishToRabbitMQ($payload, $routingKey);

            Log::info("Published {$eventType} event", [
                'event_id' => $payload['event_id'],
                'routing_key' => $routingKey,
                'employee_id' => $data['employee_id'] ?? null,
            ]);

        } catch (\Throwable $e) {
            Log::error("Failed to publish {$eventType} event", [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }

    private function publishToRabbitMQ(array $payload, string $routingKey): void
    {
        $connection = $this->getConnection();
        $channel = $connection->channel();

        $exchange = config('queue.connections.rabbitmq.options.queue.exchange', 'employee_events');
        $exchangeType = config('queue.connections.rabbitmq.options.queue.exchange_type', 'topic');

        $channel->exchange_declare($exchange, $exchangeType, false, true, false);

        $message = new AMQPMessage(
            json_encode($payload),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]
        );

        $channel->basic_publish($message, $exchange, $routingKey);

        $channel->close();
    }

    private function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                config('queue.connections.rabbitmq.hosts.0.host', 'rabbitmq'),
                config('queue.connections.rabbitmq.hosts.0.port', 5672),
                config('queue.connections.rabbitmq.hosts.0.user', 'guest'),
                config('queue.connections.rabbitmq.hosts.0.password', 'guest'),
                config('queue.connections.rabbitmq.hosts.0.vhost', '/')
            );
        }

        return $this->connection;
    }

    public function __destruct()
    {
        if ($this->connection !== null && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }
}
