@extends('layouts.app')

@section('title', 'Детали пакета')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Пакет #{{ $batch->id }}</h4>
    <a href="{{ route('buyer.domains.batches') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Назад к пакетам
    </a>
</div>

<!-- Progress Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                @if($batch->target_count)
                <div class="d-flex justify-content-between mb-2">
                    <span>Цель: купить <strong>{{ $batch->target_count }}</strong> из {{ $batch->total_domains }} доменов</span>
                    <span id="target-progress-text" class="text-success fw-bold">
                        {{ $batch->successful_domains }}/{{ $batch->target_count }}
                    </span>
                </div>
                <div class="progress mb-3" style="height: 25px;">
                    @php
                        $targetPercent = $batch->target_count > 0
                            ? min(100, round(($batch->successful_domains / $batch->target_count) * 100, 2))
                            : 0;
                    @endphp
                    <div class="progress-bar bg-success progress-bar-striped {{ $batch->isProcessing() ? 'progress-bar-animated' : '' }}"
                         id="target-progress-bar"
                         style="width: {{ $targetPercent }}%">
                        <span id="target-percent">{{ $targetPercent }}% к цели</span>
                    </div>
                </div>
                @endif

                <div class="d-flex justify-content-between mb-2">
                    <span>Обработано</span>
                    <span id="progress-text">{{ $batch->processed_domains }}/{{ $batch->total_domains }}</span>
                </div>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped {{ $batch->isProcessing() ? 'progress-bar-animated' : '' }}"
                         id="progress-bar"
                         style="width: {{ $batch->getProgressPercentage() }}%">
                        <span id="progress-percent">{{ $batch->getProgressPercentage() }}%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                @if($batch->target_count && $batch->isTargetReached())
                    <span class="badge bg-success fs-6 mb-2 d-block">
                        <i class="bi bi-check-circle"></i> Цель достигнута!
                    </span>
                @endif
                <span class="badge bg-{{ $batch->status === 'completed' ? 'success' : ($batch->status === 'processing' ? 'primary' : ($batch->status === 'failed' ? 'danger' : 'secondary')) }} fs-6"
                      id="batch-status">
                    {{ $batch->status === 'completed' ? 'Завершён' : ($batch->status === 'processing' ? 'Обрабатывается' : ($batch->status === 'failed' ? 'Ошибка' : 'В ожидании')) }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="row g-4 mb-4">
    @if($batch->target_count)
    <div class="col-md-2">
        <div class="card text-center border-primary">
            <div class="card-body">
                <div class="h4 mb-0 text-primary" id="stat-target">{{ $batch->target_count }}</div>
                <div class="text-muted small">Цель</div>
            </div>
        </div>
    </div>
    @endif
    <div class="col-md-{{ $batch->target_count ? '2' : '3' }}">
        <div class="card text-center">
            <div class="card-body">
                <div class="h4 mb-0" id="stat-total">{{ $batch->total_domains }}</div>
                <div class="text-muted small">Всего в списке</div>
            </div>
        </div>
    </div>
    <div class="col-md-{{ $batch->target_count ? '2' : '3' }}">
        <div class="card text-center">
            <div class="card-body">
                <div class="h4 mb-0" id="stat-processed">{{ $batch->processed_domains }}</div>
                <div class="text-muted small">Обработано</div>
            </div>
        </div>
    </div>
    <div class="col-md-{{ $batch->target_count ? '2' : '3' }}">
        <div class="card text-center">
            <div class="card-body">
                <div class="h4 mb-0 text-success" id="stat-successful">{{ $batch->successful_domains }}</div>
                <div class="text-muted small">Куплено</div>
            </div>
        </div>
    </div>
    <div class="col-md-{{ $batch->target_count ? '2' : '3' }}">
        <div class="card text-center">
            <div class="card-body">
                <div class="h4 mb-0 text-danger" id="stat-failed">{{ $batch->failed_domains }}</div>
                <div class="text-muted small">Ошибок</div>
            </div>
        </div>
    </div>
    @if($batch->target_count)
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="h4 mb-0 text-muted" id="stat-skipped">{{ $batch->domains()->skipped()->count() }}</div>
                <div class="text-muted small">Пропущено</div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Batch Info -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Информация о пакете</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th>Источник:</th>
                        <td>{{ $batch->file_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Сервер:</th>
                        <td>{{ $batch->server->name }} ({{ $batch->server->ip_address }})</td>
                    </tr>
                    @if($batch->target_count)
                    <tr>
                        <th>Цель:</th>
                        <td>Купить {{ $batch->target_count }} доменов</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Создан:</th>
                        <td>{{ $batch->created_at->format('d.m.Y H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Domains List -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Домены</h6>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary active" data-filter="all">Все</button>
            <button type="button" class="btn btn-outline-success" data-filter="success">Куплены</button>
            <button type="button" class="btn btn-outline-danger" data-filter="failed">Ошибки</button>
            @if($batch->target_count)
            <button type="button" class="btn btn-outline-secondary" data-filter="skipped">Пропущены</button>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="domains-table">
                <thead>
                    <tr>
                        <th>Домен</th>
                        <th>Статус</th>
                        <th>Куплен</th>
                        <th>DNS</th>
                        <th>Сообщение</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batch->domains as $domain)
                        @php
                            $statusClass = match($domain->status) {
                                'dns_set' => 'success',
                                'purchased' => 'info',
                                'failed' => 'danger',
                                'skipped' => 'warning',
                                default => 'secondary'
                            };
                            $statusText = match($domain->status) {
                                'dns_set' => 'DNS установлен',
                                'purchased' => 'Куплен',
                                'failed' => 'Ошибка',
                                'skipped' => 'Пропущен',
                                default => 'В ожидании'
                            };
                            $filterClass = in_array($domain->status, ['dns_set', 'purchased']) ? 'success' : ($domain->status === 'failed' ? 'failed' : ($domain->status === 'skipped' ? 'skipped' : 'pending'));
                        @endphp
                        <tr data-status="{{ $filterClass }}">
                            <td><code>{{ $domain->domain_name }}</code></td>
                            <td>
                                <span class="badge bg-{{ $statusClass }}">
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td>{{ $domain->purchased_at?->format('H:i:s') ?? '-' }}</td>
                            <td>{{ $domain->dns_set_at?->format('H:i:s') ?? '-' }}</td>
                            <td class="text-{{ $domain->status === 'failed' ? 'danger' : 'muted' }} small">
                                {{ $domain->error_message ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Filter buttons
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            document.querySelectorAll('#domains-table tbody tr').forEach(row => {
                if (filter === 'all' || row.dataset.status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
</script>

@if($batch->isProcessing() || $batch->isPending())
<script>
    function updateStatus() {
        fetch('{{ route('buyer.domains.batch.status', $batch) }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('progress-text').textContent = data.processed + '/' + data.total;
                document.getElementById('progress-bar').style.width = data.progress + '%';
                document.getElementById('progress-percent').textContent = data.progress + '%';
                document.getElementById('stat-processed').textContent = data.processed;
                document.getElementById('stat-successful').textContent = data.successful;
                document.getElementById('stat-failed').textContent = data.failed;

                // Update target progress if exists
                if (data.target_count) {
                    const targetPercent = Math.min(100, Math.round((data.successful / data.target_count) * 100));
                    const targetProgressText = document.getElementById('target-progress-text');
                    const targetProgressBar = document.getElementById('target-progress-bar');
                    const targetPercentEl = document.getElementById('target-percent');

                    if (targetProgressText) targetProgressText.textContent = data.successful + '/' + data.target_count;
                    if (targetProgressBar) targetProgressBar.style.width = targetPercent + '%';
                    if (targetPercentEl) targetPercentEl.textContent = targetPercent + '% к цели';
                }

                const statusBadge = document.getElementById('batch-status');
                const statusTexts = {
                    'completed': 'Завершён',
                    'processing': 'Обрабатывается',
                    'failed': 'Ошибка',
                    'pending': 'В ожидании'
                };
                statusBadge.textContent = statusTexts[data.status] || data.status;

                if (data.status === 'completed') {
                    statusBadge.className = 'badge bg-success fs-6';
                    document.getElementById('progress-bar').classList.remove('progress-bar-animated');
                    const targetBar = document.getElementById('target-progress-bar');
                    if (targetBar) targetBar.classList.remove('progress-bar-animated');
                    location.reload();
                } else if (data.status === 'failed') {
                    statusBadge.className = 'badge bg-danger fs-6';
                    document.getElementById('progress-bar').classList.remove('progress-bar-animated');
                } else {
                    setTimeout(updateStatus, 2000);
                }
            });
    }

    updateStatus();
</script>
@endif
@endpush
