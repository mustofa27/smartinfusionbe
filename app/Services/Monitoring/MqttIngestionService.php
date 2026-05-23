<?php

namespace App\Services\Monitoring;

use App\Models\Device;
use App\Models\InfusionReading;
use App\Models\InfusionSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MqttIngestionService
{
    public function __construct(private readonly AlertEngineService $alertEngineService)
    {
    }

    /**
     * @param mixed $payload
     */
    public function processMessage(string $topic, mixed $payload): void
    {
        $topicMeta = $this->parseTopic($topic);
        if (! $topicMeta) {
            Log::warning('MQTT topic ignored: unsupported format', ['topic' => $topic]);

            return;
        }

        $device = Device::query()
            ->where('mqtt_topic', $topicMeta['device_topic_id'])
            ->first();

        if (! $device) {
            $device = Device::query()
            ->where('mqtt_topic', $topic)
            ->first();
        }

        if (! $device) {
            Log::warning('MQTT device not found for topic', ['topic' => $topic]);

            return;
        }

        $this->processReading($device, $payload);
    }

    /**
     * @return array{device_topic_id:string}|null
     */
    private function parseTopic(string $topic): ?array
    {
        if (! preg_match('#^smart-infusion/([^/]+)/weight$#', $topic, $matches)) {
            return null;
        }

        return [
            'device_topic_id' => $matches[1],
        ];
    }

    /**
     * @param mixed $payload
     */
    private function processHeartbeat(Device $device, mixed $payload): void
    {
        $recordedAt = now();
        $status = 'online';

        $device->status = $status;
        $device->last_seen_at = $recordedAt;
        $device->save();
    }

    /**
     * @param mixed $payload
     */
    private function processReading(Device $device, mixed $payload): void
    {
        if (! is_numeric($payload)) {
            Log::warning('MQTT reading rejected: non-numeric payload', [
                'device_id' => $device->id,
                'payload' => $payload,
            ]);

            return;
        }

        $weight = (float) $payload;
        if ($weight < 0) {
            Log::warning('MQTT reading rejected: negative weight', [
                'device_id' => $device->id,
                'payload' => $payload,
            ]);

            return;
        }

        $recordedAt = now();

        $device->status = 'online';
        $device->last_seen_at = $recordedAt;
        $device->save();

        $session = InfusionSession::query()
            ->where('organization_id', $device->organization_id)
            ->where('device_id', $device->id)
            ->where('status', 'active')
            ->latest('started_at')
            ->first();

        if (! $session) {
            return;
        }

        $density = max(0.0001, (float) $session->fluid_density_g_per_ml);
        $remainingMl = max(0, ($weight - (float) $session->bag_empty_weight_grams) / $density);

        $flow = null;
        if ($session->last_remaining_ml !== null && $session->last_reading_at) {
            $previousAt = Carbon::parse($session->last_reading_at);
            $deltaSeconds = $previousAt->diffInSeconds($recordedAt, false);

            if ($deltaSeconds > 0) {
                $deltaHours = $deltaSeconds / 3600;
                $flow = ((float) $session->last_remaining_ml - $remainingMl) / $deltaHours;
                $flow = max(0, $flow);
            }
        }

        $reading = InfusionReading::query()->create([
            'organization_id' => $session->organization_id,
            'infusion_session_id' => $session->id,
            'device_id' => $session->device_id,
            'measured_weight_grams' => $weight,
            'remaining_ml' => $remainingMl,
            'flow_ml_per_hour' => $flow,
            'recorded_at' => $recordedAt,
            'received_at' => now(),
            'raw_payload' => ['weight_grams' => $weight],
        ]);

        $session->last_weight_grams = $weight;
        $session->last_remaining_ml = $remainingMl;
        $session->last_flow_ml_per_hour = $flow;
        $session->last_reading_at = $recordedAt;
        $session->save();

        $this->alertEngineService->evaluateReading($session, $reading);
    }

    private function parseRecordedAt(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
