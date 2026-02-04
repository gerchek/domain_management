<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'server_id',
        'batch_id',
        'domain_name',
        'status',
        'error_message',
        'purchased_at',
        'dns_set_at',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
            'dns_set_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DomainBatch::class, 'batch_id');
    }

    public function siteDeployments(): HasMany
    {
        return $this->hasMany(SiteDeployment::class);
    }

    public function domainDeployments(): HasMany
    {
        return $this->hasMany(DomainDeployment::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPurchased(): bool
    {
        return $this->status === 'purchased';
    }

    public function isDnsSet(): bool
    {
        return $this->status === 'dns_set';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function markAsPurchased(): void
    {
        $this->update([
            'status' => 'purchased',
            'purchased_at' => now(),
        ]);
    }

    public function markAsDnsSet(): void
    {
        $this->update([
            'status' => 'dns_set',
            'dns_set_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePurchased($query)
    {
        return $query->where('status', 'purchased');
    }

    public function scopeDnsSet($query)
    {
        return $query->where('status', 'dns_set');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', 'skipped');
    }
}
