@extends('layouts.app')

@section('title', 'Мои домены')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Мои домены</h4>
    <a href="{{ route('buyer.domains.create') }}" class="btn btn-primary">
        <i class="bi bi-plus"></i> Купить домены
    </a>
</div>

<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('buyer.domains.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search"
                       placeholder="Поиск домена..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">Все статусы</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Ожидает</option>
                    <option value="purchased" {{ request('status') === 'purchased' ? 'selected' : '' }}>Куплен</option>
                    <option value="dns_set" {{ request('status') === 'dns_set' ? 'selected' : '' }}>DNS настроен</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Ошибка</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="server_id">
                    <option value="">Все серверы</option>
                    @foreach($servers as $server)
                        <option value="{{ $server->id }}" {{ request('server_id') == $server->id ? 'selected' : '' }}>
                            {{ $server->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Поиск
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Домен</th>
                        <th>Сервер</th>
                        <th>Статус</th>
                        <th>Куплен</th>
                        <th>DNS настроен</th>
                        <th>Ошибка</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($domains as $domain)
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
                            <td>{{ $domain->purchased_at?->format('d.m.Y H:i') ?? '-' }}</td>
                            <td>{{ $domain->dns_set_at?->format('d.m.Y H:i') ?? '-' }}</td>
                            <td>
                                @if($domain->error_message)
                                    <span class="text-danger" title="{{ $domain->error_message }}">
                                        {{ Str::limit($domain->error_message, 30) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
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
