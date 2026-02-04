@extends('layouts.app')

@section('title', 'Деплои доменов')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Деплои доменов</h4>
    <a href="{{ route('buyer.domain-deployments.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Новый деплой
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Домен</th>
                        <th>Тип</th>
                        <th>Сервер</th>
                        <th class="text-center">Статус</th>
                        <th>Задеплоен</th>
                        <th class="text-end">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deployments as $deployment)
                        <tr>
                            <td>
                                <span class="fw-semibold">#{{ $deployment->id }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $deployment->domain->domain_name }}</div>
                                @if($deployment->siteProject)
                                    <small class="text-muted">White: {{ $deployment->siteProject->prompt->name ?? 'Project #'.$deployment->siteProject->id }}</small>
                                @endif
                            </td>
                            <td>
                                @if($deployment->isKeitaroType())
                                    <span class="badge bg-primary-subtle text-primary">
                                        <i class="bi bi-graph-up me-1"></i>Keitaro
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-box-seam me-1"></i>Offer
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($deployment->domain->server)
                                    <span class="badge bg-info-subtle text-info">
                                        {{ $deployment->domain->server->ip_address }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($deployment->status === 'deployed')
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check-circle me-1"></i>Готово
                                    </span>
                                @elseif($deployment->status === 'failed')
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="bi bi-x-circle me-1"></i>Ошибка
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="bi bi-hourglass-split me-1"></i>Деплоится
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($deployment->deployed_at)
                                    <small class="text-muted">{{ $deployment->deployed_at->format('d.m.Y H:i') }}</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('buyer.domain-deployments.show', $deployment) }}"
                                   class="btn btn-sm btn-outline-primary me-1" title="Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(in_array($deployment->status, ['deployed', 'failed']))
                                    <form action="{{ route('buyer.domain-deployments.destroy', $deployment) }}" method="POST" class="d-inline delete-form"
                                          onsubmit="return confirmDelete(this)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-layers fs-1 d-block mb-2"></i>
                                    Деплоев пока нет
                                </div>
                                <a href="{{ route('buyer.domain-deployments.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus"></i> Создать первый деплой
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($deployments->hasPages())
<div class="mt-3">
    {{ $deployments->links() }}
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
    if (!confirm('Удалить этот деплой с сервера?')) {
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
