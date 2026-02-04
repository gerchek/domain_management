@extends('layouts.app')

@section('title', 'Панель')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Панель</h4>
    <a href="{{ route('buyer.domains.create') }}" class="btn btn-primary">
        <i class="bi bi-plus"></i> Купить домены
    </a>
</div>

<!-- Статистика -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
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
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Успешных</div>
                    <div class="h4 mb-0">{{ $stats['successful_domains'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                    <i class="bi bi-clock"></i>
                </div>
                <div>
                    <div class="text-muted small">Ожидают</div>
                    <div class="h4 mb-0">{{ $stats['pending_domains'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Ошибок</div>
                    <div class="h4 mb-0">{{ $stats['failed_domains'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Недавние пакеты -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Недавние пакеты</h6>
                <a href="{{ route('buyer.domains.batches') }}" class="btn btn-sm btn-outline-primary">Смотреть все</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Сервер</th>
                                <th>Всего</th>
                                <th>Статус</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBatches as $batch)
                                <tr>
                                    <td>{{ $batch->server->name ?? 'Н/Д' }}</td>
                                    <td>{{ $batch->total_domains }}</td>
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
                                    <td>{{ $batch->created_at->format('d.m.Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">Нет пакетов</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Недавние домены -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Недавние домены</h6>
                <a href="{{ route('buyer.domains.index') }}" class="btn btn-sm btn-outline-primary">Смотреть все</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Домен</th>
                                <th>Сервер</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentDomains as $domain)
                                <tr>
                                    <td>{{ $domain->domain_name }}</td>
                                    <td>{{ $domain->server->name ?? 'Н/Д' }}</td>
                                    <td>
                                        @php
                                            $statusLabels = [
                                                'dns_set' => 'DNS настроен',
                                                'failed' => 'Ошибка',
                                                'purchased' => 'Куплен',
                                                'pending' => 'Ожидает',
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $domain->status === 'dns_set' ? 'success' : ($domain->status === 'failed' ? 'danger' : ($domain->status === 'purchased' ? 'info' : 'secondary')) }}">
                                            {{ $statusLabels[$domain->status] ?? ucfirst(str_replace('_', ' ', $domain->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">Нет доменов</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
