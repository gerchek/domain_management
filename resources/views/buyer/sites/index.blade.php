@extends('layouts.app')

@section('title', 'Мои сайты')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Мои сайты</h4>
    <a href="{{ route('buyer.sites.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Создать сайт
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Промпт</th>
                        <th>Домены</th>
                        <th class="text-center">Статус</th>
                        <th>Создан</th>
                        <th class="text-end">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            <td>
                                <span class="fw-semibold">#{{ $project->id }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $project->prompt->name }}</div>
                                <small class="text-muted">{{ strtoupper($project->prompt->language) }}</small>
                            </td>
                            <td>
                                @foreach($project->deployments as $deployment)
                                    <div>
                                        @php
                                            $badgeClass = match($deployment->status) {
                                                'completed' => 'success',
                                                'failed' => 'danger',
                                                'removed' => 'dark',
                                                'deploying' => 'info',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $badgeClass }}">
                                            {{ $deployment->domain->domain_name }}
                                            @if($deployment->status === 'removed')
                                                <i class="bi bi-trash ms-1"></i>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-center">
                                @if($project->status === 'ready')
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check-circle me-1"></i>Готов
                                    </span>
                                @elseif($project->status === 'generating')
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="bi bi-hourglass-split me-1"></i>Генерация
                                    </span>
                                @elseif($project->status === 'failed')
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="bi bi-x-circle me-1"></i>Ошибка
                                    </span>
                                @elseif($project->status === 'removed')
                                    <span class="badge bg-dark-subtle text-dark">
                                        <i class="bi bi-trash me-1"></i>Удалён
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <i class="bi bi-clock me-1"></i>Ожидание
                                    </span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $project->created_at->format('d.m.Y H:i') }}</small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('buyer.sites.show', $project) }}"
                                   class="btn btn-sm btn-outline-primary me-1" title="Подробнее">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(in_array($project->status, ['ready', 'failed']))
                                    <form action="{{ route('buyer.sites.destroy', $project) }}" method="POST" class="d-inline delete-form"
                                          onsubmit="return confirmDelete(this)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить с сервера">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-globe fs-1 d-block mb-2"></i>
                                    Сайты не найдены
                                </div>
                                <a href="{{ route('buyer.sites.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus"></i> Создать первый сайт
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($projects->hasPages())
<div class="mt-3">
    {{ $projects->links() }}
</div>
@endif

@push('styles')
<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    display: inline-block;
    animation: spin 1s linear infinite;
}
.delete-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.delete-modal {
    background: white;
    padding: 2rem 3rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}
.delete-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #dc3545;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}
</style>
@endpush

@push('scripts')
<script>
function confirmDelete(form) {
    if (!confirm('Удалить проект и все сайты с сервера?')) {
        return false;
    }

    // Show loading overlay
    const overlay = document.createElement('div');
    overlay.className = 'delete-overlay';
    overlay.innerHTML = `
        <div class="delete-modal">
            <div class="delete-spinner"></div>
            <h5>Удаление с сервера...</h5>
            <p class="text-muted mb-0">Пожалуйста, подождите</p>
        </div>
    `;
    document.body.appendChild(overlay);

    // Disable button
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split spin"></i>';

    return true;
}
</script>
@endpush
@endsection
