<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainDeployment extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'site_project_id',
        'status',
        'server_host',
        'server_path',
        'deployment_log',
        'tracking_type',
        'palladium_config_id',
        'offer_id',
        'tracking_config',
        'palladium_config',
        'deployed_at',
    ];

    protected function casts(): array
    {
        return [
            'deployment_log' => 'array',
            'tracking_config' => 'array',
            'palladium_config' => 'array',
            'deployed_at' => 'datetime',
        ];
    }

    /**
     * Get the domain that this deployment belongs to
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the site project (white site) that this deployment belongs to
     */
    public function siteProject(): BelongsTo
    {
        return $this->belongsTo(SiteProject::class);
    }

    /**
     * Get the palladium config for this deployment
     */
    public function palladiumConfigRelation(): BelongsTo
    {
        return $this->belongsTo(PalladiumConfig::class, 'palladium_config_id');
    }

    /**
     * Get the offer for this deployment
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * Check if this is a keitaro tracking type deployment
     */
    public function isKeitaroType(): bool
    {
        return $this->tracking_type === 'keitaro';
    }

    /**
     * Check if this is an offer tracking type deployment
     */
    public function isOfferType(): bool
    {
        return $this->tracking_type === 'offer';
    }

    /**
     * Check if deployment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if deployment is deployed
     */
    public function isDeployed(): bool
    {
        return $this->status === 'deployed';
    }

    /**
     * Check if deployment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark deployment as deployed
     */
    public function markAsDeployed(): void
    {
        $this->update([
            'status' => 'deployed',
            'deployed_at' => now(),
        ]);
    }

    /**
     * Mark deployment as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $logs = $this->deployment_log ?? [];
        $logs[] = [
            'timestamp' => now()->toIso8601String(),
            'type' => 'error',
            'message' => $errorMessage,
        ];

        $this->update([
            'status' => 'failed',
            'deployment_log' => $logs,
        ]);
    }

    /**
     * Add log entry
     */
    public function addLog(string $type, string $message): void
    {
        $logs = $this->deployment_log ?? [];
        $logs[] = [
            'timestamp' => now()->toIso8601String(),
            'type' => $type,
            'message' => $message,
        ];

        $this->update(['deployment_log' => $logs]);
    }

    /**
     * Scope for pending deployments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for deployed deployments
     */
    public function scopeDeployed($query)
    {
        return $query->where('status', 'deployed');
    }

    /**
     * Scope for failed deployments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
