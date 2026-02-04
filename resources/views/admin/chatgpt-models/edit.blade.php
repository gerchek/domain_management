@extends('layouts.app')

@section('title', 'Редактировать модель ChatGPT')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Редактировать модель ChatGPT</h4>
    <a href="{{ route('admin.chatgpt-models.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Назад
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.chatgpt-models.update', $model) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Название</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', $model->name) }}"
                       placeholder="Например: GPT-4 Turbo" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Отображаемое название модели</div>
            </div>

            <div class="mb-3">
                <label for="model_id" class="form-label">Model ID</label>
                <input type="text" class="form-control @error('model_id') is-invalid @enderror"
                       id="model_id" name="model_id" value="{{ old('model_id', $model->model_id) }}"
                       placeholder="Например: gpt-4-turbo" required>
                @error('model_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Идентификатор модели для API OpenAI (например: gpt-4, gpt-4-turbo, gpt-4o, gpt-3.5-turbo)</div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Описание</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="2"
                          placeholder="Краткое описание модели">{{ old('description', $model->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="sort_order" class="form-label">Порядок сортировки</label>
                <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                       id="sort_order" name="sort_order" value="{{ old('sort_order', $model->sort_order) }}"
                       min="0">
                @error('sort_order')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Чем меньше число, тем выше модель в списке</div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                           {{ old('is_active', $model->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Активна (доступна для использования)
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check"></i> Сохранить изменения
            </button>
        </form>
    </div>
</div>
@endsection
