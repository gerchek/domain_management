@extends('layouts.app')

@section('title', 'Домены без DNS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">Домены без DNS</h4>
        <small class="text-muted">Всего: {{ $totalPendingDns }} доменов ожидают установки DNS</small>
    </div>
    <a href="{{ route('buyer.domains.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Все домены
    </a>
</div>

@if($domains->count() > 0)
<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('buyer.domains.pending-dns') }}" method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search"
                       placeholder="Поиск домена..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="server_id">
                    <option value="">Все серверы</option>
                    @foreach($servers as $server)
                        <option value="{{ $server->id }}" {{ request('server_id') == $server->id ? 'selected' : '' }}>
                            {{ $server->name }} ({{ $server->ip_address }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Поиск
                </button>
            </div>
        </form>
    </div>
</div>

<form action="{{ route('buyer.domains.retry-dns') }}" method="POST" id="retryDnsForm">
    @csrf

    <!-- Панель действий -->
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted">Выбрано: <strong id="selectedCount">0</strong> доменов</span>
            </div>
            <div>
                <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="selectAll">
                    Выбрать все на странице
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="deselectAll">
                    Снять выбор
                </button>
                <button type="submit" class="btn btn-success" id="retryDnsBtn" disabled>
                    <i class="bi bi-arrow-repeat"></i> Установить DNS
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="checkAll">
                            </th>
                            <th>Домен</th>
                            <th>Сервер</th>
                            <th>IP адрес</th>
                            <th>Куплен</th>
                            <th>Ошибка</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($domains as $domain)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input domain-checkbox"
                                           name="domain_ids[]" value="{{ $domain->id }}">
                                </td>
                                <td>
                                    <strong>{{ $domain->domain_name }}</strong>
                                </td>
                                <td>{{ $domain->server->name ?? 'Н/Д' }}</td>
                                <td>
                                    @if($domain->server)
                                        <code>{{ $domain->server->ip_address }}</code>
                                    @else
                                        <span class="text-danger">Сервер не найден</span>
                                    @endif
                                </td>
                                <td>{{ $domain->purchased_at?->format('d.m.Y H:i') ?? '-' }}</td>
                                <td>
                                    @if($domain->error_message)
                                        <span class="text-danger" title="{{ $domain->error_message }}">
                                            {{ Str::limit($domain->error_message, 40) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<div class="mt-3">
    {{ $domains->withQueryString()->links() }}
</div>

@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
        <h5 class="mt-3">Все домены имеют DNS</h5>
        <p class="text-muted">Нет доменов, ожидающих установки DNS</p>
        <a href="{{ route('buyer.domains.index') }}" class="btn btn-primary">
            Перейти к списку доменов
        </a>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.domain-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const retryDnsBtn = document.getElementById('retryDnsBtn');
    const selectAllBtn = document.getElementById('selectAll');
    const deselectAllBtn = document.getElementById('deselectAll');
    const form = document.getElementById('retryDnsForm');

    function updateCount() {
        const checked = document.querySelectorAll('.domain-checkbox:checked').length;
        selectedCount.textContent = checked;
        retryDnsBtn.disabled = checked === 0;
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateCount();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = true);
            if (checkAll) checkAll.checked = true;
            updateCount();
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
            if (checkAll) checkAll.checked = false;
            updateCount();
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            const checked = document.querySelectorAll('.domain-checkbox:checked').length;
            if (checked === 0) {
                e.preventDefault();
                alert('Выберите хотя бы один домен');
                return false;
            }

            retryDnsBtn.disabled = true;
            retryDnsBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Установка DNS...';
        });
    }
});
</script>
@endpush
