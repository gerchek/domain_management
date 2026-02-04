@extends('layouts.app')

@section('title', 'Проект #' . $project->id)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.site-projects.index') }}" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Назад к проектам
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Информация о проекте -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Проект #{{ $project->id }}</h5>
                @if($project->status === 'ready')
                    <span class="badge bg-success">Готов</span>
                @elseif($project->status === 'failed')
                    <span class="badge bg-danger">Ошибка</span>
                @else
                    <span class="badge bg-secondary">{{ $project->status }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Байер:</strong><br>
                        {{ $project->buyer->name }} ({{ $project->buyer->email }})
                    </div>
                    <div class="col-md-6">
                        <strong>Промпт:</strong><br>
                        {{ $project->prompt->name ?? '-' }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Создан:</strong><br>
                        {{ $project->created_at->format('d.m.Y H:i:s') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Сгенерирован:</strong><br>
                        {{ $project->generated_at?->format('d.m.Y H:i:s') ?? '-' }}
                    </div>
                </div>
                @if($project->error_message)
                    <div class="alert alert-danger">
                        <strong>Ошибка:</strong> {{ $project->error_message }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Хранилище -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-hdd me-2"></i>Хранилище (Storage)</h6>
            </div>
            <div class="card-body">
                @if($storageInfo['exists'])
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Файлы существуют</strong>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Путь:</strong><br>
                            <code class="small">{{ $project->storage_path }}</code>
                        </div>
                        <div class="col-md-6">
                            <strong>Фактический размер:</strong><br>
                            {{ number_format($storageInfo['size'] / 1024, 2) }} KB
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Файлы не найдены в storage</strong>
                        @if($project->storage_path)
                            <br><small>Ожидаемый путь: {{ $project->storage_path }}</small>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Список файлов -->
        @if(!empty($files))
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-file-earmark-code me-2"></i>Файлы проекта ({{ count($files) }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Файл</th>
                                <th>Размер</th>
                                <th>Изменён</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $file)
                                <tr>
                                    <td><code class="small">{{ $file['name'] }}</code></td>
                                    <td><small>{{ number_format($file['size'] / 1024, 2) }} KB</small></td>
                                    <td><small>{{ $file['modified'] }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Деплои -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-cloud-upload me-2"></i>Деплои ({{ $project->deployments->count() }})</h6>
            </div>
            <div class="card-body p-0">
                @if($project->deployments->count() > 0)
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Домен</th>
                                <th>Сервер</th>
                                <th>Статус</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($project->deployments as $deployment)
                                <tr>
                                    <td>{{ $deployment->domain->domain_name }}</td>
                                    <td>{{ $deployment->domain->server->ip_address ?? '-' }}</td>
                                    <td>
                                        @if($deployment->status === 'completed')
                                            <span class="badge bg-success">Задеплоен</span>
                                        @elseif($deployment->status === 'failed')
                                            <span class="badge bg-danger">Ошибка</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $deployment->status }}</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $deployment->deployed_at?->format('d.m.Y H:i') ?? '-' }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-4 text-muted">
                        Нет деплоев
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Действия -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Действия</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($storageInfo['exists'])
                        <form action="{{ route('admin.site-projects.delete-files', $project) }}" method="POST"
                              onsubmit="return confirm('Удалить файлы проекта из storage? Деплои на серверах останутся.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-folder-x me-1"></i> Удалить файлы из storage
                            </button>
                        </form>
                    @endif

                    <hr>

                    <form action="{{ route('admin.site-projects.destroy', $project) }}" method="POST"
                          onsubmit="return confirm('Удалить проект полностью (файлы + запись в БД)?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-trash me-1"></i> Удалить проект полностью
                        </button>
                    </form>

                    @if($project->deployments->count() > 0)
                        <div class="alert alert-warning small mt-2 mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            У проекта есть {{ $project->deployments->count() }} деплоев.
                            Удаление проекта не удалит файлы с серверов.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
