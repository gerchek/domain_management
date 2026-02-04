<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDomainPurchase;
use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\DomainBatch;
use App\Models\Server;
use Illuminate\Http\Request;

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
}
