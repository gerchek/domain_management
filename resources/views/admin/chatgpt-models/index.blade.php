@extends('layouts.app')

@section('title', 'Модели ChatGPT')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Модели ChatGPT</h4>
    <a href="{{ route('admin.chatgpt-models.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Добавить модель
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Название</th>
                        <th>Model ID</th>
                        <th>Описание</th>
                        <th class="text-center">Порядок</th>
                        <th class="text-center">Статус</th>
                        <th class="text-end" style="width: 180px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($models as $model)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $model->name }}</div>
                            </td>
                            <td>
                                <code>{{ $model->model_id }}</code>
                            </td>
                            <td>
                                <span class="text-muted">{{ Str::limit($model->description, 50) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark">{{ $model->sort_order }}</span>
                            </td>
                            <td class="text-center">
                                @if($model->is_active)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check-circle me-1"></i>Активна
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="bi bi-x-circle me-1"></i>Неактивна
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.chatgpt-models.edit', $model) }}"
                                   class="btn btn-sm btn-outline-primary me-1" title="Редактировать">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.chatgpt-models.toggle-status', $model) }}" method="POST" class="d-inline">
                                    @csrf
                                    @if($model->is_active)
                                        <button type="submit" class="btn btn-sm btn-outline-warning me-1" title="Деактивировать">
                                            <i class="bi bi-pause-fill"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Активировать">
                                            <i class="bi bi-play-fill"></i>
                                        </button>
                                    @endif
                                </form>
                                <form action="{{ route('admin.chatgpt-models.destroy', $model) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Удалить модель {{ $model->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-cpu fs-1 d-block mb-2"></i>
                                    Модели не найдены
                                </div>
                                <a href="{{ route('admin.chatgpt-models.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus"></i> Добавить первую модель
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($models->hasPages())
<div class="mt-3">
    {{ $models->links() }}
</div>
@endif
@endsection
