<?php

namespace App\Console\Commands;

use App\Services\Monitoring\MqttIngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;

class MonitoringMqttConsumeCommand extends Command
{
    protected $signature = 'monitoring:mqtt-consume {--topic=}';

    protected $description = 'Consume infusion readings and heartbeat events from MQTT broker.';

    public function handle(MqttIngestionService $ingestionService): int
    {
        $host = (string) config('mqtt.host');
        $port = (int) config('mqtt.port');
        $clientId = (string) config('mqtt.client_id');
        $topic = $this->option('topic') ?: (string) config('mqtt.topic');
        $qos = (int) config('mqtt.qos');

        $client = new MqttClient($host, $port, $clientId);

        $settings = (new ConnectionSettings())
            ->setUsername((string) config('mqtt.username'))
            ->setPassword((string) config('mqtt.password'))
            ->setUseTls((bool) config('mqtt.use_tls'));

        try {
            $client->connect($settings, true);

            $this->info("Connected to MQTT broker at {$host}:{$port}, topic={$topic}");

            $client->subscribe($topic, function (string $topic, string $message) use ($ingestionService): void {
                $ingestionService->processMessage($topic, trim($message));
            }, $qos);

            $client->loop(true);

            return self::SUCCESS;
        } catch (MqttClientException $exception) {
            $this->error($exception->getMessage());
            Log::error('MQTT consumer error', ['message' => $exception->getMessage()]);

            return self::FAILURE;
        }
    }
}
