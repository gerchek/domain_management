@extends('layouts.app')

@section('title', 'Настройки')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Настройки</h4>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="dynadot_api_key" class="form-label">Dynadot API Key</label>
                <input type="text" class="form-control @error('dynadot_api_key') is-invalid @enderror"
                       id="dynadot_api_key" name="dynadot_api_key"
                       value="{{ old('dynadot_api_key', $settings['dynadot_api_key']) }}" required>
                @error('dynadot_api_key')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Ваш API ключ Dynadot для регистрации доменов</div>
            </div>

            <div class="mb-3">
                <label for="domains_per_request" class="form-label">Доменов за запрос</label>
                <input type="number" class="form-control @error('domains_per_request') is-invalid @enderror"
                       id="domains_per_request" name="domains_per_request"
                       value="{{ old('domains_per_request', $settings['domains_per_request']) }}"
                       min="1" max="100" required>
                @error('domains_per_request')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Количество доменов для обработки за один API запрос (макс. 100)</div>
            </div>

            <hr class="my-4">
            <h5 class="mb-3">ChatGPT API</h5>

            <div class="mb-3">
                <label for="chatgpt_api_key" class="form-label">ChatGPT API Key</label>
                <input type="text" class="form-control @error('chatgpt_api_key') is-invalid @enderror"
                       id="chatgpt_api_key" name="chatgpt_api_key"
                       value="{{ old('chatgpt_api_key', $settings['chatgpt_api_key']) }}"
                       placeholder="sk-...">
                @error('chatgpt_api_key')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">API ключ OpenAI для генерации сайтов (получить на platform.openai.com)</div>
            </div>

            <div class="mb-3">
                <label for="chatgpt_model" class="form-label">Модель ChatGPT</label>
                <select class="form-select @error('chatgpt_model') is-invalid @enderror"
                        id="chatgpt_model" name="chatgpt_model">
                    @forelse($chatgptModels as $model)
                        <option value="{{ $model->model_id }}" {{ old('chatgpt_model', $settings['chatgpt_model']) == $model->model_id ? 'selected' : '' }}>
                            {{ $model->name }}@if($model->description) ({{ $model->description }})@endif
                        </option>
                    @empty
                        <option value="gpt-4" {{ old('chatgpt_model', $settings['chatgpt_model']) == 'gpt-4' ? 'selected' : '' }}>GPT-4 (По умолчанию)</option>
                    @endforelse
                </select>
                @error('chatgpt_model')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    Модель для генерации кода сайтов.
                    <a href="{{ route('admin.chatgpt-models.index') }}">Управление моделями</a>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check"></i> Сохранить настройки
            </button>
        </form>
    </div>
</div>
@endsection
