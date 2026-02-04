<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'ssh_username',
        'ssh_password',
        'ssh_private_key',
        'ssh_port',
        'max_domains',
        'current_domains_count',
        'is_active',
    ];

    protected $hidden = [
        'ssh_password',
        'ssh_private_key',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ssh_port' => 'integer',
            'max_domains' => 'integer',
            'current_domains_count' => 'integer',
        ];
    }

    public function setSshPasswordAttribute($value): void
    {
        $this->attributes['ssh_password'] = Crypt::encryptString($value);
    }

    public function getSshPasswordDecryptedAttribute(): string
    {
        return Crypt::decryptString($this->attributes['ssh_password']);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function domainBatches(): HasMany
    {
        return $this->hasMany(DomainBatch::class);
    }

    public function hasAvailableSlots(): bool
    {
        return $this->current_domains_count < $this->max_domains;
    }

    public function availableSlots(): int
    {
        return $this->max_domains - $this->current_domains_count;
    }

    public function incrementDomainsCount(int $count = 1): void
    {
        $this->increment('current_domains_count', $count);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithAvailableSlots($query)
    {
        return $query->whereRaw('current_domains_count < max_domains');
    }
}
