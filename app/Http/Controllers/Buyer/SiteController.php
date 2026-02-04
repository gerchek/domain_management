<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSiteJob;
use App\Models\Domain;
use App\Models\Prompt;
use App\Models\SiteDeployment;
use App\Models\SiteProject;
use App\Services\DeployService;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /**
     * Список проектов байера
     */
    public function index()
    {
        $projects = SiteProject::where('buyer_id', auth()->id())
            ->with(['prompt', 'deployments.domain'])
            ->latest()
            ->paginate(20);

        return view('buyer.sites.index', compact('projects'));
    }

    /**
     * Шаг 1: Выбор доменов
     */
    public function create()
    {
        // Домены со статусом dns_set, которые ещё не используются в активных проектах
        $domains = Domain::where('buyer_id', auth()->id())
            ->where('status', 'dns_set')
            ->whereDoesntHave('siteDeployments', function ($query) {
                $query->whereIn('status', ['pending', 'deploying', 'completed']);
            })
            ->with('server')
            ->get();

        return view('buyer.sites.create', compact('domains'));
    }

    /**
     * Шаг 2: Выбор промпта
     */
    public function selectPrompt(Request $request)
    {
        $request->validate([
            'domains' => ['required', 'array', 'min:1'],
            'domains.*' => ['exists:domains,id'],
        ]);

        // Проверяем что домены принадлежат байеру
        $domainIds = $request->domains;
        $domains = Domain::whereIn('id', $domainIds)
            ->where('buyer_id', auth()->id())
            ->where('status', 'dns_set')
            ->get();

        if ($domains->count() !== count($domainIds)) {
            return back()->with('error', 'Некоторые домены недоступны');
        }

        $prompts = Prompt::active()->get();

        return view('buyer.sites.select-prompt', compact('domains', 'prompts'));
    }

    /**
     * Запуск генерации
     */
    public function store(Request $request)
    {
        $request->validate([
            'domains' => ['required', 'array', 'min:1'],
            'domains.*' => ['exists:domains,id'],
            'prompt_id' => ['required', 'exists:prompts,id'],
        ]);

        $domainIds = $request->domains;
        $promptId = $request->prompt_id;

        // Проверяем домены
        $domains = Domain::whereIn('id', $domainIds)
            ->where('buyer_id', auth()->id())
            ->where('status', 'dns_set')
            ->get();

        if ($domains->count() !== count($domainIds)) {
            return back()->with('error', 'Некоторые домены недоступны');
        }

        // Проверяем промпт
        $prompt = Prompt::active()->findOrFail($promptId);

        // Создаём проект
        $project = SiteProject::create([
            'buyer_id' => auth()->id(),
            'prompt_id' => $prompt->id,
            'status' => 'pending',
        ]);

        // Создаём деплои для каждого домена
        foreach ($domains as $domain) {
            SiteDeployment::create([
                'project_id' => $project->id,
                'domain_id' => $domain->id,
                'status' => 'pending',
            ]);
        }

        // Запускаем Job
        GenerateSiteJob::dispatch($project);

        return redirect()->route('buyer.sites.show', $project)
            ->with('success', 'Генерация сайта запущена');
    }

    /**
     * Просмотр проекта и прогресса
     */
    public function show(SiteProject $project)
    {
        if ($project->buyer_id !== auth()->id()) {
            abort(403);
        }

        $project->load(['prompt', 'deployments.domain.server']);

        return view('buyer.sites.show', compact('project'));
    }

    /**
     * API: Статус проекта для AJAX
     */
    public function status(SiteProject $project)
    {
        if ($project->buyer_id !== auth()->id()) {
            abort(403);
        }

        $project->load(['deployments.domain']);

        $deployments = $project->deployments->map(function ($deployment) {
            return [
                'id' => $deployment->id,
                'domain' => $deployment->domain->domain_name,
                'status' => $deployment->status,
                'ssl_installed' => $deployment->ssl_installed,
                'error_message' => $deployment->error_message,
                'deployed_at' => $deployment->deployed_at?->format('d.m.Y H:i'),
            ];
        });

        return response()->json([
            'project_status' => $project->status,
            'generated_at' => $project->generated_at?->format('d.m.Y H:i'),
            'files_count' => $project->files_count,
            'deployments' => $deployments,
        ]);
    }

    /**
     * Удалить сайт с сервера
     */
    public function destroy(SiteProject $project, DeployService $deployService)
    {
        if ($project->buyer_id !== auth()->id()) {
            abort(403);
        }

        $project->load(['deployments.domain.server']);

        $errors = [];
        $success = [];

        foreach ($project->deployments as $deployment) {
            $domain = $deployment->domain;
            $server = $domain->server;

            if (!$server) {
                $errors[] = "{$domain->domain_name}: сервер не найден";
                continue;
            }

            $result = $deployService->removeSite($domain->domain_name, $server);

            if ($result['success']) {
                $success[] = $domain->domain_name;
                $deployment->update([
                    'status' => 'removed',
                    'error_message' => null,
                ]);
            } else {
                $errors[] = "{$domain->domain_name}: {$result['message']}";
            }
        }

        // Обновляем статус проекта
        $project->update(['status' => 'removed']);

        // Удаляем локальные файлы
        $storagePath = storage_path('app/private/' . $project->storage_path);
        if (is_dir($storagePath)) {
            $this->deleteDirectory($storagePath);
        }

        if (count($errors) > 0) {
            return redirect()->route('buyer.sites.index')
                ->with('warning', 'Удалено: ' . implode(', ', $success) . '. Ошибки: ' . implode('; ', $errors));
        }

        return redirect()->route('buyer.sites.index')
            ->with('success', 'Проект удалён с сервера');
    }

    /**
     * Удалить отдельный домен с сервера
     */
    public function destroyDeployment(SiteDeployment $deployment, DeployService $deployService)
    {
        $project = $deployment->project;

        if ($project->buyer_id !== auth()->id()) {
            abort(403);
        }

        $domain = $deployment->domain;
        $server = $domain->server;

        if (!$server) {
            return back()->with('error', "{$domain->domain_name}: сервер не найден");
        }

        $result = $deployService->removeSite($domain->domain_name, $server);

        if ($result['success']) {
            $deployment->update(['status' => 'removed']);
            return back()->with('success', "Сайт {$domain->domain_name} удалён с сервера");
        }

        return back()->with('error', "{$domain->domain_name}: {$result['message']}");
    }

    /**
     * Рекурсивное удаление директории
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
