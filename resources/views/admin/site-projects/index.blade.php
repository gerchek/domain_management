@extends('layouts.app')

@section('title', 'Проекты сайтов')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Проекты сайтов (White Sites)</h4>
    <div class="btn-group">
        <a href="{{ route('admin.site-projects.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise"></i> Обновить
        </a>
    </div>
</div>

<!-- Статистика -->
<div class="row mb-4 g-3">
    <div class="col-6 col-md">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary-subtle text-primary me-3">
                        <i class="bi bi-folder"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">{{ $stats['total'] }}</h3>
                        <small class="text-muted">Всего</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success-subtle text-success me-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">{{ $stats['ready'] }}</h3>
                        <small class="text-muted">Готовых</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-danger-subtle text-danger me-3">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">{{ $stats['failed'] }}</h3>
                        <small class="text-muted">Ошибок</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-secondary-subtle text-secondary me-3">
                        <i class="bi bi-trash"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">{{ $stats['removed'] }}</h3>
                        <small class="text-muted">Удалённых</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-info-subtle text-info me-3">
                        <i class="bi bi-hdd"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">{{ number_format($stats['total_size'] / 1048576, 2) }} MB</h3>
                        <small class="text-muted">Размер</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Байер</label>
                <select name="buyer_id" class="form-select">
                    <option value="">Все байеры</option>
                    @foreach($buyers as $buyer)
                        <option value="{{ $buyer->id }}" {{ request('buyer_id') == $buyer->id ? 'selected' : '' }}>
                            {{ $buyer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Статус</label>
                <select name="status" class="form-select">
                    <option value="">Все статусы</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Ожидание</option>
                    <option value="generating" {{ request('status') == 'generating' ? 'selected' : '' }}>Генерация</option>
                    <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>Готов</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Ошибка</option>
                    <option value="removed" {{ request('status') == 'removed' ? 'selected' : '' }}>Удалён</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Фильтр</button>
                <a href="{{ route('admin.site-projects.index') }}" class="btn btn-outline-secondary">Сброс</a>
            </div>
        </form>
    </div>
</div>

<!-- Таблица -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Байер</th>
                        <th>Промпт</th>
                        <th>Файлы</th>
                        <th>Размер</th>
                        <th>Деплоев</th>
                        <th class="text-center">Статус</th>
                        <th>Создан</th>
                        <th class="text-end">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            <td><span class="fw-semibold">#{{ $project->id }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $project->buyer->name }}</div>
                                <small class="text-muted">{{ $project->buyer->email }}</small>
                            </td>
                            <td>{{ $project->prompt->name ?? '-' }}</td>
                            <td>{{ $project->files_count }}</td>
                            <td>
                                @if($project->total_size > 0)
                                    {{ number_format($project->total_size / 1024, 1) }} KB
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-info">{{ $project->deployments->count() }}</span>
                            </td>
                            <td class="text-center">
                                @if($project->status === 'ready')
                                    <span class="badge bg-success">Готов</span>
                                @elseif($project->status === 'generating')
                                    <span class="badge bg-warning">Генерация</span>
                                @elseif($project->status === 'failed')
                                    <span class="badge bg-danger">Ошибка</span>
                                @elseif($project->status === 'removed')
                                    <span class="badge bg-secondary">Удалён</span>
                                @else
                                    <span class="badge bg-info">Ожидание</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $project->created_at->format('d.m.Y H:i') }}</small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.site-projects.show', $project) }}"
                                   class="btn btn-sm btn-outline-primary me-1" title="Подробнее">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($project->storage_path)
                                    <form action="{{ route('admin.site-projects.delete-files', $project) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Удалить файлы проекта #{{ $project->id }} из storage?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-warning me-1" title="Удалить файлы">
                                            <i class="bi bi-folder-x"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.site-projects.destroy', $project) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Удалить проект #{{ $project->id }} полностью?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить проект">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-folder fs-1 d-block mb-2"></i>
                                    Проекты не найдены
                                </div>
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
    {{ $projects->withQueryString()->links() }}
</div>
@endif
@endsection
