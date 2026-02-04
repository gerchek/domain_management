@extends('layouts.app')

@section('title', 'Создание сайта - Шаг 2')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Создание сайта</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('buyer.sites.index') }}">Мои сайты</a></li>
                <li class="breadcrumb-item active">Шаг 2: Выбор промпта</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Progress Steps -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between">
            <div class="text-center flex-fill">
                <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-check"></i>
                </div>
                <div class="small mt-1 text-success">Выбор доменов</div>
            </div>
            <div class="text-center flex-fill">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    2
                </div>
                <div class="small mt-1 fw-semibold text-primary">Выбор промпта</div>
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

<!-- Selected Domains Summary -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-globe me-1"></i> Выбранные домены ({{ $domains->count() }})
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            @foreach($domains as $domain)
                <span class="badge bg-primary-subtle text-primary">{{ $domain->domain_name }}</span>
            @endforeach
        </div>
    </div>
</div>

@if($prompts->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-file-text fs-1 text-muted d-block mb-3"></i>
            <h5 class="text-muted">Нет доступных промптов</h5>
            <p class="text-muted mb-3">
                Администратор ещё не добавил промпты для генерации сайтов.<br>
                Пожалуйста, обратитесь к администратору.
            </p>
            <a href="{{ route('buyer.sites.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>
@else
    <form action="{{ route('buyer.sites.store') }}" method="POST" id="promptForm">
        @csrf
        @foreach($domains as $domain)
            <input type="hidden" name="domains[]" value="{{ $domain->id }}">
        @endforeach

        <div class="card">
            <div class="card-header">
                Выберите промпт для генерации сайта
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($prompts as $prompt)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 prompt-card" data-prompt-id="{{ $prompt->id }}">
                                <div class="card-body">
                                    <div class="d-flex align-items-start">
                                        <div class="form-check">
                                            <input type="radio" class="form-check-input" name="prompt_id"
                                                   id="prompt_{{ $prompt->id }}" value="{{ $prompt->id }}" required>
                                        </div>
                                        <label class="flex-grow-1 cursor-pointer" for="prompt_{{ $prompt->id }}">
                                            <h6 class="mb-1">{{ $prompt->name }}</h6>
                                            <span class="badge bg-secondary-subtle text-secondary mb-2">
                                                {{ strtoupper($prompt->language) }}
                                            </span>
                                            @if($prompt->description)
                                                <p class="text-muted small mb-0">{{ Str::limit($prompt->description, 100) }}</p>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('buyer.sites.create') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Назад
                </a>
                <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                    <i class="bi bi-rocket me-1"></i> Запустить генерацию
                </button>
            </div>
        </div>
    </form>
@endif

@if(!$prompts->isEmpty())
@push('styles')
<style>
.prompt-card {
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.prompt-card:hover {
    border-color: var(--bs-primary);
}
.prompt-card.selected {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), 0.25);
}
.cursor-pointer {
    cursor: pointer;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.prompt-card');
    const submitBtn = document.getElementById('submitBtn');

    cards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;

            cards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            submitBtn.disabled = false;
        });
    });

    document.querySelectorAll('input[name="prompt_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            cards.forEach(c => c.classList.remove('selected'));
            this.closest('.prompt-card').classList.add('selected');
            submitBtn.disabled = false;
        });
    });
});
</script>
@endpush
@endif
@endsection
