<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\NurseFcmToken;
use App\Services\Notifications\FcmPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchAlertFcmJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $alertId)
    {
    }

    public function handle(FcmPushService $fcmPushService): void
    {
        $alert = Alert::query()->find($this->alertId);
        if (! $alert) {
            return;
        }

        $targetUserIds = \DB::table('nurse_device_subscriptions')
            ->where('organization_id', $alert->organization_id)
            ->where('device_id', $alert->device_id)
            ->pluck('nurse_user_id')
            ->all();

        if ($targetUserIds === []) {
            return;
        }

        $tokens = NurseFcmToken::query()
            ->where('organization_id', $alert->organization_id)
            ->whereIn('nurse_user_id', $targetUserIds)
            ->where('is_active', true)
            ->get();

        foreach ($tokens as $token) {
            $delivery = AlertDelivery::query()->create([
                'organization_id' => $alert->organization_id,
                'alert_id' => $alert->id,
                'user_id' => $token->nurse_user_id,
                'channel' => 'fcm',
                'fcm_token' => $token->fcm_token,
                'delivery_status' => 'queued',
            ]);

            $result = $fcmPushService->sendToToken(
                $token->fcm_token,
                strtoupper($alert->severity).' infusion alert',
                $alert->message,
                [
                    'alert_id' => (string) $alert->id,
                    'device_id' => (string) $alert->device_id,
                    'alert_type' => $alert->alert_type,
                    'status' => $alert->status,
                ],
            );

            $delivery->sent_at = now();
            $delivery->delivery_status = $result['ok'] ? 'sent' : 'failed';
            $delivery->provider_message_id = $result['message_id'];
            $delivery->error_message = $result['error'];
            $delivery->save();
        }
    }
}
