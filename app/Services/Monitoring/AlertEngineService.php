<?php

namespace App\Services\Monitoring;

use App\Jobs\DispatchAlertFcmJob;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Device;
use App\Models\InfusionReading;
use App\Models\InfusionSession;
use Carbon\Carbon;

class AlertEngineService
{
    /**
     * @return array<string, mixed>
     */
    private function getRuleConfig(int $organizationId, string $code): array
    {
        $fallback = match ($code) {
            'low_volume' => ['threshold' => 50.0, 'unit' => 'ml', 'cooldown' => 300],
            'no_flow' => ['threshold' => 1.0, 'unit' => 'ml_per_hour', 'cooldown' => 300],
            'device_offline' => ['threshold' => 5.0, 'unit' => 'minute', 'cooldown' => 300],
            default => ['threshold' => 0.0, 'unit' => 'custom', 'cooldown' => 300],
        };

        $rule = AlertRule::query()
            ->where('organization_id', $organizationId)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $rule) {
            return $fallback;
        }

        return [
            'threshold' => (float) $rule->threshold_value,
            'unit' => $rule->threshold_unit,
            'cooldown' => max(30, (int) $rule->cooldown_seconds),
        ];
    }

    public function evaluateReading(InfusionSession $session, InfusionReading $reading): void
    {
        $lowVolumeRule = $this->getRuleConfig($session->organization_id, 'low_volume');
        $isLowVolume = (float) $reading->remaining_ml <= $lowVolumeRule['threshold'];

        $this->handleRuleResult(
            session: $session,
            alertType: 'low_volume',
            isTriggered: $isLowVolume,
            severity: 'warning',
            message: sprintf('Remaining volume is low (%.2f ml).', (float) $reading->remaining_ml),
            cooldownSeconds: (int) $lowVolumeRule['cooldown'],
            now: Carbon::parse($reading->recorded_at),
        );

        $noFlowRule = $this->getRuleConfig($session->organization_id, 'no_flow');
        $flow = $reading->flow_ml_per_hour !== null ? (float) $reading->flow_ml_per_hour : null;
        $isNoFlow = $flow !== null && $flow <= $noFlowRule['threshold'];

        $this->handleRuleResult(
            session: $session,
            alertType: 'no_flow',
            isTriggered: $isNoFlow,
            severity: 'critical',
            message: sprintf('Flow rate is below threshold (%.2f ml/h).', $flow ?? 0.0),
            cooldownSeconds: (int) $noFlowRule['cooldown'],
            now: Carbon::parse($reading->recorded_at),
        );
    }

    public function evaluateDeviceOffline(Device $device, Carbon $now): void
    {
        $rule = $this->getRuleConfig($device->organization_id, 'device_offline');
        $thresholdMinutes = (float) $rule['threshold'];
        $lastSeenAt = $device->last_seen_at ? Carbon::parse($device->last_seen_at) : null;

        $isOffline = true;
        if ($lastSeenAt) {
            $isOffline = $lastSeenAt->diffInMinutes($now) >= $thresholdMinutes;
        }

        $activeSession = InfusionSession::query()
            ->where('organization_id', $device->organization_id)
            ->where('device_id', $device->id)
            ->where('status', 'active')
            ->latest('started_at')
            ->first();

        if (! $activeSession) {
            return;
        }

        $this->handleRuleResult(
            session: $activeSession,
            alertType: 'device_offline',
            isTriggered: $isOffline,
            severity: 'critical',
            message: 'Device appears offline based on heartbeat timeout.',
            cooldownSeconds: (int) $rule['cooldown'],
            now: $now,
        );
    }

    private function handleRuleResult(
        InfusionSession $session,
        string $alertType,
        bool $isTriggered,
        string $severity,
        string $message,
        int $cooldownSeconds,
        Carbon $now,
    ): void {
        if ($isTriggered) {
            $dedupeKey = $this->buildDedupeKey(
                organizationId: $session->organization_id,
                deviceId: $session->device_id,
                alertType: $alertType,
                cooldownSeconds: $cooldownSeconds,
                at: $now,
            );

            $existing = Alert::query()
                ->where('organization_id', $session->organization_id)
                ->where('device_id', $session->device_id)
                ->where('alert_type', $alertType)
                ->whereIn('status', ['open', 'acknowledged'])
                ->where('dedupe_key', $dedupeKey)
                ->first();

            if ($existing) {
                return;
            }

            $alert = Alert::query()->create([
                'organization_id' => $session->organization_id,
                'infusion_session_id' => $session->id,
                'patient_id' => $session->patient_id,
                'device_id' => $session->device_id,
                'alert_type' => $alertType,
                'severity' => $severity,
                'message' => $message,
                'triggered_at' => $now,
                'status' => 'open',
                'dedupe_key' => $dedupeKey,
                'payload' => [
                    'auto_resolve' => true,
                    'triggered_at' => $now->toIso8601String(),
                ],
            ]);

            DispatchAlertFcmJob::dispatch($alert->id);

            return;
        }

        Alert::query()
            ->where('organization_id', $session->organization_id)
            ->where('device_id', $session->device_id)
            ->where('alert_type', $alertType)
            ->whereIn('status', ['open', 'acknowledged'])
            ->update([
                'status' => 'resolved',
                'resolved_at' => $now,
                'resolved_by_user_id' => null,
            ]);
    }

    private function buildDedupeKey(
        int $organizationId,
        int $deviceId,
        string $alertType,
        int $cooldownSeconds,
        Carbon $at,
    ): string {
        $bucket = intdiv($at->getTimestamp(), max(1, $cooldownSeconds));

        return implode(':', [$organizationId, $deviceId, $alertType, $bucket]);
    }
}
