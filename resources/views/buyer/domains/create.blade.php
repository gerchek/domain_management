@extends('layouts.app')

@section('title', 'Купить домены')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Купить домены</h4>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('buyer.domains.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Tabs для выбора способа ввода -->
                    <ul class="nav nav-tabs mb-3" id="inputTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="text-tab" data-bs-toggle="tab" data-bs-target="#text-input"
                                    type="button" role="tab">Вставить список</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="file-tab" data-bs-toggle="tab" data-bs-target="#file-input"
                                    type="button" role="tab">Загрузить файл</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="inputTabsContent">
                        <!-- Текстовый ввод -->
                        <div class="tab-pane fade show active" id="text-input" role="tabpanel">
                            <div class="mb-3">
                                <label for="domains_text" class="form-label">Список доменов</label>
                                <textarea class="form-control @error('domains_text') is-invalid @enderror"
                                          id="domains_text" name="domains_text" rows="10"
                                          placeholder='["domain1.com", "domain2.net"]&#10;или&#10;domain1.com&#10;domain2.net'>{{ old('domains_text') }}</textarea>
                                @error('domains_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Поддерживается: JSON массив, построчный список, или через запятую
                                </div>
                            </div>
                        </div>

                        <!-- Файловый ввод -->
                        <div class="tab-pane fade" id="file-input" role="tabpanel">
                            <div class="mb-3">
                                <label for="domains_file" class="form-label">Файл с доменами (TXT)</label>
                                <input type="file" class="form-control @error('domains_file') is-invalid @enderror"
                                       id="domains_file" name="domains_file" accept=".txt">
                                @error('domains_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Загрузите TXT файл с доменами (по одному на строку)</div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="server_id" class="form-label">Сервер</label>
                                <select class="form-select @error('server_id') is-invalid @enderror"
                                        id="server_id" name="server_id" required>
                                    <option value="">Выберите сервер...</option>
                                    @foreach($servers as $server)
                                        <option value="{{ $server->id }}" {{ old('server_id') == $server->id ? 'selected' : '' }}>
                                            {{ $server->name }} ({{ $server->ip_address }}) -
                                            Свободно: {{ $server->availableSlots() }} слотов
                                        </option>
                                    @endforeach
                                </select>
                                @error('server_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="target_count" class="form-label">
                                    Сколько купить
                                    <i class="bi bi-question-circle" data-bs-toggle="tooltip"
                                       title="Укажите сколько доменов нужно успешно купить. Система будет пробовать покупать пока не достигнет этого числа. Оставьте пустым чтобы попробовать купить все."></i>
                                </label>
                                <input type="number" class="form-control @error('target_count') is-invalid @enderror"
                                       id="target_count" name="target_count" min="1"
                                       value="{{ old('target_count') }}"
                                       placeholder="Все из списка">
                                @error('target_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Пустое = попробовать купить все домены из списка</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-cart-plus"></i> Начать покупку
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6><i class="bi bi-lightbulb"></i> Как это работает</h6>
                <p class="mb-0 small">
                    Если вы укажете "Сколько купить" = 5, а в списке 20 доменов, система будет покупать домены
                    по порядку пока не купит 5 штук. Домены которые уже заняты или недоступны будут пропущены.
                </p>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-white">
                <h6 class="mb-0">Примеры формата</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">JSON массив:</p>
                <pre class="bg-light p-2 rounded small">["domain1.lat", "domain2.lat", "domain3.lat"]</pre>

                <p class="small text-muted mb-2">Построчно:</p>
                <pre class="bg-light p-2 rounded small mb-0">domain1.lat
domain2.lat
domain3.lat</pre>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-white">
                <h6 class="mb-0">Возможные статусы</h6>
            </div>
            <div class="card-body small">
                <p class="mb-2"><span class="badge bg-success">success</span> - Домен куплен</p>
                <p class="mb-2"><span class="badge bg-danger">not available</span> - Уже занят</p>
                <p class="mb-2"><span class="badge bg-warning text-dark">premium</span> - Премиум домен</p>
                <p class="mb-0"><span class="badge bg-secondary">system busy</span> - Временная ошибка</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Инициализация tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Очищаем другое поле при переключении табов
    document.getElementById('text-tab').addEventListener('shown.bs.tab', function() {
        document.getElementById('domains_file').value = '';
    });
    document.getElementById('file-tab').addEventListener('shown.bs.tab', function() {
        document.getElementById('domains_text').value = '';
    });
</script>
@endpush
@endsection
