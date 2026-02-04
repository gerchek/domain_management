<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDomainPurchase;
use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\DomainBatch;
use App\Models\Server;
use App\Models\Setting;
use App\Services\DynadotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = $user->domains()->with('server');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        if ($request->filled('search')) {
            $query->where('domain_name', 'like', '%' . $request->search . '%');
        }

        $domains = $query->latest()->paginate(50);
        $servers = Server::active()->get();

        return view('buyer.domains.index', compact('domains', 'servers'));
    }

    public function create()
    {
        $servers = Server::active()
            ->withAvailableSlots()
            ->get();

        return view('buyer.domains.create', compact('servers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'domains_file' => ['nullable', 'file', 'mimes:txt', 'max:2048'],
            'domains_text' => ['nullable', 'string'],
            'server_id' => ['required', 'exists:servers,id'],
            'target_count' => ['nullable', 'integer', 'min:1'],
        ]);

        // Должен быть либо файл, либо текст
        if (!$request->hasFile('domains_file') && empty($request->domains_text)) {
            return back()->with('error', 'Укажите файл или вставьте список доменов');
        }

        $server = Server::findOrFail($request->server_id);

        if (!$server->is_active) {
            return back()->with('error', 'Выбранный сервер неактивен');
        }

        // Получаем контент из файла или текстового поля
        if ($request->hasFile('domains_file')) {
            $file = $request->file('domains_file');
            $content = file_get_contents($file->getRealPath());
            $fileName = $file->getClientOriginalName();
        } else {
            $content = $request->domains_text;
            $fileName = 'text_input_' . date('Y-m-d_H-i-s');
        }

        // Парсим домены из контента
        $domains = $this->parseDomainsFromContent($content);

        if (empty($domains)) {
            return back()->with('error', 'Не найдено валидных доменов');
        }

        // target_count - сколько успешных покупок нужно
        $targetCount = $request->target_count;

        // Проверяем доступные слоты на сервере
        $slotsNeeded = $targetCount ?? count($domains);
        if ($slotsNeeded > $server->availableSlots()) {
            return back()->with('error', 'На сервере недостаточно слотов. Доступно: ' . $server->availableSlots());
        }

        $user = auth()->user();

        $batch = DomainBatch::create([
            'buyer_id' => $user->id,
            'server_id' => $server->id,
            'file_name' => $fileName,
            'pending_domains' => $domains,
            'total_domains' => count($domains),
            'target_count' => $targetCount,
            'status' => 'pending',
        ]);

        $logMessage = "Загружено " . count($domains) . " доменов";
        if ($targetCount) {
            $logMessage .= " (цель: купить $targetCount)";
        }
        ActivityLog::log('upload_domains', $logMessage);

        ProcessDomainPurchase::dispatch($batch);

        return redirect()->route('buyer.domains.batch', $batch)
            ->with('success', 'Домены загружены. Обработка началась.');
    }

    /**
     * Парсит домены из текста (поддерживает JSON массив, построчный список, разделенные запятыми)
     */
    private function parseDomainsFromContent(string $content): array
    {
        $domains = [];
        $content = trim($content);

        // Пробуем распарсить как JSON
        if (str_starts_with($content, '[')) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (is_string($item)) {
                        $domains[] = $item;
                    }
                }
            }
        }

        // Если JSON не сработал, парсим построчно или через запятую
        if (empty($domains)) {
            // Заменяем запятые и точки с запятой на переводы строк
            $content = preg_replace('/[,;]+/', "\n", $content);
            $lines = array_filter(array_map('trim', explode("\n", $content)));

            foreach ($lines as $line) {
                // Удаляем кавычки если есть
                $line = trim($line, "\"' \t\n\r\0\x0B");
                if (!empty($line)) {
                    $domains[] = $line;
                }
            }
        }

        // Валидируем и нормализуем домены
        $validDomains = [];
        foreach ($domains as $domain) {
            $domain = strtolower(trim($domain));
            if (!empty($domain) && preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z]{2,})+$/', $domain)) {
                $validDomains[] = $domain;
            }
        }

        return array_unique($validDomains);
    }

    public function batch(DomainBatch $batch)
    {
        if ($batch->buyer_id !== auth()->id()) {
            abort(403);
        }

        $batch->load(['server', 'domains']);

        return view('buyer.domains.batch', compact('batch'));
    }

    public function batchStatus(DomainBatch $batch)
    {
        if ($batch->buyer_id !== auth()->id()) {
            abort(403);
        }

        return response()->json([
            'status' => $batch->status,
            'total' => $batch->total_domains,
            'target_count' => $batch->target_count,
            'processed' => $batch->processed_domains,
            'successful' => $batch->successful_domains,
            'failed' => $batch->failed_domains,
            'progress' => $batch->getProgressPercentage(),
            'target_reached' => $batch->isTargetReached(),
            'remaining_target' => $batch->getRemainingTarget(),
        ]);
    }

    public function batches()
    {
        $batches = auth()->user()
            ->domainBatches()
            ->with('server')
            ->latest()
            ->paginate(20);

        return view('buyer.domains.batches', compact('batches'));
    }

    /**
     * Показать домены без установленного DNS (статус purchased)
     */
    public function pendingDns(Request $request)
    {
        $user = auth()->user();

        $query = $user->domains()
            ->with('server')
            ->where('status', 'purchased');

        if ($request->filled('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        if ($request->filled('search')) {
            $query->where('domain_name', 'like', '%' . $request->search . '%');
        }

        $domains = $query->latest()->paginate(50);
        $servers = Server::active()->get();

        // Считаем общее количество доменов без DNS
        $totalPendingDns = $user->domains()->where('status', 'purchased')->count();

        return view('buyer.domains.pending-dns', compact('domains', 'servers', 'totalPendingDns'));
    }

    /**
     * Повторно установить DNS для выбранных доменов
     */
    public function retryDns(Request $request)
    {
        $request->validate([
            'domain_ids' => ['required', 'array', 'min:1'],
            'domain_ids.*' => ['required', 'integer', 'exists:domains,id'],
        ]);

        $user = auth()->user();
        $domainIds = $request->domain_ids;

        // Получаем домены пользователя со статусом purchased
        $domains = Domain::whereIn('id', $domainIds)
            ->where('buyer_id', $user->id)
            ->where('status', 'purchased')
            ->with('server')
            ->get();

        if ($domains->isEmpty()) {
            return back()->with('error', 'Не найдено доменов для установки DNS');
        }

        // Группируем домены по серверам
        $domainsByServer = $domains->groupBy('server_id');

        $apiKey = Setting::get('dynadot_api_key');
        if (!$apiKey) {
            return back()->with('error', 'API ключ Dynadot не настроен');
        }

        $dynadotService = new DynadotService($apiKey);
        $domainsPerRequest = (int) Setting::get('domains_per_request', 50);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($domainsByServer as $serverId => $serverDomains) {
            $server = $serverDomains->first()->server;

            if (!$server) {
                foreach ($serverDomains as $domain) {
                    $domain->update([
                        'error_message' => 'Сервер не найден',
                    ]);
                    $failedCount++;
                }
                continue;
            }

            // Разбиваем на чанки
            $chunks = $serverDomains->chunk($domainsPerRequest);

            foreach ($chunks as $chunk) {
                $domainNames = $chunk->pluck('domain_name')->toArray();

                Log::info('Retrying DNS setup', [
                    'domains' => $domainNames,
                    'server_ip' => $server->ip_address,
                ]);

                $result = $dynadotService->setDns($domainNames, $server->ip_address);

                if ($result['success']) {
                    foreach ($chunk as $domain) {
                        $domain->update([
                            'status' => 'dns_set',
                            'dns_set_at' => now(),
                            'error_message' => null,
                        ]);
                        $successCount++;
                    }

                    ActivityLog::log('dns_retry_success', "DNS установлен для " . count($domainNames) . " доменов");
                } else {
                    foreach ($chunk as $domain) {
                        $domain->update([
                            'error_message' => $result['message'],
                        ]);
                        $failedCount++;
                    }
                    $errors[] = $result['message'];

                    Log::warning('DNS retry failed', [
                        'domains' => $domainNames,
                        'error' => $result['message'],
                    ]);
                }

                // Пауза между запросами к API
                if ($chunks->count() > 1) {
                    sleep(2);
                }
            }
        }

        if ($successCount > 0 && $failedCount > 0) {
            return back()->with('warning', "DNS установлен для {$successCount} доменов. Ошибки: {$failedCount}");
        } elseif ($successCount > 0) {
            return back()->with('success', "DNS успешно установлен для {$successCount} доменов");
        } else {
            $errorMsg = implode('; ', array_unique($errors));
            return back()->with('error', "Не удалось установить DNS. {$errorMsg}");
        }
    }
}
