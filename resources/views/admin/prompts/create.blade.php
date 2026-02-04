@extends('layouts.app')

@section('title', 'Добавить промпт')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Добавить промпт</h4>
    <a href="{{ route('admin.prompts.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Назад
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.prompts.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Название</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name') }}"
                       placeholder="Например: Испанский сайт - Сетевой фильтр" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Описание (для байеров)</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="2"
                          placeholder="Краткое описание что будет сгенерировано">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="language" class="form-label">Язык сайта</label>
                <select class="form-select @error('language') is-invalid @enderror" id="language" name="language" required>
                    <option value="es" {{ old('language') == 'es' ? 'selected' : '' }}>Испанский (ES)</option>
                    <option value="en" {{ old('language', 'en') == 'en' ? 'selected' : '' }}>Английский (EN)</option>
                    <option value="de" {{ old('language') == 'de' ? 'selected' : '' }}>Немецкий (DE)</option>
                    <option value="fr" {{ old('language') == 'fr' ? 'selected' : '' }}>Французский (FR)</option>
                    <option value="it" {{ old('language') == 'it' ? 'selected' : '' }}>Итальянский (IT)</option>
                    <option value="pt" {{ old('language') == 'pt' ? 'selected' : '' }}>Португальский (PT)</option>
                    <option value="ru" {{ old('language') == 'ru' ? 'selected' : '' }}>Русский (RU)</option>
                </select>
                @error('language')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="prompt_text" class="form-label">Текст промпта для ChatGPT</label>
                <textarea class="form-control @error('prompt_text') is-invalid @enderror"
                          id="prompt_text" name="prompt_text" rows="15"
                          placeholder="Полный текст промпта который будет отправлен в ChatGPT..." required>{{ old('prompt_text') }}</textarea>
                @error('prompt_text')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    Напишите детальный промпт для генерации сайта. Укажите язык, тематику, структуру страниц, требования к контенту и изображениям.
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Активен (доступен для байеров)
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check"></i> Создать промпт
            </button>
        </form>
    </div>
</div>
@endsection
