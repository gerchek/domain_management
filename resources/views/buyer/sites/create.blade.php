@extends('layouts.app')

@section('title', 'Создание сайта - Шаг 1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Создание сайта</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('buyer.sites.index') }}">Мои сайты</a></li>
                <li class="breadcrumb-item active">Шаг 1: Выбор доменов</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Progress Steps -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between">
            <div class="text-center flex-fill">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    1
                </div>
                <div class="small mt-1 fw-semibold text-primary">Выбор доменов</div>
            </div>
            <div class="text-center flex-fill">
                <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    2
                </div>
                <div class="small mt-1 text-muted">Выбор промпта</div>
            </div>
            <div class="text-center flex-fill">
                <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    3
                </div>
                <div class="small mt-1 text-muted">Генерация</div>
            </div>
        </div>
    </div>
</div>

@if($domains->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-globe fs-1 text-muted d-block mb-3"></i>
            <h5 class="text-muted">Нет доступных доменов</h5>
            <p class="text-muted mb-3">
                Для создания сайта необходимы домены со статусом "DNS установлен".<br>
                Сначала купите домены и дождитесь настройки DNS.
            </p>
            <a href="{{ route('buyer.domains.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Купить домены
            </a>
        </div>
    </div>
@else
    <form action="{{ route('buyer.sites.select-prompt') }}" method="POST" id="domainsForm">
        @csrf

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Выберите домены для сайта</span>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">
                        Выбрать все
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">
                        Снять выбор
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">
                                    <input type="checkbox" class="form-check-input" id="checkAll">
                                </th>
                                <th>Домен</th>
                                <th>Сервер</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($domains as $domain)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input domain-checkbox"
                                               name="domains[]" value="{{ $domain->id }}">
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $domain->domain_name }}</span>
                                    </td>
                                    <td>
                                        @if($domain->server)
                                            <span class="badge bg-info-subtle text-info">
                                                {{ $domain->server->name }}
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ $domain->server->ip_address }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="bi bi-check-circle me-1"></i>DNS установлен
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted">Выбрано: <strong id="selectedCount">0</strong> из {{ $domains->count() }}</span>
                </div>
                <div>
                    <a href="{{ route('buyer.sites.index') }}" class="btn btn-outline-secondary me-2">
                        Отмена
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        Далее <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
@endif

@if(!$domains->isEmpty())
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.domain-checkbox');
    const checkAll = document.getElementById('checkAll');
    const selectAllBtn = document.getElementById('selectAll');
    const deselectAllBtn = document.getElementById('deselectAll');
    const selectedCount = document.getElementById('selectedCount');
    const submitBtn = document.getElementById('submitBtn');

    function updateCount() {
        const checked = document.querySelectorAll('.domain-checkbox:checked').length;
        selectedCount.textContent = checked;
        submitBtn.disabled = checked === 0;
        checkAll.checked = checked === checkboxes.length && checkboxes.length > 0;
        checkAll.indeterminate = checked > 0 && checked < checkboxes.length;
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));

    checkAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateCount();
    });

    selectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = true);
        updateCount();
    });

    deselectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = false);
        updateCount();
    });

    updateCount();
});
</script>
@endpush
@endif
@endsection
