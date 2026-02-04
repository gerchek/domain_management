@extends('layouts.app')

@section('title', 'Управление серверами')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Серверы</h4>
    <a href="{{ route('admin.servers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus"></i> Добавить сервер
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>IP адрес</th>
                        <th>SSH порт</th>
                        <th>Домены</th>
                        <th>Статус</th>
                        <th class="text-end" style="width: 180px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($servers as $server)
                        <tr>
                            <td>{{ $server->name }}</td>
                            <td><code>{{ $server->ip_address }}</code></td>
                            <td>{{ $server->ssh_port }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="me-2">{{ $server->current_domains_count }}/{{ $server->max_domains }}</span>
                                    @php
                                        $percentage = $server->max_domains > 0 ? ($server->current_domains_count / $server->max_domains) * 100 : 0;
                                    @endphp
                                    <div class="progress flex-grow-1" style="height: 6px; width: 100px;">
                                        <div class="progress-bar {{ $percentage > 80 ? 'bg-danger' : ($percentage > 50 ? 'bg-warning' : 'bg-success') }}"
                                             style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $server->is_active ? 'success' : 'danger' }}">
                                    {{ $server->is_active ? 'Активен' : 'Неактивен' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.servers.edit', $server) }}"
                                   class="btn btn-sm btn-outline-primary me-1" title="Редактировать">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.servers.toggle-status', $server) }}" method="POST" class="d-inline">
                                    @csrf
                                    @if($server->is_active)
                                        <button type="submit" class="btn btn-sm btn-outline-warning me-1" title="Деактивировать">
                                            <i class="bi bi-pause-fill"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Активировать">
                                            <i class="bi bi-play-fill"></i>
                                        </button>
                                    @endif
                                </form>
                                <form action="{{ route('admin.servers.destroy', $server) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Удалить сервер {{ $server->name }}?')">
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
                            <td colspan="6" class="text-muted text-center py-4">Серверы не найдены</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $servers->links() }}
</div>
@endsection
