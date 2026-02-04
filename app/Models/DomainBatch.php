<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'server_id',
        'file_name',
        'pending_domains',
        'total_domains',
        'target_count',
        'processed_domains',
        'successful_domains',
        'failed_domains',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'pending_domains' => 'array',
            'total_domains' => 'integer',
            'target_count' => 'integer',
            'processed_domains' => 'integer',
            'successful_domains' => 'integer',
            'failed_domains' => 'integer',
        ];
    }

    /**
     * Получить следующую порцию доменов для обработки
     */
    public function getNextDomains(int $count): array
    {
        $domains = $this->pending_domains ?? [];
        return array_slice($domains, 0, $count);
    }

    /**
     * Удалить обработанные домены из очереди
     */
    public function removeProcessedDomains(array $processedDomains): void
    {
        $domains = $this->pending_domains ?? [];
        $domains = array_values(array_diff($domains, $processedDomains));
        $this->update(['pending_domains' => $domains]);
    }

    /**
     * Проверить есть ли ещё домены для обработки
     */
    public function hasPendingDomains(): bool
    {
        return !empty($this->pending_domains);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'batch_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_domains === 0) {
            return 0;
        }
        return round(($this->processed_domains / $this->total_domains) * 100, 2);
    }

    /**
     * Проверяет, достигнута ли цель по количеству успешных покупок
     */
    public function isTargetReached(): bool
    {
        if ($this->target_count === null) {
            return false;
        }
        return $this->successful_domains >= $this->target_count;
    }

    /**
     * Возвращает сколько доменов ещё нужно купить
     */
    public function getRemainingTarget(): ?int
    {
        if ($this->target_count === null) {
            return null;
        }
        return max(0, $this->target_count - $this->successful_domains);
    }

    /**
     * Получить эффективную цель (target_count или total_domains)
     */
    public function getEffectiveTarget(): int
    {
        return $this->target_count ?? $this->total_domains;
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function incrementProcessed(): void
    {
        $this->increment('processed_domains');
    }

    public function incrementSuccessful(): void
    {
        $this->increment('successful_domains');
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_domains');
    }
}
