@extends('layouts.app')

@section('title', 'Деплой Black сайта')

@section('content')
<div class="mb-4">
    <a href="{{ route('buyer.domain-deployments.index') }}" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Назад к деплоям
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-layers me-2"></i>Деплой Black сайта</h5>
            </div>
            <div class="card-body">
                @if($domains->isEmpty())
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Нет доступных доменов.</strong><br>
                        Сначала задеплойте White сайт на домен.
                        <a href="{{ route('buyer.sites.index') }}" class="alert-link">Перейти к сайтам</a>
                    </div>
                @elseif($palladiumConfigs->isEmpty())
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Нет Palladium конфигов.</strong><br>
                        Обратитесь к администратору для добавления конфигураций.
                    </div>
                @else
                    <form action="{{ route('buyer.domain-deployments.store') }}" method="POST" id="deployForm">
                        @csrf

                        {{-- Шаг 1: Выбор доменов --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-primary me-1">1</span>
                                Выберите домены <span class="text-danger">*</span>
                            </label>
                            <p class="text-muted small mb-2">Выберите один или несколько доменов с задеплоенным White сайтом</p>

                            @error('domain_ids')
                                <div class="alert alert-danger py-2">{{ $message }}</div>
                            @enderror

                            {{-- Выбранные домены (теги) --}}
                            <div id="selectedDomainsTags" class="selected-tags mb-2" style="display: none;">
                                {{-- Теги выбранных доменов добавляются через JS --}}
                            </div>

                            {{-- Поиск и выбор --}}
                            <div class="domain-picker">
                                <div class="domain-picker-input position-relative">
                                    <input type="text"
                                           id="domainSearch"
                                           class="form-control"
                                           placeholder="Поиск доменов... ({{ $domains->count() }} доступно)"
                                           autocomplete="off">
                                    <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                                </div>

                                {{-- Dropdown список --}}
                                <div id="domainDropdown" class="domain-dropdown border rounded mt-1" style="display: none;">
                                    <div class="dropdown-actions px-2 py-1 border-bottom bg-light d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="selectAllDomains">
                                            <i class="bi bi-check-all"></i> Все
                                        </button>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="selectFilteredDomains">
                                            <i class="bi bi-funnel"></i> Найденные
                                        </button>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger" id="deselectAllDomains">
                                            <i class="bi bi-x-lg"></i> Сбросить
                                        </button>
                                    </div>
                                    <div class="domain-list" style="max-height: 200px; overflow-y: auto;">
                                        @foreach($domains as $domain)
                                            @php
                                                $whiteSite = $domain->siteDeployments->first();
                                                $projectName = $whiteSite?->project?->prompt?->name ?? 'Неизвестно';
                                            @endphp
                                            <label class="domain-option d-flex align-items-center px-2 py-1 cursor-pointer"
                                                   data-domain="{{ strtolower($domain->domain_name) }}"
                                                   data-server="{{ $domain->server?->ip_address ?? '' }}">
                                                <input type="checkbox"
                                                       class="form-check-input me-2 domain-checkbox"
                                                       name="domain_ids[]"
                                                       value="{{ $domain->id }}"
                                                       data-name="{{ $domain->domain_name }}"
                                                       {{ in_array($domain->id, old('domain_ids', [])) ? 'checked' : '' }}>
                                                <span class="flex-grow-1 text-truncate">{{ $domain->domain_name }}</span>
                                                @if($domain->server)
                                                    <small class="text-muted ms-2">{{ $domain->server->ip_address }}</small>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                    <div id="noDomainsFound" class="text-center text-muted py-3" style="display: none;">
                                        Домены не найдены
                                    </div>
                                </div>
                            </div>

                            {{-- Счётчик --}}
                            <div class="mt-2 d-flex align-items-center justify-content-between">
                                <span class="text-muted small">
                                    <i class="bi bi-check2-square me-1"></i>
                                    Выбрано: <strong id="selectedCount">0</strong> из {{ $domains->count() }}
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="toggleDomainList">
                                    <i class="bi bi-list me-1"></i> Показать список
                                </button>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Шаг 2: Palladium конфиг (ВСЕГДА обязателен) --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-primary me-1">2</span>
                                Palladium конфиг <span class="text-danger">*</span>
                            </label>
                            <p class="text-muted small mb-3">Palladium фильтрует ботов и показывает им White сайт</p>

                            @error('palladium_config_id')
                                <div class="alert alert-danger py-2">{{ $message }}</div>
                            @enderror

                            <select name="palladium_config_id"
                                    class="form-select form-select-lg @error('palladium_config_id') is-invalid @enderror"
                                    id="palladiumSelect"
                                    required>
                                <option value="">-- Выберите Palladium конфиг --</option>
                                @foreach($palladiumConfigs as $config)
                                    <option value="{{ $config->id }}"
                                            {{ old('palladium_config_id') == $config->id ? 'selected' : '' }}
                                            data-client-id="{{ $config->client_id }}"
                                            data-banner="{{ $config->banner_source }}">
                                        {{ $config->display_name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Превью Palladium конфига --}}
                            <div id="palladiumPreview" class="mt-3 d-none">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <small class="text-muted">
                                            Client ID: <code id="previewClientId">-</code>
                                            &bull;
                                            Баннер: <span class="badge bg-secondary" id="previewBanner">-</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Шаг 3: Тип трекинга --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <span class="badge bg-primary me-1">3</span>
                                Тип трекинга <span class="text-danger">*</span>
                            </label>
                            <p class="text-muted small mb-3">Выберите Keitaro (для трекинга) или Оффер (прямой лендинг)</p>

                            @error('tracking_type')
                                <div class="alert alert-danger py-2">{{ $message }}</div>
                            @enderror

                            <div class="row g-3">
                                {{-- Keitaro вариант --}}
                                <div class="col-md-6">
                                    <div class="card h-100 type-card {{ old('tracking_type', 'keitaro') == 'keitaro' ? 'border-primary' : '' }}"
                                         id="keitaroCard">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="radio"
                                                       name="tracking_type"
                                                       id="typeKeitaro"
                                                       value="keitaro"
                                                       {{ old('tracking_type', 'keitaro') == 'keitaro' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="typeKeitaro">
                                                    <i class="bi bi-graph-up text-primary me-1"></i> Keitaro
                                                </label>
                                            </div>
                                            <p class="text-muted small mt-2 mb-0">
                                                Трекинг через Keitaro с редиректом на оффер
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Оффер вариант --}}
                                <div class="col-md-6">
                                    <div class="card h-100 type-card {{ old('tracking_type') == 'offer' ? 'border-success' : '' }}"
                                         id="offerCard">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="radio"
                                                       name="tracking_type"
                                                       id="typeOffer"
                                                       value="offer"
                                                       {{ old('tracking_type') == 'offer' ? 'checked' : '' }}
                                                       {{ $offers->isEmpty() ? 'disabled' : '' }}>
                                                <label class="form-check-label fw-semibold" for="typeOffer">
                                                    <i class="bi bi-box-seam text-success me-1"></i> Оффер
                                                </label>
                                            </div>
                                            <p class="text-muted small mt-2 mb-0">
                                                Прямой лендинг из ZIP без Keitaro трекинга
                                            </p>
                                            @if($offers->isEmpty())
                                                <span class="badge bg-warning-subtle text-warning mt-2">Нет офферов</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Настройки Keitaro (только для типа keitaro) --}}
                        <div class="mb-4 config-section" id="keitaroSection" style="{{ old('tracking_type', 'keitaro') == 'keitaro' ? '' : 'display: none;' }}">
                            <label class="form-label fw-semibold">Настройки Keitaro <span class="text-danger">*</span></label>
                            <p class="text-muted small mb-3">Настройте трекер Keitaro для black сайта</p>

                            @error('khost')
                                <div class="alert alert-danger py-2">{{ $message }}</div>
                            @enderror
                            @error('kapitoken')
                                <div class="alert alert-danger py-2">{{ $message }}</div>
                            @enderror

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Keitaro Host (khost) <span class="text-danger">*</span></label>
                                    <input type="url"
                                           name="khost"
                                           class="form-control @error('khost') is-invalid @enderror"
                                           placeholder="https://your-tracker.com/"
                                           value="{{ old('khost') }}"
                                           id="khostInput">
                                    <small class="text-muted">URL вашего трекера Keitaro</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Keitaro Token (kapitoken) <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="kapitoken"
                                           class="form-control @error('kapitoken') is-invalid @enderror"
                                           placeholder="your_campaign_token"
                                           value="{{ old('kapitoken') }}"
                                           id="kapitokenInput">
                                    <small class="text-muted">Токен кампании из Keitaro</small>
                                </div>
                            </div>
                        </div>

                        {{-- Выбор Оффера (только для типа offer) --}}
                        <div class="mb-4 config-section" id="offerSection" style="{{ old('tracking_type') == 'offer' ? '' : 'display: none;' }}">
                            <label class="form-label fw-semibold">Выберите Оффер <span class="text-danger">*</span></label>
                            @error('offer_id')
                                <div class="alert alert-danger py-2">{{ $message }}</div>
                            @enderror

                            <select name="offer_id"
                                    class="form-select form-select-lg @error('offer_id') is-invalid @enderror"
                                    id="offerSelect">
                                <option value="">-- Выберите Оффер --</option>
                                @foreach($offers as $offer)
                                    <option value="{{ $offer->id }}" {{ old('offer_id') == $offer->id ? 'selected' : '' }}>
                                        {{ $offer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="my-4">

                        {{-- Инфо блок --}}
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Как это работает:</strong><br>
                            <ul class="mb-0 mt-2">
                                <li>White сайт уже задеплоен на домене (показывается ботам)</li>
                                <li><strong>Palladium</strong> фильтрует трафик и отправляет реальных пользователей на Black сайт</li>
                                <li>Black сайт будет создан в директории <code>/newspage/</code></li>
                                <li><strong>Keitaro:</strong> Трекинг + редирект на внешний оффер</li>
                                <li><strong>Оффер:</strong> Прямой лендинг из ZIP без трекинга</li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('buyer.domain-deployments.index') }}" class="btn btn-light">
                                Отмена
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <span class="normal-state">
                                    <i class="bi bi-rocket me-1"></i> Задеплоить Black сайт
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
    .cursor-pointer { cursor: pointer; }

    /* Domain picker */
    .domain-picker {
        position: relative;
    }
    .domain-picker-input input {
        padding-right: 2.5rem;
    }
    .domain-dropdown {
        position: absolute;
        z-index: 1050;
        width: 100%;
        background: #fff;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    }
    .domain-option {
        transition: background-color 0.15s;
        border-bottom: 1px solid #f0f0f0;
    }
    .domain-option:last-child {
        border-bottom: none;
    }
    .domain-option:hover {
        background-color: #f8f9fa;
    }
    .domain-option.hidden {
        display: none !important;
    }
    .domain-option input:checked + span {
        font-weight: 600;
        color: #0d6efd;
    }

    /* Selected tags */
    .selected-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        padding: 0.5rem;
        background: #f8f9fa;
        border-radius: 0.375rem;
        min-height: 2.5rem;
    }
    .domain-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.5rem;
        background: #0d6efd;
        color: #fff;
        border-radius: 0.25rem;
        font-size: 0.8rem;
        max-width: 200px;
    }
    .domain-tag span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .domain-tag .remove-tag {
        margin-left: 0.35rem;
        cursor: pointer;
        opacity: 0.7;
        font-size: 0.7rem;
    }
    .domain-tag .remove-tag:hover {
        opacity: 1;
    }

    /* Scrollbar */
    .domain-list::-webkit-scrollbar {
        width: 6px;
    }
    .domain-list::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .domain-list::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    /* Type cards */
    .type-card {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .type-card:hover {
        border-color: #0d6efd !important;
        box-shadow: 0 0.125rem 0.25rem rgba(13, 110, 253, 0.15);
    }
    .type-card.border-primary {
        border-width: 2px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(13, 110, 253, 0.15);
    }
    .type-card.border-success {
        border-width: 2px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(25, 135, 84, 0.15);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('deployForm');
    const submitBtn = document.getElementById('submitBtn');
    const domainCheckboxes = document.querySelectorAll('.domain-checkbox');
    const selectedCountEl = document.getElementById('selectedCount');
    const selectAllBtn = document.getElementById('selectAllDomains');
    const selectFilteredBtn = document.getElementById('selectFilteredDomains');
    const deselectAllBtn = document.getElementById('deselectAllDomains');
    const toggleListBtn = document.getElementById('toggleDomainList');

    // Domain picker elements
    const domainSearch = document.getElementById('domainSearch');
    const domainDropdown = document.getElementById('domainDropdown');
    const domainOptions = document.querySelectorAll('.domain-option');
    const selectedTagsContainer = document.getElementById('selectedDomainsTags');
    const noDomainsFound = document.getElementById('noDomainsFound');

    // Type selection
    const typeKeitaro = document.getElementById('typeKeitaro');
    const typeOffer = document.getElementById('typeOffer');
    const keitaroSection = document.getElementById('keitaroSection');
    const offerSection = document.getElementById('offerSection');
    const keitaroCard = document.getElementById('keitaroCard');
    const offerCard = document.getElementById('offerCard');

    // Keitaro inputs
    const khostInput = document.getElementById('khostInput');
    const kapitokenInput = document.getElementById('kapitokenInput');

    // Palladium preview
    const palladiumSelect = document.getElementById('palladiumSelect');
    const palladiumPreview = document.getElementById('palladiumPreview');
    const previewClientId = document.getElementById('previewClientId');
    const previewBanner = document.getElementById('previewBanner');

    let dropdownVisible = false;

    // Update selected tags display
    function updateSelectedTags() {
        const checked = document.querySelectorAll('.domain-checkbox:checked');
        selectedCountEl.textContent = checked.length;

        selectedTagsContainer.innerHTML = '';
        if (checked.length > 0) {
            selectedTagsContainer.style.display = 'flex';
            checked.forEach(cb => {
                const tag = document.createElement('span');
                tag.className = 'domain-tag';
                tag.innerHTML = `<span>${cb.dataset.name}</span><i class="bi bi-x remove-tag" data-id="${cb.value}"></i>`;
                selectedTagsContainer.appendChild(tag);
            });
        } else {
            selectedTagsContainer.style.display = 'none';
        }
    }

    // Remove tag click handler
    selectedTagsContainer?.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-tag')) {
            const id = e.target.dataset.id;
            const cb = document.querySelector(`.domain-checkbox[value="${id}"]`);
            if (cb) {
                cb.checked = false;
                updateSelectedTags();
            }
        }
    });

    // Domain search filter
    domainSearch?.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        let visibleCount = 0;

        domainOptions.forEach(option => {
            const domain = option.dataset.domain;
            const server = option.dataset.server;
            const matches = domain.includes(query) || server.includes(query);

            if (matches) {
                option.classList.remove('hidden');
                visibleCount++;
            } else {
                option.classList.add('hidden');
            }
        });

        noDomainsFound.style.display = visibleCount === 0 ? 'block' : 'none';
    });

    // Show dropdown on focus
    domainSearch?.addEventListener('focus', function() {
        showDropdown();
    });

    // Toggle dropdown button
    toggleListBtn?.addEventListener('click', function() {
        if (dropdownVisible) {
            hideDropdown();
        } else {
            showDropdown();
            domainSearch.focus();
        }
    });

    function showDropdown() {
        domainDropdown.style.display = 'block';
        dropdownVisible = true;
        toggleListBtn.innerHTML = '<i class="bi bi-x-lg me-1"></i> Скрыть список';
    }

    function hideDropdown() {
        domainDropdown.style.display = 'none';
        dropdownVisible = false;
        toggleListBtn.innerHTML = '<i class="bi bi-list me-1"></i> Показать список';
    }

    // Close dropdown on click outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.domain-picker') && !e.target.closest('#toggleDomainList')) {
            hideDropdown();
        }
    });

    // Checkbox change handler
    domainCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedTags);
    });

    // Select all
    selectAllBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        domainCheckboxes.forEach(cb => cb.checked = true);
        updateSelectedTags();
    });

    // Select filtered (visible) only
    selectFilteredBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        domainOptions.forEach(option => {
            if (!option.classList.contains('hidden')) {
                const cb = option.querySelector('.domain-checkbox');
                if (cb) cb.checked = true;
            }
        });
        updateSelectedTags();
    });

    // Deselect all
    deselectAllBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        domainCheckboxes.forEach(cb => cb.checked = false);
        updateSelectedTags();
    });

    // Type switching
    function updateTypeSelection() {
        if (typeKeitaro?.checked) {
            keitaroSection.style.display = '';
            offerSection.style.display = 'none';
            keitaroCard?.classList.add('border-primary');
            keitaroCard?.classList.remove('border-success');
            offerCard?.classList.remove('border-primary', 'border-success');
            // Make keitaro fields required
            khostInput.required = true;
            kapitokenInput.required = true;
        } else if (typeOffer?.checked) {
            keitaroSection.style.display = 'none';
            offerSection.style.display = '';
            keitaroCard?.classList.remove('border-primary', 'border-success');
            offerCard?.classList.add('border-success');
            offerCard?.classList.remove('border-primary');
            // Make keitaro fields not required
            khostInput.required = false;
            kapitokenInput.required = false;
        }
    }

    typeKeitaro?.addEventListener('change', updateTypeSelection);
    typeOffer?.addEventListener('change', updateTypeSelection);

    // Card click to select radio
    keitaroCard?.addEventListener('click', function(e) {
        if (e.target.type !== 'radio') {
            typeKeitaro.checked = true;
            updateTypeSelection();
        }
    });

    offerCard?.addEventListener('click', function(e) {
        if (e.target.type !== 'radio' && !typeOffer.disabled) {
            typeOffer.checked = true;
            updateTypeSelection();
        }
    });

    // Palladium preview
    palladiumSelect?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (this.value) {
            previewClientId.textContent = selected.dataset.clientId;
            previewBanner.textContent = selected.dataset.banner;
            palladiumPreview.classList.remove('d-none');
        } else {
            palladiumPreview.classList.add('d-none');
        }
    });

    // Form submit with loading
    form?.addEventListener('submit', function(e) {
        const checkedDomains = document.querySelectorAll('.domain-checkbox:checked');
        if (checkedDomains.length === 0) {
            e.preventDefault();
            alert('Пожалуйста, выберите хотя бы один домен');
            return;
        }

        submitBtn.querySelector('.normal-state').classList.add('d-none');
        submitBtn.querySelector('.loading-state').classList.remove('d-none');
        submitBtn.disabled = true;
    });

    // Initialize
    updateSelectedTags();
    updateTypeSelection();

    // Trigger palladium preview if value exists
    if (palladiumSelect?.value) {
        palladiumSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
@endsection
