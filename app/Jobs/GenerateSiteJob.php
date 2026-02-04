<?php

namespace App\Jobs;

use App\Models\SiteDeployment;
use App\Models\SiteProject;
use App\Services\ChatGptService;
use App\Services\DeployService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900; // 15 minutes for ChatGPT + deployment
    public int $tries = 3;

    /**
     * Exponential backoff between retries (in seconds)
     */
    public array $backoff = [60, 180, 300];

    public function __construct(
        protected SiteProject $project
    ) {}

    public function handle(ChatGptService $chatGptService): void
    {
        Log::info('GenerateSiteJob started', ['project_id' => $this->project->id]);

        try {
            // Step 1: Mark project as generating
            $this->project->update(['status' => 'generating']);

            // Step 2: Get prompt text
            $prompt = $this->project->prompt;
            if (!$prompt) {
                throw new \Exception('Промпт не найден');
            }

            // Step 3: Generate site code via ChatGPT
            Log::info('Generating site code via ChatGPT', ['project_id' => $this->project->id]);
            $result = $chatGptService->generateSiteCode($prompt->prompt_text);

            if (!$result['success']) {
                throw new \Exception('Генерация ChatGPT не удалась: ' . $result['message']);
            }

            $files = $result['files'];
            Log::info('Site code generated', [
                'project_id' => $this->project->id,
                'files_count' => count($files),
            ]);

            // Step 4: Save files to storage
            $storageResult = $chatGptService->saveProjectFiles($this->project->id, $files);

            // Step 5: Update project with file info
            $this->project->update([
                'status' => 'ready',
                'storage_path' => $storageResult['storage_path'],
                'files_count' => $storageResult['files_count'],
                'total_size' => $storageResult['total_size'],
                'generated_at' => now(),
            ]);

            Log::info('Project files saved', [
                'project_id' => $this->project->id,
                'storage_path' => $storageResult['storage_path'],
            ]);

            // Step 6: Deploy to each domain
            $this->deployToAllDomains();

        } catch (\Exception $e) {
            Log::error('GenerateSiteJob failed', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);

            $this->project->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Mark all pending deployments as failed
            SiteDeployment::where('project_id', $this->project->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'error_message' => 'Генерация проекта не удалась: ' . $e->getMessage(),
                ]);
        }
    }

    /**
     * Deploy generated site to all domains
     */
    protected function deployToAllDomains(): void
    {
        $deployments = SiteDeployment::where('project_id', $this->project->id)
            ->where('status', 'pending')
            ->with(['domain.server'])
            ->get();

        foreach ($deployments as $deployment) {
            try {
                $this->deployToDomain($deployment);
            } catch (\Exception $e) {
                Log::error('Deployment failed', [
                    'deployment_id' => $deployment->id,
                    'domain' => $deployment->domain->domain_name,
                    'error' => $e->getMessage(),
                ]);

                $deployment->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Deploy site to a single domain
     */
    protected function deployToDomain(SiteDeployment $deployment): void
    {
        $domain = $deployment->domain;
        $server = $domain->server;

        if (!$server) {
            throw new \Exception('Сервер не привязан к домену');
        }

        Log::info('Deploying to domain', [
            'deployment_id' => $deployment->id,
            'domain' => $domain->domain_name,
            'server' => $server->ip_address,
        ]);

        // Mark as deploying
        $deployment->update(['status' => 'deploying']);

        // Deploy via DeployService
        $deployService = app(DeployService::class);
        $result = $deployService->deployProject($this->project, $deployment);

        if ($result['success']) {
            $deployment->update([
                'status' => 'completed',
                'ssl_installed' => $result['ssl_installed'] ?? false,
                'deployed_at' => now(),
            ]);

            Log::info('Deployment completed', [
                'deployment_id' => $deployment->id,
                'domain' => $domain->domain_name,
            ]);
        } else {
            throw new \Exception($result['message'] ?? 'Деплой не удался');
        }
    }
}
