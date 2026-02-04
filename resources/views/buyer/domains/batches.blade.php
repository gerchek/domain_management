@extends('layouts.app')

@section('title', 'Мои пакеты')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Мои пакеты</h4>
    <a href="{{ route('buyer.domains.create') }}" class="btn btn-primary">
        <i class="bi bi-plus"></i> Купить домены
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Файл</th>
                        <th>Сервер</th>
                        <th>Всего</th>
                        <th>Успешно</th>
                        <th>Ошибок</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>#{{ $batch->id }}</td>
                            <td>{{ $batch->file_name ?? 'Н/Д' }}</td>
                            <td>{{ $batch->server->name ?? 'Н/Д' }}</td>
                            <td>{{ $batch->total_domains }}</td>
                            <td class="text-success">{{ $batch->successful_domains }}</td>
                            <td class="text-danger">{{ $batch->failed_domains }}</td>
                            <td>
                                @php
                                    $statusLabels = [
                                        'completed' => 'Завершён',
                                        'processing' => 'В процессе',
                                        'failed' => 'Ошибка',
                                        'pending' => 'Ожидает',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $batch->status === 'completed' ? 'success' : ($batch->status === 'processing' ? 'primary' : ($batch->status === 'failed' ? 'danger' : 'secondary')) }}">
                                    {{ $statusLabels[$batch->status] ?? ucfirst($batch->status) }}
                                </span>
                            </td>
                            <td>{{ $batch->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                <a href="{{ route('buyer.domains.batch', $batch) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Подробнее
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-muted text-center py-4">Пакеты не найдены</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $batches->links() }}
</div>
@endsection
