<?php

namespace App\Console\Commands;

use App\Listeners\ProcessEmployeeEvent;
use App\Services\ChecklistService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeEmployeeEvents extends Command
{
    protected $signature = 'rabbitmq:consume
                            {--exchange=employee_events : Exchange name}
                            {--queue=hub_service_events : Queue name}
                            {--routing-key=employee.# : Routing key pattern}';

    protected $description = 'Consume employee events from RabbitMQ and process them';

    public function handle(ChecklistService $checklistService): int
    {
        $exchange = $this->option('exchange');
        $queueName = $this->option('queue');
        $routingKey = $this->option('routing-key');

        $this->info("Starting RabbitMQ consumer on exchange '{$exchange}', queue '{$queueName}'");

        $connection = $this->createConnection();
        $channel = $connection->channel();

        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $exchange, $routingKey);

        $processor = new ProcessEmployeeEvent($checklistService);

        $channel->basic_consume(
            $queueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($processor) {
                $this->processMessage($message, $processor);
            }
        );

        $this->info('Consumer started. Waiting for messages...');

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return self::SUCCESS;
    }

    private function processMessage(AMQPMessage $message, ProcessEmployeeEvent $processor): void
    {
        try {
            $payload = json_decode($message->body, true, 512, JSON_THROW_ON_ERROR);
            $eventType = $payload['event_type'] ?? null;

            Log::info('Received RabbitMQ event', [
                'event_type' => $eventType,
                'event_id' => $payload['event_id'] ?? null,
                'country' => $payload['country'] ?? null,
            ]);

            match ($eventType) {
                'EmployeeCreated' => $processor->handleCreated($payload),
                'EmployeeUpdated' => $processor->handleUpdated($payload),
                'EmployeeDeleted' => $processor->handleDeleted($payload),
                default => Log::warning('Unknown event type received', ['event_type' => $eventType]),
            };

            $message->ack();

        } catch (\JsonException $e) {
            Log::error('Failed to decode RabbitMQ message', ['error' => $e->getMessage()]);
            $message->nack(false);
        } catch (\Throwable $e) {
            Log::error('Failed to process RabbitMQ event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $message->nack(true);
        }
    }

    private function createConnection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            config('queue.connections.rabbitmq.hosts.0.host', env('RABBITMQ_HOST', 'rabbitmq')),
            config('queue.connections.rabbitmq.hosts.0.port', env('RABBITMQ_PORT', 5672)),
            config('queue.connections.rabbitmq.hosts.0.user', env('RABBITMQ_USER', 'guest')),
            config('queue.connections.rabbitmq.hosts.0.password', env('RABBITMQ_PASSWORD', 'guest')),
            config('queue.connections.rabbitmq.hosts.0.vhost', env('RABBITMQ_VHOST', '/'))
        );
    }
}
