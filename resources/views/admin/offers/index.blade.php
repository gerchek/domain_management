@extends('layouts.app')

@section('title', 'Офферы')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Офферы</h4>
        <p class="text-muted mb-0">Управление ZIP файлами офферов для black сайтов</p>
    </div>
    <a href="{{ route('admin.offers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Добавить оффер
    </a>
</div>

@if($offers->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-box-seam text-muted" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Нет офферов</h5>
            <p class="text-muted">Добавьте первый оффер для начала работы</p>
            <a href="{{ route('admin.offers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Добавить оффер
            </a>
        </div>
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Название</th>
                        <th>ZIP файл</th>
                        <th>Использование</th>
                        <th>Создан</th>
                        <th width="100">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($offers as $offer)
                        <tr>
                            <td>
                                <strong>{{ $offer->name }}</strong>
                            </td>
                            <td>
                                @if($offer->zipExists())
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-file-zip me-1"></i> Есть
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Отсутствует
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($offer->domain_deployments_count > 0)
                                    <span class="badge bg-primary">{{ $offer->domain_deployments_count }} деплоев</span>
                                @else
                                    <span class="text-muted">Не используется</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $offer->created_at->format('d.m.Y H:i') }}</small>
                            </td>
                            <td>
                                <form action="{{ route('admin.offers.destroy', $offer) }}"
                                      method="POST"
                                      class="delete-form d-inline"
                                      data-name="{{ $offer->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Удалить"
                                            @if($offer->domain_deployments_count > 0) disabled @endif>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $offers->links() }}
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
document.addEventListener('DOMContentLoaded', function() {
    // Delete confirmation with spinner
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const name = this.dataset.name;
            if (!confirm(`Вы уверены, что хотите удалить "${name}"?`)) {
                e.preventDefault();
                return;
            }

            showDeleteOverlay(`Удаляем "${name}"...`);
        });
    });

    function showDeleteOverlay(message) {
        const overlay = document.createElement('div');
        overlay.className = 'delete-overlay';
        overlay.innerHTML = `
            <div class="delete-modal">
                <div class="delete-spinner"></div>
                <h5>${message}</h5>
                <p class="text-muted mb-0">Подождите</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }
});
</script>
@endpush
@endsection
