<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteProject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SiteProjectController extends Controller
{
    /**
     * Validate storage path to prevent path traversal
     */
    private function validateStoragePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // Check for path traversal attempts
        if (str_contains($path, '..') || str_contains($path, '//')) {
            Log::warning('Path traversal attempt detected', ['path' => $path]);
            return null;
        }

        return $path;
    }

    /**
     * Get the full storage path for a project
     */
    private function getFullStoragePath(string $relativePath): ?string
    {
        // Try private directory first (Laravel 11 default)
        if (Storage::disk('local')->exists('private/' . $relativePath)) {
            return storage_path('app/private/' . $relativePath);
        }

        // Fallback to old path
        if (Storage::disk('local')->exists($relativePath)) {
            return storage_path('app/' . $relativePath);
        }

        return null;
    }

    /**
     * Get the relative storage path for deletion
     */
    private function getRelativeStoragePath(string $path): ?string
    {
        // Check private directory
        if (Storage::disk('local')->exists('private/' . $path)) {
            return 'private/' . $path;
        }

        // Check regular directory
        if (Storage::disk('local')->exists($path)) {
            return $path;
        }

        return null;
    }
    /**
     * Список всех проектов (white sites)
     */
    public function index(Request $request)
    {
        $query = SiteProject::with(['buyer', 'prompt', 'deployments.domain']);

        // Фильтр по байеру
        if ($request->filled('buyer_id')) {
            $query->where('buyer_id', $request->buyer_id);
        }

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $projects = $query->latest()->paginate(20);
        $buyers = User::where('role', 'buyer')->get();

        // Статистика
        $stats = [
            'total' => SiteProject::count(),
            'total_size' => SiteProject::sum('total_size'),
            'ready' => SiteProject::where('status', 'ready')->count(),
            'failed' => SiteProject::where('status', 'failed')->count(),
            'removed' => SiteProject::where('status', 'removed')->count(),
        ];

        return view('admin.site-projects.index', compact('projects', 'buyers', 'stats'));
    }

    /**
     * Детали проекта
     */
    public function show(SiteProject $project)
    {
        $project->load(['buyer', 'prompt', 'deployments.domain.server']);

        // Получить список файлов
        $files = [];
        $storageInfo = [
            'exists' => false,
            'path' => null,
            'size' => 0,
        ];

        $validatedPath = $this->validateStoragePath($project->storage_path);

        if ($validatedPath) {
            $fullPath = $this->getFullStoragePath($validatedPath);

            if ($fullPath && is_dir($fullPath)) {
                $storageInfo['exists'] = true;
                $storageInfo['path'] = $fullPath;
                $storageInfo['size'] = $this->getDirectorySize($fullPath);
                $files = $this->getDirectoryFiles($fullPath);
            }
        }

        return view('admin.site-projects.show', compact('project', 'files', 'storageInfo'));
    }

    /**
     * Удалить файлы проекта из storage (не с сервера)
     */
    public function deleteFiles(SiteProject $project)
    {
        $validatedPath = $this->validateStoragePath($project->storage_path);

        if (!$validatedPath) {
            return back()->with('error', 'Путь к файлам не найден или недопустим');
        }

        $relativePath = $this->getRelativeStoragePath($validatedPath);

        if ($relativePath && Storage::disk('local')->exists($relativePath)) {
            // Use Laravel Storage API for safe deletion
            Storage::disk('local')->deleteDirectory($relativePath);

            // Обновляем проект
            $project->update([
                'storage_path' => null,
                'files_count' => 0,
                'total_size' => 0,
            ]);

            Log::info('Project files deleted from storage', ['project_id' => $project->id]);

            return back()->with('success', "Файлы проекта #{$project->id} удалены из storage");
        }

        return back()->with('error', 'Директория не найдена');
    }

    /**
     * Полное удаление проекта (файлы + запись в БД)
     */
    public function destroy(SiteProject $project)
    {
        // Проверяем активные деплои
        $activeDeployments = $project->deployments()
            ->whereIn('status', ['pending', 'deploying', 'completed'])
            ->count();

        if ($activeDeployments > 0) {
            return back()->with('error', "Нельзя удалить проект с {$activeDeployments} активными деплоями. Сначала удалите деплои.");
        }

        // Удаляем файлы используя Storage API
        $validatedPath = $this->validateStoragePath($project->storage_path);

        if ($validatedPath) {
            $relativePath = $this->getRelativeStoragePath($validatedPath);
            if ($relativePath) {
                Storage::disk('local')->deleteDirectory($relativePath);
            }
        }

        $projectId = $project->id;
        $project->delete();

        Log::info('Project deleted', ['project_id' => $projectId]);

        return redirect()->route('admin.site-projects.index')
            ->with('success', "Проект #{$projectId} полностью удалён");
    }

    /**
     * Получить размер директории
     */
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Получить список файлов директории
     */
    private function getDirectoryFiles(string $path): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $relativePath = str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $files[] = [
                'name' => $relativePath,
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        return $files;
    }

}
