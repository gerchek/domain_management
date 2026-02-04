@extends('layouts.app')

@section('title', 'Панель администратора')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Панель</h4>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="text-muted small">Всего байеров</div>
                    <div class="h4 mb-0">{{ $stats['total_buyers'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="bi bi-hdd-rack"></i>
                </div>
                <div>
                    <div class="text-muted small">Активных серверов</div>
                    <div class="h4 mb-0">{{ $stats['active_servers'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                    <i class="bi bi-globe"></i>
                </div>
                <div>
                    <div class="text-muted small">Всего доменов</div>
                    <div class="h4 mb-0">{{ $stats['total_domains'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Успешных</div>
                    <div class="h4 mb-0">{{ $stats['successful_domains'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Server Usage -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Использование серверов</h6>
            </div>
            <div class="card-body">
                @forelse($serverUsage as $server)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ $server->name }}</span>
                            <span class="text-muted">{{ $server->current_domains_count }}/{{ $server->max_domains }}</span>
                        </div>
                        @php
                            $percentage = $server->max_domains > 0 ? ($server->current_domains_count / $server->max_domains) * 100 : 0;
                        @endphp
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar {{ $percentage > 80 ? 'bg-danger' : ($percentage > 50 ? 'bg-warning' : 'bg-success') }}"
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Нет активных серверов</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Top Buyers -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Топ байеров</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Байер</th>
                            <th class="text-end">Доменов</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topBuyers as $buyer)
                            <tr>
                                <td>{{ $buyer->name }}</td>
                                <td class="text-end">{{ $buyer->domains_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-muted">Нет байеров</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Recent Batches -->
<div class="card mt-4">
    <div class="card-header bg-white">
        <h6 class="mb-0">Недавние пакеты</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Байер</th>
                        <th>Сервер</th>
                        <th>Всего</th>
                        <th>Успешно</th>
                        <th>Ошибки</th>
                        <th>Статус</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBatches as $batch)
                        <tr>
                            <td>{{ $batch->buyer->name ?? 'Н/Д' }}</td>
                            <td>{{ $batch->server->name ?? 'Н/Д' }}</td>
                            <td>{{ $batch->total_domains }}</td>
                            <td class="text-success">{{ $batch->successful_domains }}</td>
                            <td class="text-danger">{{ $batch->failed_domains }}</td>
                            <td>
                                @php
                                    $statusLabels = [
                                        'completed' => 'Завершён',
                                        'processing' => 'В процессе',
                                        'failed' => 'Ошибка',
                                        'pending' => 'Ожидает',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $batch->status === 'completed' ? 'success' : ($batch->status === 'processing' ? 'primary' : ($batch->status === 'failed' ? 'danger' : 'secondary')) }}">
                                    {{ $statusLabels[$batch->status] ?? ucfirst($batch->status) }}
                                </span>
                            </td>
                            <td>{{ $batch->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted text-center">Нет пакетов</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
