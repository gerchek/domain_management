@extends('layouts.app')

@section('title', 'Проект #' . $project->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Проект #{{ $project->id }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('buyer.sites.index') }}">Мои сайты</a></li>
                <li class="breadcrumb-item active">Проект #{{ $project->id }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        @if($project->status === 'ready' || $project->status === 'failed')
            <form action="{{ route('buyer.sites.destroy', $project) }}" method="POST" id="deleteForm"
                  onsubmit="return confirmDelete(this)">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger" id="deleteBtn">
                    <i class="bi bi-trash me-1"></i> <span>Удалить с сервера</span>
                </button>
            </form>
        @endif
        <a href="{{ route('buyer.sites.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> К списку
        </a>
    </div>
</div>

<!-- Project Status Card -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-info-circle me-1"></i> Информация о проекте</span>
        <span id="projectStatus">
            @if($project->status === 'ready')
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Готов</span>
            @elseif($project->status === 'generating')
                <span class="badge bg-warning"><i class="bi bi-hourglass-split me-1"></i>Генерация...</span>
            @elseif($project->status === 'failed')
                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Ошибка</span>
            @else
                <span class="badge bg-secondary"><i class="bi bi-clock me-1"></i>Ожидание</span>
            @endif
        </span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th class="ps-0" style="width: 150px;">Промпт:</th>
                        <td>
                            <strong>{{ $project->prompt->name }}</strong>
                            <span class="badge bg-secondary-subtle text-secondary ms-1">{{ strtoupper($project->prompt->language) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="ps-0">Создан:</th>
                        <td>{{ $project->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th class="ps-0">Сгенерирован:</th>
                        <td id="generatedAt">
                            @if($project->generated_at)
                                {{ $project->generated_at->format('d.m.Y H:i') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th class="ps-0" style="width: 150px;">Файлов:</th>
                        <td id="filesCount">{{ $project->files_count ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th class="ps-0">Размер:</th>
                        <td>{{ $project->total_size ? number_format($project->total_size / 1024, 2) . ' KB' : '—' }}</td>
                    </tr>
                    @if($project->error_message)
                    <tr>
                        <th class="ps-0 text-danger">Ошибка:</th>
                        <td class="text-danger">{{ $project->error_message }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Deployments Card -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-cloud-upload me-1"></i> Развёртывание на домены
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Домен</th>
                        <th>Сервер</th>
                        <th class="text-center">Статус</th>
                        <th class="text-center">SSL</th>
                        <th>Развёрнут</th>
                        <th>Ошибка</th>
                        <th class="text-end">Действия</th>
                    </tr>
                </thead>
                <tbody id="deploymentsTable">
                    @foreach($project->deployments as $deployment)
                        <tr data-deployment-id="{{ $deployment->id }}">
                            <td>
                                <strong>{{ $deployment->domain->domain_name }}</strong>
                                @if($deployment->status === 'completed')
                                    <br>
                                    <a href="https://{{ $deployment->domain->domain_name }}" target="_blank" class="small">
                                        <i class="bi bi-box-arrow-up-right"></i> Открыть
                                    </a>
                                @endif
                            </td>
                            <td>
                                @if($deployment->domain->server)
                                    {{ $deployment->domain->server->name }}
                                    <br>
                                    <small class="text-muted">{{ $deployment->domain->server->ip_address }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center deployment-status">
                                @if($deployment->status === 'completed')
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check-circle me-1"></i>Готово
                                    </span>
                                @elseif($deployment->status === 'deploying')
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="bi bi-arrow-repeat me-1 spin"></i>Развёртывание
                                    </span>
                                @elseif($deployment->status === 'failed')
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="bi bi-x-circle me-1"></i>Ошибка
                                    </span>
                                @elseif($deployment->status === 'removed')
                                    <span class="badge bg-dark-subtle text-dark">
                                        <i class="bi bi-trash me-1"></i>Удалён
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <i class="bi bi-clock me-1"></i>Ожидание
                                    </span>
                                @endif
                            </td>
                            <td class="text-center deployment-ssl">
                                @if($deployment->ssl_installed)
                                    <span class="badge bg-success"><i class="bi bi-shield-check"></i></span>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-shield"></i></span>
                                    @if(in_array($deployment->status, ['completed', 'failed']))
                                        <form action="{{ route('buyer.sites.deployment.retry-ssl', $deployment) }}" method="POST" class="d-inline ms-1">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success retry-ssl-btn" title="Установить SSL">
                                                <i class="bi bi-shield-plus"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                            <td class="deployment-date">
                                @if($deployment->deployed_at)
                                    {{ $deployment->deployed_at->format('d.m.Y H:i') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="deployment-error" style="max-width: 300px;">
                                @if($deployment->error_message)
                                    <div class="text-danger small error-text-wrap">{{ $deployment->error_message }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if(in_array($deployment->status, ['completed', 'failed']))
                                    <form action="{{ route('buyer.sites.deployment.destroy', $deployment) }}" method="POST" class="d-inline delete-form"
                                          onsubmit="return confirmDeleteDomain(this, '{{ $deployment->domain->domain_name }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить с сервера">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @elseif($deployment->status === 'removed')
                                    <span class="badge bg-secondary">Удалён</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

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
.error-text-wrap {
    word-wrap: break-word;
    white-space: pre-wrap;
    line-height: 1.4;
    max-height: 80px;
    overflow-y: auto;
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
    showDeleteOverlay('Удаление всех сайтов...');
    disableButton(form);
    return true;
}

function confirmDeleteDomain(form, domainName) {
    if (!confirm('Удалить сайт ' + domainName + ' с сервера?')) {
        return false;
    }
    showDeleteOverlay('Удаление ' + domainName + '...');
    disableButton(form);
    return true;
}

function showDeleteOverlay(message) {
    const overlay = document.createElement('div');
    overlay.className = 'delete-overlay';
    overlay.innerHTML = `
        <div class="delete-modal">
            <div class="delete-spinner"></div>
            <h5>${message}</h5>
            <p class="text-muted mb-0">Пожалуйста, подождите</p>
        </div>
    `;
    document.body.appendChild(overlay);
}

function disableButton(form) {
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split spin"></i>';
}

// SSL retry button handling
document.querySelectorAll('.retry-ssl-btn').forEach(btn => {
    btn.closest('form').addEventListener('submit', function(e) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split spin"></i>';
        showDeleteOverlay('Установка SSL сертификата...');
    });
});
</script>
@endpush

@if(in_array($project->status, ['pending', 'generating']) || $project->deployments->whereIn('status', ['pending', 'deploying'])->count() > 0)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectId = {{ $project->id }};
    let polling = true;

    function updateStatus() {
        fetch('{{ route('buyer.sites.status', $project) }}')
            .then(response => response.json())
            .then(data => {
                // Update project status
                const statusEl = document.getElementById('projectStatus');
                if (data.project_status === 'ready') {
                    statusEl.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Готов</span>';
                } else if (data.project_status === 'generating') {
                    statusEl.innerHTML = '<span class="badge bg-warning"><i class="bi bi-hourglass-split me-1"></i>Генерация...</span>';
                } else if (data.project_status === 'failed') {
                    statusEl.innerHTML = '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Ошибка</span>';
                    polling = false;
                }

                // Update generated at
                if (data.generated_at) {
                    document.getElementById('generatedAt').textContent = data.generated_at;
                }

                // Update files count
                if (data.files_count) {
                    document.getElementById('filesCount').textContent = data.files_count;
                }

                // Update deployments
                data.deployments.forEach(dep => {
                    const row = document.querySelector(`tr[data-deployment-id="${dep.id}"]`);
                    if (row) {
                        // Status
                        const statusCell = row.querySelector('.deployment-status');
                        if (dep.status === 'completed') {
                            statusCell.innerHTML = '<span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Готово</span>';
                        } else if (dep.status === 'deploying') {
                            statusCell.innerHTML = '<span class="badge bg-info-subtle text-info"><i class="bi bi-arrow-repeat me-1 spin"></i>Развёртывание</span>';
                        } else if (dep.status === 'failed') {
                            statusCell.innerHTML = '<span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i>Ошибка</span>';
                        }

                        // SSL
                        const sslCell = row.querySelector('.deployment-ssl');
                        if (dep.ssl_installed) {
                            sslCell.innerHTML = '<span class="badge bg-success"><i class="bi bi-shield-check"></i></span>';
                        }

                        // Date
                        const dateCell = row.querySelector('.deployment-date');
                        if (dep.deployed_at) {
                            dateCell.textContent = dep.deployed_at;
                        }

                        // Error
                        const errorCell = row.querySelector('.deployment-error');
                        if (dep.error_message) {
                            errorCell.innerHTML = `<div class="text-danger small error-text-wrap">${dep.error_message.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>`;
                        }
                    }
                });

                // Check if we need to continue polling
                const hasPending = data.deployments.some(d => d.status === 'pending' || d.status === 'deploying');
                if (data.project_status === 'ready' && !hasPending) {
                    polling = false;
                }

                if (polling) {
                    setTimeout(updateStatus, 3000);
                }
            })
            .catch(error => {
                console.error('Error fetching status:', error);
                if (polling) {
                    setTimeout(updateStatus, 5000);
                }
            });
    }

    // Start polling
    setTimeout(updateStatus, 3000);
});
</script>
@endpush
@endif
@endsection
