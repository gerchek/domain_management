<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'zip_file_path',
    ];

    /**
     * Get deployments using this offer
     */
    public function domainDeployments(): HasMany
    {
        return $this->hasMany(DomainDeployment::class);
    }

    /**
     * Check if this offer is used by any deployment
     */
    public function isInUse(): bool
    {
        return $this->domainDeployments()->exists();
    }

    /**
     * Get full path to the uploaded ZIP file
     */
    public function getFullZipPathAttribute(): ?string
    {
        if (!$this->zip_file_path) {
            return null;
        }

        // Prevent path traversal
        if (str_contains($this->zip_file_path, '..')) {
            return null;
        }

        // Laravel 11 saves to storage/app/private/ by default
        $privatePath = storage_path('app/private/' . $this->zip_file_path);
        if (file_exists($privatePath)) {
            return $privatePath;
        }

        // Fallback to old path for backwards compatibility
        return storage_path('app/' . $this->zip_file_path);
    }

    /**
     * Check if the ZIP file exists using Storage facade
     */
    public function zipExists(): bool
    {
        if (!$this->zip_file_path || str_contains($this->zip_file_path, '..')) {
            return false;
        }

        // Check both private and public paths
        return Storage::disk('local')->exists('private/' . $this->zip_file_path)
            || Storage::disk('local')->exists($this->zip_file_path);
    }
}
