@extends('layouts.app')

@section('title', 'Редактировать сервер')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Редактировать сервер</h4>
    <a href="{{ route('admin.servers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Назад
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.servers.update', $server) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Название сервера</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name" value="{{ old('name', $server->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="ip_address" class="form-label">IP адрес</label>
                    <input type="text" class="form-control @error('ip_address') is-invalid @enderror"
                           id="ip_address" name="ip_address" value="{{ old('ip_address', $server->ip_address) }}" required>
                    @error('ip_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="ssh_username" class="form-label">SSH пользователь</label>
                    <input type="text" class="form-control @error('ssh_username') is-invalid @enderror"
                           id="ssh_username" name="ssh_username" value="{{ old('ssh_username', $server->ssh_username) }}" required>
                    @error('ssh_username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="ssh_password" class="form-label">SSH пароль (оставьте пустым, чтобы сохранить текущий)</label>
                    <input type="password" class="form-control @error('ssh_password') is-invalid @enderror"
                           id="ssh_password" name="ssh_password">
                    @error('ssh_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="ssh_port" class="form-label">SSH порт</label>
                    <input type="number" class="form-control @error('ssh_port') is-invalid @enderror"
                           id="ssh_port" name="ssh_port" value="{{ old('ssh_port', $server->ssh_port) }}" required>
                    @error('ssh_port')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="ssh_private_key" class="form-label">SSH приватный ключ (оставьте пустым, чтобы сохранить текущий)</label>
                <textarea class="form-control font-monospace @error('ssh_private_key') is-invalid @enderror"
                          id="ssh_private_key" name="ssh_private_key" rows="5"
                          placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"></textarea>
                <small class="text-muted">
                    @if($server->ssh_private_key)
                        <span class="text-success"><i class="bi bi-check-circle"></i> SSH ключ настроен.</span> Оставьте пустым, чтобы сохранить текущий ключ.
                    @else
                        Вставьте содержимое приватного ключа. Если указан, будет использоваться вместо пароля.
                    @endif
                </small>
                @error('ssh_private_key')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="max_domains" class="form-label">Макс. доменов</label>
                    <input type="number" class="form-control @error('max_domains') is-invalid @enderror"
                           id="max_domains" name="max_domains" value="{{ old('max_domains', $server->max_domains) }}" required>
                    @error('max_domains')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Текущих доменов</label>
                    <input type="text" class="form-control" value="{{ $server->current_domains_count }}" disabled>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                       {{ old('is_active', $server->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Активен</label>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check"></i> Обновить сервер
            </button>
        </form>
    </div>
</div>
@endsection
