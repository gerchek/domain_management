@extends('layouts.app')

@section('title', 'Добавить сервер')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Добавить сервер</h4>
    <a href="{{ route('admin.servers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Назад
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.servers.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Название сервера</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="ip_address" class="form-label">IP адрес</label>
                    <input type="text" class="form-control @error('ip_address') is-invalid @enderror"
                           id="ip_address" name="ip_address" value="{{ old('ip_address') }}" required>
                    @error('ip_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="ssh_username" class="form-label">SSH пользователь</label>
                    <input type="text" class="form-control @error('ssh_username') is-invalid @enderror"
                           id="ssh_username" name="ssh_username" value="{{ old('ssh_username', 'root') }}" required>
                    @error('ssh_username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="ssh_password" class="form-label">SSH пароль</label>
                    <input type="password" class="form-control @error('ssh_password') is-invalid @enderror"
                           id="ssh_password" name="ssh_password">
                    <small class="text-muted">Обязателен, если нет SSH ключа</small>
                    @error('ssh_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="ssh_port" class="form-label">SSH порт</label>
                    <input type="number" class="form-control @error('ssh_port') is-invalid @enderror"
                           id="ssh_port" name="ssh_port" value="{{ old('ssh_port', 22) }}" required>
                    @error('ssh_port')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="ssh_private_key" class="form-label">SSH приватный ключ (опционально)</label>
                <textarea class="form-control font-monospace @error('ssh_private_key') is-invalid @enderror"
                          id="ssh_private_key" name="ssh_private_key" rows="5"
                          placeholder="-----BEGIN OPENSSH PRIVATE KEY-----">{{ old('ssh_private_key') }}</textarea>
                <small class="text-muted">Вставьте содержимое приватного ключа. Если указан, будет использоваться вместо пароля.</small>
                @error('ssh_private_key')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="max_domains" class="form-label">Макс. доменов</label>
                <input type="number" class="form-control @error('max_domains') is-invalid @enderror"
                       id="max_domains" name="max_domains" value="{{ old('max_domains', 500) }}" required>
                @error('max_domains')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check"></i> Создать сервер
            </button>
        </form>
    </div>
</div>
@endsection
