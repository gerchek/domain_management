@extends('layouts.app')

@section('title', 'Отчёты')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Отчёты</h4>
    <a href="{{ route('admin.reports.export', request()->query()) }}" class="btn btn-success">
        <i class="bi bi-download"></i> Экспорт CSV
    </a>
</div>

<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('admin.reports.index') }}" method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Дата с</label>
                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Дата по</label>
                <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Байер</label>
                <select class="form-select" name="buyer_id">
                    <option value="">Все байеры</option>
                    @foreach($buyers as $buyer)
                        <option value="{{ $buyer->id }}" {{ $buyerId == $buyer->id ? 'selected' : '' }}>
                            {{ $buyer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Сервер</label>
                <select class="form-select" name="server_id">
                    <option value="">Все серверы</option>
                    @foreach($servers as $server)
                        <option value="{{ $server->id }}" {{ $serverId == $server->id ? 'selected' : '' }}>
                            {{ $server->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Фильтр
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Статистика -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="h3 mb-0">{{ $stats['total'] }}</div>
                <div class="text-muted small">Всего доменов</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="h3 mb-0 text-success">{{ $stats['successful'] }}</div>
                <div class="text-muted small">Успешных</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="h3 mb-0 text-danger">{{ $stats['failed'] }}</div>
                <div class="text-muted small">Ошибок</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="h3 mb-0 text-warning">{{ $stats['pending'] }}</div>
                <div class="text-muted small">Ожидают</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- По байерам -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Домены по байерам</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Байер</th>
                            <th class="text-end">Кол-во</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buyerStats as $stat)
                            <tr>
                                <td>{{ $stat->buyer->name ?? 'Н/Д' }}</td>
                                <td class="text-end">{{ $stat->total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">Нет данных</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- По серверам -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Домены по серверам</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Сервер</th>
                            <th class="text-end">Кол-во</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serverStats as $stat)
                            <tr>
                                <td>{{ $stat->server->name ?? 'Н/Д' }}</td>
                                <td class="text-end">{{ $stat->total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">Нет данных</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Таблица доменов -->
<div class="card">
    <div class="card-header bg-white">
        <h6 class="mb-0">Домены</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Домен</th>
                        <th>Байер</th>
                        <th>Сервер</th>
                        <th>Статус</th>
                        <th>Куплен</th>
                        <th>DNS настроен</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($domains as $domain)
                        <tr>
                            <td>{{ $domain->domain_name }}</td>
                            <td>{{ $domain->buyer->name ?? 'Н/Д' }}</td>
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
                            <td>{{ $domain->purchased_at?->format('d.m.Y H:i') ?? '-' }}</td>
                            <td>{{ $domain->dns_set_at?->format('d.m.Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted text-center py-4">Домены не найдены</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $domains->withQueryString()->links() }}
</div>
@endsection
