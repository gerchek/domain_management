@extends('layouts.app')

@section('title', 'Логи деплоя')

@section('content')
<div class="mb-4">
    <a href="{{ route('buyer.domain-deployments.show', $deployment) }}" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Назад к деплою
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Логи деплоя</h5>
            <small class="text-muted">Домен: {{ $deployment->domain->domain_name }}</small>
        </div>
        <div>
            @if($deployment->status === 'deployed')
                <span class="badge bg-success">Готово</span>
            @elseif($deployment->status === 'failed')
                <span class="badge bg-danger">Ошибка</span>
            @else
                <span class="badge bg-warning">В процессе</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if(!empty($deployment->deployment_log))
            <div class="timeline">
                @foreach($deployment->deployment_log as $index => $log)
                    @php
                        $logType = $log['type'] ?? 'info';
                        $iconClass = match($logType) {
                            'error' => 'bi-x-circle-fill text-danger',
                            'success' => 'bi-check-circle-fill text-success',
                            'warning' => 'bi-exclamation-triangle-fill text-warning',
                            default => 'bi-info-circle-fill text-info'
                        };
                        $bgClass = match($logType) {
                            'error' => 'bg-danger-subtle',
                            'success' => 'bg-success-subtle',
                            'warning' => 'bg-warning-subtle',
                            default => 'bg-info-subtle'
                        };
                    @endphp
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0 me-3">
                            <i class="bi {{ $iconClass }} fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="p-3 rounded {{ $bgClass }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="fw-semibold">{{ $log['message'] ?? 'Неизвестно' }}</div>
                                    <small class="text-muted ms-2">
                                        @if(isset($log['timestamp']))
                                            {{ \Carbon\Carbon::parse($log['timestamp'])->format('d.m.Y H:i:s') }}
                                        @endif
                                    </small>
                                </div>
                                @if(isset($log['details']))
                                    <pre class="mt-2 mb-0 small text-muted">{{ $log['details'] }}</pre>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                <h5>Нет логов</h5>
                <p class="mb-0">Логи появятся здесь во время и после деплоя.</p>
            </div>
        @endif
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">Детали деплоя</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <small class="text-muted d-block">Создан</small>
                <strong>{{ $deployment->created_at->format('d.m.Y H:i:s') }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Задеплоен</small>
                <strong>{{ $deployment->deployed_at?->format('d.m.Y H:i:s') ?? '-' }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Путь на сервере</small>
                <code>{{ $deployment->server_path ?? '-' }}</code>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 12px;
    top: 30px;
    bottom: 30px;
    width: 2px;
    background: #e9ecef;
}
</style>
@endpush
@endsection
