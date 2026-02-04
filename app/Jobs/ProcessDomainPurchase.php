<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Models\DomainBatch;
use App\Models\Setting;
use App\Services\DynadotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDomainPurchase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        protected DomainBatch $batch
    ) {}

    public function handle(DynadotService $dynadotService): void
    {
        $this->batch->markAsProcessing();

        $server = $this->batch->server;
        $domainsPerRequest = Setting::getDomainsPerRequest();
        $targetCount = $this->batch->target_count;

        // Обрабатываем домены пока есть в очереди и цель не достигнута
        while (true) {
            $this->batch->refresh();

            // Проверяем достигнута ли цель
            if ($targetCount !== null && $this->batch->successful_domains >= $targetCount) {
                Log::info('Target reached, stopping processing', [
                    'batch_id' => $this->batch->id,
                    'target' => $targetCount,
                    'successful' => $this->batch->successful_domains,
                ]);
                break;
            }

            // Проверяем есть ли ещё домены
            if (!$this->batch->hasPendingDomains()) {
                Log::info('No more pending domains', ['batch_id' => $this->batch->id]);
                break;
            }

            // Вычисляем размер порции для запроса
            $remaining = $targetCount !== null
                ? $targetCount - $this->batch->successful_domains
                : $domainsPerRequest;

            $batchSize = $targetCount !== null
                ? min($domainsPerRequest, max($remaining, $remaining * 2))
                : $domainsPerRequest;

            // Получаем следующую порцию доменов из JSON
            $domainNames = $this->batch->getNextDomains($batchSize);

            if (empty($domainNames)) {
                break;
            }

            $results = $dynadotService->bulkRegister($domainNames);

            $successfulDomains = [];
            $processedDomainNames = [];

            foreach ($results as $result) {
                $domainName = strtolower($result['domain']);
                $processedDomainNames[] = $domainName;

                $this->batch->incrementProcessed();

                if ($result['success']) {
                    // Создаём запись Domain только при успешной покупке
                    $domain = Domain::create([
                        'buyer_id' => $this->batch->buyer_id,
                        'server_id' => $this->batch->server_id,
                        'batch_id' => $this->batch->id,
                        'domain_name' => $domainName,
                        'status' => 'purchased',
                        'purchased_at' => now(),
                    ]);

                    $this->batch->incrementSuccessful();
                    $successfulDomains[] = $domain;

                    // Проверяем цель после каждой успешной покупки
                    $this->batch->refresh();
                    if ($targetCount !== null && $this->batch->successful_domains >= $targetCount) {
                        Log::info('Target reached during processing', [
                            'batch_id' => $this->batch->id,
                            'target' => $targetCount,
                            'successful' => $this->batch->successful_domains,
                        ]);
                        break;
                    }
                } else {
                    $this->batch->incrementFailed();
                    Log::info('Domain purchase failed', [
                        'domain' => $domainName,
                        'error' => $result['message'],
                    ]);
                }
            }

            // Удаляем обработанные домены из очереди
            $this->batch->removeProcessedDomains($processedDomainNames);

            // Устанавливаем DNS для успешных доменов
            if (!empty($successfulDomains)) {
                // Ждём 30 секунд чтобы Dynadot успел обработать покупку
                Log::info('Waiting 30 seconds before setting DNS...', ['batch_id' => $this->batch->id]);
                sleep(30);

                $this->setDnsForDomains($dynadotService, $successfulDomains, $server->ip_address);
            }

            sleep(1);
        }

        // Очищаем оставшиеся домены из очереди
        $this->batch->update(['pending_domains' => []]);

        $this->batch->markAsCompleted();

        // Обновляем счётчик доменов на сервере
        $server->update([
            'current_domains_count' => $server->domains()->whereIn('status', ['purchased', 'dns_set'])->count(),
        ]);

        Log::info('Domain batch processing completed', [
            'batch_id' => $this->batch->id,
            'total' => $this->batch->total_domains,
            'target' => $this->batch->target_count,
            'successful' => $this->batch->successful_domains,
            'failed' => $this->batch->failed_domains,
        ]);
    }

    protected function setDnsForDomains(DynadotService $dynadotService, array $domains, string $ipAddress): void
    {
        $domainsPerRequest = Setting::getDomainsPerRequest();
        $domainChunks = array_chunk($domains, $domainsPerRequest);

        foreach ($domainChunks as $chunk) {
            $domainNames = array_map(fn($d) => $d->domain_name, $chunk);

            $result = $dynadotService->setDns($domainNames, $ipAddress);

            if ($result['success']) {
                foreach ($chunk as $domain) {
                    $domain->markAsDnsSet();
                }
            } else {
                Log::warning('Failed to set DNS for domains', [
                    'domains' => $domainNames,
                    'error' => $result['message'],
                ]);
            }

            sleep(1);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Domain batch processing failed', [
            'batch_id' => $this->batch->id,
            'error' => $exception->getMessage(),
        ]);

        $this->batch->markAsFailed();
    }
}
