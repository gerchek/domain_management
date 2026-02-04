@extends('layouts.app')

@section('title', 'Деплой Black сайта')

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
                <h5 class="mb-0"><i class="bi bi-layers me-2"></i>Деплой Black сайта</h5>
                <small class="text-muted">Домен: {{ $deployment->domain->domain_name }}</small>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Black сайт будет задеплоен в <code>/newspage/</code> и показываться реальным пользователям.
                </div>

                @if($palladiumConfigs->isEmpty())
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Нет Palladium конфигов.</strong><br>
                        Обратитесь к администратору для добавления конфигураций.
                    </div>
                @else
                    <form action="{{ route('buyer.domain-deployments.attach-black-site.store', $deployment) }}" method="POST" id="attachForm">
                        @csrf

                        {{-- Шаг 1: Palladium конфиг (ВСЕГДА обязателен) --}}
                        <h6 class="fw-semibold mb-3">
                            <i class="bi bi-shield-check text-primary me-1"></i>
                            Palladium конфиг <span class="text-danger">*</span>
                        </h6>
                        <p class="text-muted small mb-3">Palladium фильтрует ботов и показывает им White сайт</p>

                        @error('palladium_config_id')
                            <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
                        @enderror

                        <div class="row mb-4">
                            @foreach($palladiumConfigs as $config)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check config-card p-3 border rounded {{ old('palladium_config_id') == $config->id ? 'border-primary bg-primary-subtle' : '' }}">
                                        <input class="form-check-input" type="radio" name="palladium_config_id"
                                               value="{{ $config->id }}" id="palladium_{{ $config->id }}"
                                               {{ old('palladium_config_id') == $config->id ? 'checked' : '' }}
                                               required>
                                        <label class="form-check-label w-100 cursor-pointer" for="palladium_{{ $config->id }}">
                                            <strong>{{ $config->name }}</strong>
                                            @if($config->geo)
                                                <span class="badge bg-info-subtle text-info ms-1">{{ $config->geo }}</span>
                                            @endif
                                            <br>
                                            <small class="text-muted">ID: {{ $config->client_id }}</small>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr class="my-4">

                        {{-- Шаг 2: Тип трекинга --}}
                        <h6 class="fw-semibold mb-3">
                            <i class="bi bi-gear text-primary me-1"></i>
                            Тип трекинга <span class="text-danger">*</span>
                        </h6>

                        @error('tracking_type')
                            <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
                        @enderror

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card h-100 type-card {{ old('tracking_type', 'keitaro') === 'keitaro' ? 'border-primary' : '' }}" data-type="keitaro">
                                    <div class="card-body text-center py-4">
                                        <input type="radio" name="tracking_type" value="keitaro" class="d-none type-radio"
                                               {{ old('tracking_type', 'keitaro') === 'keitaro' ? 'checked' : '' }}>
                                        <i class="bi bi-graph-up fs-1 text-primary mb-2 d-block"></i>
                                        <h6 class="mb-1">Keitaro</h6>
                                        <small class="text-muted">Трекинг + редирект на оффер</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 type-card {{ old('tracking_type') === 'offer' ? 'border-success' : '' }} {{ $offers->isEmpty() ? 'opacity-50' : '' }}" data-type="offer">
                                    <div class="card-body text-center py-4">
                                        <input type="radio" name="tracking_type" value="offer" class="d-none type-radio"
                                               {{ old('tracking_type') === 'offer' ? 'checked' : '' }}
                                               {{ $offers->isEmpty() ? 'disabled' : '' }}>
                                        <i class="bi bi-box-seam fs-1 text-success mb-2 d-block"></i>
                                        <h6 class="mb-1">Оффер</h6>
                                        <small class="text-muted">Прямой лендинг из ZIP</small>
                                        @if($offers->isEmpty())
                                            <br><span class="badge bg-warning-subtle text-warning mt-2">Нет офферов</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Настройки Keitaro (только для типа keitaro) --}}
                        <div id="keitaro-section" class="{{ old('tracking_type') === 'offer' ? 'd-none' : '' }}">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-graph-up text-primary me-1"></i>
                                Настройки Keitaro <span class="text-danger">*</span>
                            </h6>

                            @error('khost')
                                <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
                            @enderror
                            @error('kapitoken')
                                <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
                            @enderror

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Keitaro Host (khost) <span class="text-danger">*</span></label>
                                    <input type="url" name="khost" class="form-control @error('khost') is-invalid @enderror"
                                           placeholder="https://your-tracker.com/"
                                           value="{{ old('khost', $deployment->tracking_config['khost'] ?? '') }}"
                                           id="khostInput">
                                    <small class="text-muted">URL вашего трекера Keitaro</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Keitaro Token (kapitoken) <span class="text-danger">*</span></label>
                                    <input type="text" name="kapitoken" class="form-control @error('kapitoken') is-invalid @enderror"
                                           placeholder="your_campaign_token"
                                           value="{{ old('kapitoken', $deployment->tracking_config['kapitoken'] ?? '') }}"
                                           id="kapitokenInput">
                                    <small class="text-muted">Токен кампании из Keitaro</small>
                                </div>
                            </div>
                        </div>

                        {{-- Выбор Оффера (только для типа offer) --}}
                        <div id="offer-section" class="{{ old('tracking_type') !== 'offer' ? 'd-none' : '' }}">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-box-seam text-success me-1"></i>
                                Оффер <span class="text-danger">*</span>
                            </h6>

                            @if($offers->isEmpty())
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Нет офферов. Обратитесь к админу.
                                </div>
                            @else
                                @error('offer_id')
                                    <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
                                @enderror

                                <div class="row mb-4">
                                    @foreach($offers as $offer)
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check config-card p-3 border rounded {{ old('offer_id') == $offer->id ? 'border-success bg-success-subtle' : '' }}">
                                                <input class="form-check-input" type="radio" name="offer_id"
                                                       value="{{ $offer->id }}" id="offer_{{ $offer->id }}"
                                                       {{ old('offer_id') == $offer->id ? 'checked' : '' }}>
                                                <label class="form-check-label w-100 cursor-pointer" for="offer_{{ $offer->id }}">
                                                    <strong>{{ $offer->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">ZIP archive</small>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('buyer.domain-deployments.show', $deployment) }}" class="btn btn-light me-2">Отмена</a>
                            <button type="submit" class="btn btn-dark" id="submitBtn">
                                <span class="normal-state">
                                    <i class="bi bi-plus-circle me-1"></i> Задеплоить Black сайт
                                </span>
                                <span class="loading-state d-none">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Деплоим...
                                </span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.type-card {
    cursor: pointer;
    transition: all 0.2s ease;
}
.type-card:hover {
    border-color: #6c757d !important;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
}
.type-card.border-primary {
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
}
.type-card.border-success {
    box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.25);
}
.config-card {
    cursor: pointer;
    transition: all 0.2s ease;
}
.config-card:hover {
    border-color: #6c757d !important;
}
.cursor-pointer {
    cursor: pointer;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeCards = document.querySelectorAll('.type-card');
    const keitaroSection = document.getElementById('keitaro-section');
    const offerSection = document.getElementById('offer-section');
    const form = document.getElementById('attachForm');
    const submitBtn = document.getElementById('submitBtn');
    const khostInput = document.getElementById('khostInput');
    const kapitokenInput = document.getElementById('kapitokenInput');

    // Type card selection
    typeCards.forEach(card => {
        card.addEventListener('click', function() {
            const type = this.dataset.type;
            const radio = this.querySelector('.type-radio');

            if (radio.disabled) return;

            radio.checked = true;

            // Update card styles
            typeCards.forEach(c => {
                c.classList.remove('border-primary', 'border-success');
            });
            this.classList.add(type === 'keitaro' ? 'border-primary' : 'border-success');

            // Show/hide sections and update required
            if (type === 'keitaro') {
                keitaroSection.classList.remove('d-none');
                offerSection.classList.add('d-none');
                khostInput.required = true;
                kapitokenInput.required = true;
            } else {
                keitaroSection.classList.add('d-none');
                offerSection.classList.remove('d-none');
                khostInput.required = false;
                kapitokenInput.required = false;
            }
        });
    });

    // Config card highlighting
    document.querySelectorAll('.config-card input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const name = this.name;
            document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
                r.closest('.config-card').classList.remove('border-primary', 'border-success', 'bg-primary-subtle', 'bg-success-subtle');
            });
            if (this.checked) {
                const isPalladium = name === 'palladium_config_id';
                this.closest('.config-card').classList.add(
                    isPalladium ? 'border-primary' : 'border-success',
                    isPalladium ? 'bg-primary-subtle' : 'bg-success-subtle'
                );
            }
        });
    });

    // Form submit spinner
    form?.addEventListener('submit', function() {
        submitBtn.querySelector('.normal-state').classList.add('d-none');
        submitBtn.querySelector('.loading-state').classList.remove('d-none');
        submitBtn.disabled = true;
    });

    // Initialize required based on current type
    const currentType = document.querySelector('.type-radio:checked');
    if (currentType) {
        if (currentType.value === 'keitaro') {
            khostInput.required = true;
            kapitokenInput.required = true;
        } else {
            khostInput.required = false;
            kapitokenInput.required = false;
        }
    }
});
</script>
@endpush
@endsection
