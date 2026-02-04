@extends('layouts.app')

@section('title', 'Редактировать байера')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Редактировать байера</h4>
    <a href="{{ route('admin.buyers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Назад
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.buyers.update', $buyer) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Имя</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', $buyer->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror"
                       id="email" name="email" value="{{ old('email', $buyer->email) }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Новый пароль (оставьте пустым, чтобы сохранить текущий)</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror"
                       id="password" name="password">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Подтверждение нового пароля</label>
                <input type="password" class="form-control"
                       id="password_confirmation" name="password_confirmation">
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                       {{ old('is_active', $buyer->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Активен</label>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check"></i> Обновить байера
            </button>
        </form>
    </div>
</div>
@endsection
