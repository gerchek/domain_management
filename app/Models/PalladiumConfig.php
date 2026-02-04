<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PalladiumConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'geo',
        'client_id',
        'client_company',
        'client_secret',
        'banner_source',
        'file_path',
    ];

    protected $casts = [
        'client_id' => 'integer',
    ];

    /**
     * Get deployments using this config
     */
    public function domainDeployments(): HasMany
    {
        return $this->hasMany(DomainDeployment::class);
    }

    /**
     * Check if this config is used by any deployment
     */
    public function isInUse(): bool
    {
        return $this->domainDeployments()->exists();
    }

    /**
     * Get display name with geo
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->geo) {
            return "{$this->name} ({$this->geo})";
        }
        return $this->name;
    }

    /**
     * Get full path to the uploaded file
     */
    public function getFullFilePathAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        // Prevent path traversal
        if (str_contains($this->file_path, '..')) {
            return null;
        }

        // Laravel 11 saves to storage/app/private/ by default
        $privatePath = storage_path('app/private/' . $this->file_path);
        if (file_exists($privatePath)) {
            return $privatePath;
        }

        // Fallback to old path for backwards compatibility
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Check if the uploaded file exists using Storage facade
     */
    public function fileExists(): bool
    {
        if (!$this->file_path || str_contains($this->file_path, '..')) {
            return false;
        }

        // Check both private and public paths
        return Storage::disk('local')->exists('private/' . $this->file_path)
            || Storage::disk('local')->exists($this->file_path);
    }
}
