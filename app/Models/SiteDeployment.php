<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteDeployment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'domain_id',
        'status',
        'ssl_installed',
        'error_message',
        'deployed_at',
    ];

    protected function casts(): array
    {
        return [
            'ssl_installed' => 'boolean',
            'deployed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(SiteProject::class, 'project_id');
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDeploying(): bool
    {
        return $this->status === 'deploying';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsDeploying(): void
    {
        $this->update(['status' => 'deploying']);
    }

    public function markAsCompleted(bool $sslInstalled = false): void
    {
        $this->update([
            'status' => 'completed',
            'ssl_installed' => $sslInstalled,
            'deployed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Получить сервер для этого деплоя (через домен)
     */
    public function getServer(): ?Server
    {
        return $this->domain?->server;
    }
}
