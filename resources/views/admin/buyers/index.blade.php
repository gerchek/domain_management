@extends('layouts.app')

@section('title', 'Байеры')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Байеры</h4>
    <a href="{{ route('admin.buyers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Добавить байера
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Имя</th>
                        <th>Email</th>
                        <th class="text-center">Домены</th>
                        <th class="text-center">Статус</th>
                        <th>Создан</th>
                        <th class="text-end" style="width: 180px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buyers as $buyer)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $buyer->name }}</div>
                            </td>
                            <td>
                                <span class="text-muted">{{ $buyer->email }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark fs-6">{{ $buyer->domains_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($buyer->is_active)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check-circle me-1"></i>Активен
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="bi bi-x-circle me-1"></i>Неактивен
                                    </span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $buyer->created_at->format('d.m.Y H:i') }}</small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.buyers.edit', $buyer) }}"
                                   class="btn btn-sm btn-outline-primary me-1" title="Редактировать">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.buyers.toggle-status', $buyer) }}" method="POST" class="d-inline">
                                    @csrf
                                    @if($buyer->is_active)
                                        <button type="submit" class="btn btn-sm btn-outline-warning me-1" title="Деактивировать">
                                            <i class="bi bi-pause-fill"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Активировать">
                                            <i class="bi bi-play-fill"></i>
                                        </button>
                                    @endif
                                </form>
                                <form action="{{ route('admin.buyers.destroy', $buyer) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Удалить байера {{ $buyer->name }}?')">
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
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-people fs-1 d-block mb-2"></i>
                                    Байеры не найдены
                                </div>
                                <a href="{{ route('admin.buyers.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus"></i> Добавить первого байера
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($buyers->hasPages())
<div class="mt-3">
    {{ $buyers->links() }}
</div>
@endif
@endsection
