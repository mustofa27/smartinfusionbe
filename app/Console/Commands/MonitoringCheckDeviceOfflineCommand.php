<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\Monitoring\AlertEngineService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MonitoringCheckDeviceOfflineCommand extends Command
{
    protected $signature = 'monitoring:check-device-offline';

    protected $description = 'Evaluate device offline status and trigger or resolve alerts.';

    public function handle(AlertEngineService $alertEngineService): int
    {
        $now = Carbon::now();

        $count = 0;
        Device::query()
            ->whereNotIn('status', ['maintenance', 'retired'])
            ->orderBy('id')
            ->chunkById(100, function ($devices) use (&$count, $alertEngineService, $now): void {
                foreach ($devices as $device) {
                    $alertEngineService->evaluateDeviceOffline($device, $now);
                    $count++;
                }
            });

        $this->info("Checked {$count} devices for offline condition.");

        return self::SUCCESS;
    }
}
