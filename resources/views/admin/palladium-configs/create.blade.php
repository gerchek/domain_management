@extends('layouts.app')

@section('title', 'Добавить Palladium конфиг')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.palladium-configs.index') }}" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Назад к конфигам
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-shield-plus me-2"></i>Добавить Palladium конфиг</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.palladium-configs.store') }}" method="POST" enctype="multipart/form-data" id="createForm">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Название конфига <span class="text-danger">*</span></label>
                        <input type="text"
                               name="name"
                               class="form-control form-control-lg @error('name') is-invalid @enderror"
                               placeholder="Например: Palladium US Traffic, Facebook Campaign"
                               value="{{ old('name') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Описательное название для идентификации этой конфигурации</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">GEO страны</label>
                        <input type="text"
                               name="geo"
                               class="form-control @error('geo') is-invalid @enderror"
                               placeholder="Например: us,es,de,fr"
                               value="{{ old('geo') }}">
                        @error('geo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Целевые страны для этого конфига (через запятую, только для отображения)</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Palladium PHP файл <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="file"
                                   name="palladium_file"
                                   class="form-control @error('palladium_file') is-invalid @enderror"
                                   accept=".php,.txt"
                                   id="fileInput"
                                   required>
                            <label class="input-group-text" for="fileInput">
                                <i class="bi bi-file-code"></i>
                            </label>
                        </div>
                        @error('palladium_file')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            Загрузите index.php файл, содержащий учётные данные Palladium.
                            Система извлечёт: clientId, clientCompany, clientSecret, bannerSource
                        </small>
                    </div>

                    <!-- File preview area -->
                    <div id="filePreview" class="mb-4 d-none">
                        <div class="card bg-light">
                            <div class="card-header py-2">
                                <i class="bi bi-file-code me-1"></i>
                                <span id="fileName">-</span>
                                <span class="badge bg-secondary ms-2" id="fileSize">-</span>
                            </div>
                            <div class="card-body py-2">
                                <small class="text-muted">Файл выбран и готов к загрузке</small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Ожидаемый формат файла:</strong><br>
                        <code class="d-block mt-2 p-2 bg-white rounded">
                            $headers['auth']['clientId'] = 5795;<br>
                            $headers['auth']['clientCompany'] = "VHAlcYWR55Dm...";<br>
                            $headers['auth']['clientSecret'] = "NTc5NVZ...";<br>
                            $headers['server']['bannerSource'] = 'adwords';
                        </code>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.palladium-configs.index') }}" class="btn btn-light">
                            Отмена
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="normal-state">
                                <i class="bi bi-check-lg me-1"></i> Сохранить
                            </span>
                            <span class="loading-state d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span> Сохраняем...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const form = document.getElementById('createForm');
    const submitBtn = document.getElementById('submitBtn');

    // File preview
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            fileName.textContent = file.name;
            fileSize.textContent = formatBytes(file.size);
            filePreview.classList.remove('d-none');
        } else {
            filePreview.classList.add('d-none');
        }
    });

    // Form submit with loading spinner
    form.addEventListener('submit', function() {
        submitBtn.querySelector('.normal-state').classList.add('d-none');
        submitBtn.querySelector('.loading-state').classList.remove('d-none');
        submitBtn.disabled = true;
    });

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Байт';
        const k = 1024;
        const sizes = ['Байт', 'КБ', 'МБ', 'ГБ'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>
@endpush
@endsection
