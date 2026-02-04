<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class SiteProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'prompt_id',
        'storage_path',
        'status',
        'files_count',
        'total_size',
        'error_message',
        'generated_at',
        'black_site_archive_path',
        'site_type',
        'macros',
    ];

    protected function casts(): array
    {
        return [
            'files_count' => 'integer',
            'total_size' => 'integer',
            'generated_at' => 'datetime',
            'macros' => 'array',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(SiteDeployment::class, 'project_id');
    }

    public function domainDeployments(): HasMany
    {
        return $this->hasMany(DomainDeployment::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isGenerating(): bool
    {
        return $this->status === 'generating';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsGenerating(): void
    {
        $this->update(['status' => 'generating']);
    }

    public function markAsReady(string $storagePath, int $filesCount, int $totalSize): void
    {
        $this->update([
            'status' => 'ready',
            'storage_path' => $storagePath,
            'files_count' => $filesCount,
            'total_size' => $totalSize,
            'generated_at' => now(),
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
     * Получить полный путь к папке проекта в storage
     */
    public function getFullStoragePath(): string
    {
        return Storage::disk('local')->path($this->storage_path);
    }

    /**
     * Получить список файлов проекта
     */
    public function getFiles(): array
    {
        if (!$this->storage_path) {
            return [];
        }

        $files = [];
        $path = $this->storage_path;

        if (Storage::disk('local')->exists($path)) {
            $allFiles = Storage::disk('local')->allFiles($path);
            foreach ($allFiles as $file) {
                $relativePath = str_replace($path . '/', '', $file);
                $files[$relativePath] = Storage::disk('local')->get($file);
            }
        }

        return $files;
    }
}
