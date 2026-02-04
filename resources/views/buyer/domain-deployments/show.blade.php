@extends('layouts.app')

@section('title', 'Деплой #' . $deployment->id)

@section('content')
<div class="mb-4">
    <a href="{{ route('buyer.domain-deployments.index') }}" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Назад к деплоям
    </a>
</div>

@if($deployment->isPending())
<!-- Спиннер загрузки -->
<div class="card mb-4 border-warning" id="loading-card">
    <div class="card-body text-center py-5">
        <div class="spinner-border text-warning mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Загрузка...</span>
        </div>
        <h5 class="mb-2">Деплоим black сайт на сервер...</h5>
        <p class="text-muted mb-0" id="loading-status">Подключение к серверу и загрузка файлов</p>
        <div class="progress mt-3" style="height: 4px; max-width: 300px; margin: 0 auto;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width: 100%"></div>
        </div>
        <small class="text-muted d-block mt-3">Страница обновится автоматически</small>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Деплой #{{ $deployment->id }}</h5>
                <div>
                    {{-- Бейдж типа трекинга --}}
                    @if($deployment->isKeitaroType())
                        <span class="badge bg-primary-subtle text-primary me-2">
                            <i class="bi bi-graph-up me-1"></i> Keitaro
                        </span>
                    @else
                        <span class="badge bg-success-subtle text-success me-2">
                            <i class="bi bi-box-seam me-1"></i> Оффер
                        </span>
                    @endif
                    {{-- Бейдж статуса --}}
                    <span id="status-badge">
                        @if($deployment->status === 'deployed')
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Готово</span>
                        @elseif($deployment->status === 'failed')
                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Ошибка</span>
                        @else
                            <span class="badge bg-warning">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Деплоится...
                            </span>
                        @endif
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Домен</h6>
                        <p class="fw-semibold mb-0">
                            {{ $deployment->domain->domain_name }}
                            @if($deployment->isDeployed())
                                <a href="https://{{ $deployment->domain->domain_name }}" target="_blank" class="ms-2">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Сервер</h6>
                        <p class="mb-0">
                            @if($deployment->domain->server)
                                {{ $deployment->domain->server->name ?? $deployment->domain->server->ip_address }}
                                @if($deployment->server_host)
                                    <br><small class="text-muted">{{ $deployment->server_host }}</small>
                                @endif
                            @else
                                <span class="text-muted">Не назначен</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Статус Black сайта</h6>
                        <p class="mb-0">
                            @if($deployment->isDeployed())
                                <span class="badge bg-dark"><i class="bi bi-check-lg me-1"></i> Задеплоен</span>
                                <br><small class="text-muted">Путь: /newspage/</small>
                            @elseif($deployment->isPending())
                                <span class="badge bg-warning">Деплоится...</span>
                            @else
                                <span class="badge bg-danger">Ошибка</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Дата деплоя</h6>
                        <p class="mb-0">
                            @if($deployment->deployed_at)
                                {{ $deployment->deployed_at->format('d.m.Y H:i:s') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($deployment->siteProject)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-muted mb-1">Проект White сайта</h6>
                            <p class="mb-0">
                                #{{ $deployment->siteProject->id }} - {{ $deployment->siteProject->prompt->name ?? 'Неизвестно' }}
                                <br><small class="text-muted">{{ $deployment->siteProject->files_count ?? 0 }} файлов</small>
                            </p>
                        </div>
                    </div>
                @endif

                <hr>

                {{-- Palladium конфиг (ALWAYS shown since it's always used) --}}
                @if($deployment->palladiumConfigRelation)
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-shield-check text-primary me-1"></i> Palladium конфиг
                    </h6>
                    <div class="card bg-light mb-4">
                        <div class="card-body py-2">
                            <div class="row small">
                                <div class="col-md-4">
                                    <strong>Название:</strong> {{ $deployment->palladiumConfigRelation->name }}
                                </div>
                                <div class="col-md-4">
                                    <strong>GEO:</strong>
                                    @if($deployment->palladiumConfigRelation->geo)
                                        <span class="badge bg-info-subtle text-info">{{ $deployment->palladiumConfigRelation->geo }}</span>
                                    @else
                                        -
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <strong>Client ID:</strong> <code>{{ $deployment->palladium_config['client_id'] ?? '-' }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Настройки Keitaro (only for keitaro type) --}}
                @if($deployment->isKeitaroType() && !empty($deployment->tracking_config))
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-graph-up text-primary me-1"></i> Настройки Keitaro
                    </h6>
                    <div class="row mb-4">
                        @if(isset($deployment->tracking_config['khost']))
                            <div class="col-md-6">
                                <small class="text-muted">khost:</small>
                                <p class="mb-0"><code>{{ $deployment->tracking_config['khost'] }}</code></p>
                            </div>
                        @endif
                        @if(isset($deployment->tracking_config['kapitoken']))
                            <div class="col-md-6">
                                <small class="text-muted">kapitoken:</small>
                                <p class="mb-0"><code>{{ $deployment->tracking_config['kapitoken'] }}</code></p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Инфо об оффере --}}
                @if($deployment->isOfferType() && $deployment->offer)
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-box-seam text-success me-1"></i> Оффер
                    </h6>
                    <div class="card bg-light mb-4">
                        <div class="card-body py-2">
                            <strong>{{ $deployment->offer->name }}</strong>
                            <br>
                            <small class="text-muted">ZIP распакован в /newspage/</small>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Логи деплоя -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Логи деплоя</h6>
                @if($deployment->isPending())
                    <span class="spinner-border spinner-border-sm text-muted" role="status"></span>
                @endif
            </div>
            <div class="card-body p-0" id="logs-container">
                @if(!empty($deployment->deployment_log))
                    <ul class="list-group list-group-flush">
                        @foreach($deployment->deployment_log as $log)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        @php
                                            $iconClass = match($log['type'] ?? 'info') {
                                                'error' => 'bi-x-circle text-danger',
                                                'success' => 'bi-check-circle text-success',
                                                'warning' => 'bi-exclamation-triangle text-warning',
                                                default => 'bi-info-circle text-info'
                                            };
                                        @endphp
                                        <i class="bi {{ $iconClass }} me-2"></i>
                                        {{ $log['message'] ?? '' }}
                                    </div>
                                    <small class="text-muted">
                                        {{ isset($log['timestamp']) ? \Carbon\Carbon::parse($log['timestamp'])->format('H:i:s') : '' }}
                                    </small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-journal-text fs-3 d-block mb-2"></i>
                        Нет логов
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Действия -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Действия</h6>
            </div>
            <div class="card-body">
                @if($deployment->isDeployed())
                    <a href="https://{{ $deployment->domain->domain_name }}" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-globe me-1"></i> Открыть White сайт
                    </a>
                    <a href="https://{{ $deployment->domain->domain_name }}/newspage/" target="_blank" class="btn btn-outline-dark w-100 mb-2">
                        <i class="bi bi-globe me-1"></i> Открыть Black сайт
                    </a>
                @elseif($deployment->isPending())
                    <button class="btn btn-warning w-100 mb-2" disabled>
                        <span class="spinner-border spinner-border-sm me-1"></span> Деплоится...
                    </button>
                @endif

                {{-- Кнопка редактирования только для Keitaro типа --}}
                @if($deployment->isDeployed() && $deployment->isKeitaroType())
                    <a href="{{ route('buyer.domain-deployments.edit', $deployment) }}" class="btn btn-outline-secondary w-100 mb-2">
                        <i class="bi bi-pencil me-1"></i> Изменить Keitaro
                    </a>
                @endif

                <a href="{{ route('buyer.domain-deployments.logs', $deployment) }}" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="bi bi-journal-text me-1"></i> Все логи
                </a>

                <hr>

                <form action="{{ route('buyer.domain-deployments.destroy', $deployment) }}" method="POST"
                      onsubmit="return confirm('Удалить этот деплой? Black сайт будет удалён, White сайт останется.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash me-1"></i> Удалить деплой
                    </button>
                </form>
            </div>
        </div>

        <!-- Структура сайта -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Структура сайта</h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="mb-2">
                        <i class="bi bi-folder-fill text-warning me-1"></i>
                        <strong>/var/www/{{ $deployment->domain->domain_name }}/</strong>
                    </div>
                    @if($deployment->isKeitaroType())
                        <div class="ms-3 mb-1">
                            <i class="bi bi-file-earmark-code me-1"></i> Xn4z.php
                            <span class="badge bg-secondary-subtle text-secondary ms-1">Keitaro трекинг</span>
                        </div>
                    @endif
                    <div class="ms-3 mb-1">
                        <i class="bi bi-folder text-warning me-1"></i> public_html/
                    </div>
                    {{-- Palladium filter is ALWAYS installed now --}}
                    <div class="ms-4 mb-1">
                        <i class="bi bi-file-earmark-code me-1"></i> index.php
                        <span class="badge bg-primary-subtle text-primary ms-1">Palladium фильтр</span>
                    </div>
                    <div class="ms-4 mb-1">
                        <i class="bi bi-file-earmark-code me-1"></i> mainpage.php
                        <span class="badge bg-success-subtle text-success ms-1">White сайт</span>
                    </div>
                    <div class="ms-4 mb-1">
                        <i class="bi bi-folder text-warning me-1"></i> newspage/
                        @if($deployment->isPending())
                            <span class="spinner-border spinner-border-sm text-warning ms-1"></span>
                        @endif
                    </div>
                    <div class="ms-5 mb-1">
                        <i class="bi bi-file-earmark-code me-1"></i>
                        @if($deployment->isKeitaroType())
                            index.php <span class="badge bg-dark ms-1">Keitaro redirect</span>
                        @else
                            * <span class="badge bg-dark ms-1">Оффер (ZIP)</span>
                        @endif
                    </div>
                    <div class="ms-3">
                        <i class="bi bi-folder text-warning me-1"></i> logs/
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($deployment->isPending())
@push('scripts')
<script>
    // Авто-обновление при pending
    let refreshInterval = setInterval(function() {
        fetch('{{ route('buyer.domain-deployments.status', $deployment) }}')
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'pending') {
                    clearInterval(refreshInterval);
                    window.location.reload();
                }
            })
            .catch(err => console.error('Ошибка проверки статуса:', err));
    }, 3000);
</script>
@endpush
@endif
@endsection
