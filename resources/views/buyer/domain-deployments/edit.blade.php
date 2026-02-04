@extends('layouts.app')

@section('title', 'Редактирование настроек деплоя')

@section('content')
<div class="mb-4">
    <a href="{{ route('buyer.domain-deployments.show', $deployment) }}" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Назад к деплою
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Редактирование настроек</h5>
                <small class="text-muted">Домен: {{ $deployment->domain->domain_name }}</small>
            </div>
            <div class="card-body">
                {{-- Palladium Info (Read-only) --}}
                @if($deployment->palladiumConfigRelation)
                    <div class="alert alert-secondary">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-shield-check me-2"></i>
                            <strong>Palladium конфиг</strong>
                            <span class="badge bg-secondary ms-2">Только чтение</span>
                        </div>
                        <div class="row small">
                            <div class="col-md-6">
                                <strong>Название:</strong> {{ $deployment->palladiumConfigRelation->name }}
                            </div>
                            <div class="col-md-6">
                                <strong>GEO:</strong> {{ $deployment->palladiumConfigRelation->geo ?? '-' }}
                            </div>
                            <div class="col-md-6 mt-1">
                                <strong>Client ID:</strong> <code>{{ $deployment->palladium_config['client_id'] ?? '-' }}</code>
                            </div>
                            <div class="col-md-6 mt-1">
                                <strong>Баннер:</strong> <span class="badge bg-info">{{ $deployment->palladium_config['banner_source'] ?? 'adwords' }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('buyer.domain-deployments.update', $deployment) }}" method="POST" id="editForm">
                    @csrf
                    @method('PUT')

                    <h6 class="fw-semibold mb-3">
                        <i class="bi bi-graph-up text-primary me-1"></i>
                        Настройки Keitaro <span class="text-danger">*</span>
                    </h6>
                    <p class="text-muted small mb-3">Вы можете изменить настройки трекера Keitaro</p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Keitaro Host (khost) <span class="text-danger">*</span></label>
                            <input type="url"
                                   name="khost"
                                   class="form-control @error('khost') is-invalid @enderror"
                                   placeholder="https://your-tracker.com/"
                                   value="{{ old('khost', $deployment->tracking_config['khost'] ?? '') }}"
                                   required>
                            @error('khost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">URL вашего трекера Keitaro</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Keitaro Token (kapitoken) <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="kapitoken"
                                   class="form-control @error('kapitoken') is-invalid @enderror"
                                   placeholder="your_campaign_token"
                                   value="{{ old('kapitoken', $deployment->tracking_config['kapitoken'] ?? '') }}"
                                   required>
                            @error('kapitoken')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Токен кампании из Keitaro</small>
                        </div>
                    </div>

                    <div class="alert alert-info small mt-3">
                        <i class="bi bi-info-circle me-1"></i>
                        После сохранения настройки Keitaro будут автоматически обновлены на сервере.
                        Настройки Palladium нельзя изменить после деплоя.
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('buyer.domain-deployments.show', $deployment) }}" class="btn btn-light">
                            Отмена
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="normal-state">
                                <i class="bi bi-check-lg me-1"></i> Сохранить
                            </span>
                            <span class="loading-state d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span> Сохраняем...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function() {
        submitBtn.querySelector('.normal-state').classList.add('d-none');
        submitBtn.querySelector('.loading-state').classList.remove('d-none');
        submitBtn.disabled = true;
    });
});
</script>
@endpush
@endsection
