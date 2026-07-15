<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\NurseFcmToken;
use App\Services\Notifications\FcmPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestFcmController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = (int) $request->user()->organization_id;

        $devices = Device::query()
            ->where('organization_id', $orgId)
            ->orderBy('serial_number')
            ->get(['id', 'serial_number']);

        $totalTokens = NurseFcmToken::query()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->count();

        return view('admin.test-fcm.index', compact('devices', 'totalTokens'));
    }

    public function send(Request $request, FcmPushService $fcmPushService): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $validated = $request->validate([
            'target' => ['required', 'string', 'in:all_tokens,device_subscribers'],
            'device_id' => ['required_if:target,device_subscribers', 'nullable', 'integer', 'exists:devices,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'alert_type' => ['nullable', 'string', 'max:50'],
            'severity' => ['nullable', 'string', 'in:info,warning,critical'],
        ]);

        $tokensQuery = NurseFcmToken::query()
            ->where('organization_id', $orgId)
            ->where('is_active', true);

        if ($validated['target'] === 'device_subscribers') {
            $deviceId = (int) $validated['device_id'];

            $subscriberUserIds = \DB::table('nurse_device_subscriptions')
                ->where('organization_id', $orgId)
                ->where('device_id', $deviceId)
                ->pluck('nurse_user_id');

            if ($subscriberUserIds->isEmpty()) {
                return back()
                    ->withInput()
                    ->withErrors(['device_id' => 'No nurses are subscribed to this device.']);
            }

            $tokensQuery->whereIn('nurse_user_id', $subscriberUserIds);
        }

        $tokens = $tokensQuery->get();

        if ($tokens->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['target' => 'No active FCM tokens found for the selected target.']);
        }

        $data = [];
        if ($validated['alert_type']) {
            $data['alert_type'] = $validated['alert_type'];
        }
        if ($validated['severity']) {
            $data['severity'] = $validated['severity'];
        }
        $data['sender'] = $request->user()->name;
        $data['organization_id'] = (string) $orgId;

        $successCount = 0;
        $failCount = 0;

        foreach ($tokens as $token) {
            $result = $fcmPushService->sendToToken(
                $token->fcm_token,
                $validated['title'],
                $validated['body'],
                $data,
            );

            if ($result['ok']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        $totalTargeted = $tokens->count();
        $message = "Test notification sent to {$successCount}/{$totalTargeted} device(s).";
        if ($failCount > 0) {
            $message .= " {$failCount} failed.";
        }

        return back()->with('success', $message);
    }
}