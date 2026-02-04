<?php

namespace App\Jobs;

use App\Models\DomainDeployment;
use App\Services\DeployService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeployBlackSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900; // 15 minutes for SFTP + SSL operations

    /**
     * Exponential backoff between retries (in seconds)
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public DomainDeployment $deployment
    ) {}

    public function handle(DeployService $deployService): void
    {
        $trackingType = $this->deployment->tracking_type ?? 'keitaro';

        Log::info('DeployBlackSiteJob started', [
            'deployment_id' => $this->deployment->id,
            'tracking_type' => $trackingType,
        ]);

        $typeLabel = $trackingType === 'offer' ? 'Offer' : 'Keitaro';
        $this->deployment->addLog('info', "Starting {$typeLabel} black site deployment (with Palladium filter)...");

        $result = $deployService->attachBlackSite($this->deployment);

        if ($result['success']) {
            $this->deployment->update([
                'status' => 'deployed',
                'deployed_at' => now(),
                'server_host' => $this->deployment->domain->server->ip_address ?? null,
                'server_path' => "/var/www/{$this->deployment->domain->domain_name}",
            ]);
            $this->deployment->addLog('success', "{$typeLabel} black сайт успешно задеплоен");

            Log::info('DeployBlackSiteJob completed', [
                'deployment_id' => $this->deployment->id,
                'tracking_type' => $trackingType,
            ]);
        } else {
            $this->deployment->markAsFailed($result['message']);

            Log::error('DeployBlackSiteJob failed', [
                'deployment_id' => $this->deployment->id,
                'tracking_type' => $trackingType,
                'error' => $result['message'],
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DeployBlackSiteJob exception', [
            'deployment_id' => $this->deployment->id,
            'error' => $exception->getMessage(),
        ]);

        $this->deployment->markAsFailed($exception->getMessage());
    }
}
