<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Управление доменами')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #212529;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: #0d6efd;
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        .main-content {
            min-height: 100vh;
            background: #f8f9fa;
        }
        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
        }
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h5 class="text-white mb-0">
                        <i class="bi bi-globe"></i> Менеджер доменов
                    </h5>
                </div>
                <hr class="text-secondary my-0">
                <nav class="nav flex-column py-3">
                    @if(auth()->user()->isSuperAdmin())
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Панель
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.buyers.*') ? 'active' : '' }}" href="{{ route('admin.buyers.index') }}">
                            <i class="bi bi-people"></i> Байеры
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}" href="{{ route('admin.servers.index') }}">
                            <i class="bi bi-hdd-rack"></i> Серверы
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.prompts.*') ? 'active' : '' }}" href="{{ route('admin.prompts.index') }}">
                            <i class="bi bi-chat-square-text"></i> Промпты
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.chatgpt-models.*') ? 'active' : '' }}" href="{{ route('admin.chatgpt-models.index') }}">
                            <i class="bi bi-cpu"></i> Модели ChatGPT
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                            <i class="bi bi-graph-up"></i> Отчёты
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                            <i class="bi bi-gear"></i> Настройки
                        </a>
                        <hr class="text-secondary my-2">
                        <a class="nav-link {{ request()->routeIs('admin.site-projects.*') ? 'active' : '' }}" href="{{ route('admin.site-projects.index') }}">
                            <i class="bi bi-folder"></i> Проекты сайтов
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.palladium-configs.*') ? 'active' : '' }}" href="{{ route('admin.palladium-configs.index') }}">
                            <i class="bi bi-shield-check"></i> Palladium конфиги
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}" href="{{ route('admin.offers.index') }}">
                            <i class="bi bi-box-seam"></i> Офферы
                        </a>
                    @else
                        <a class="nav-link {{ request()->routeIs('buyer.dashboard') ? 'active' : '' }}" href="{{ route('buyer.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Панель
                        </a>
                        <a class="nav-link {{ request()->routeIs('buyer.domains.create') ? 'active' : '' }}" href="{{ route('buyer.domains.create') }}">
                            <i class="bi bi-plus-circle"></i> Купить домены
                        </a>
                        <a class="nav-link {{ request()->routeIs('buyer.domains.index') ? 'active' : '' }}" href="{{ route('buyer.domains.index') }}">
                            <i class="bi bi-list"></i> Мои домены
                        </a>
                        <a class="nav-link {{ request()->routeIs('buyer.domains.batches') ? 'active' : '' }}" href="{{ route('buyer.domains.batches') }}">
                            <i class="bi bi-collection"></i> Пакеты
                        </a>
                        <hr class="text-secondary my-2">
                        <a class="nav-link {{ request()->routeIs('buyer.sites.*') ? 'active' : '' }}" href="{{ route('buyer.sites.index') }}">
                            <i class="bi bi-globe2"></i> Сайты (White)
                        </a>
                        <a class="nav-link {{ request()->routeIs('buyer.domain-deployments.*') ? 'active' : '' }}" href="{{ route('buyer.domain-deployments.index') }}">
                            <i class="bi bi-layers"></i> Деплои (Black)
                        </a>
                    @endif
                </nav>
                <hr class="text-secondary my-0">
                <div class="p-3">
                    <div class="text-white-50 small mb-2">{{ auth()->user()->name }}</div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm w-100">
                            <i class="bi bi-box-arrow-right"></i> Выйти
                        </button>
                    </form>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        @if(session('success'))
            <div class="toast show toast-success" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong class="me-auto">Успешно</strong>
                    <small class="text-white-50">Сейчас</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="toast show toast-error" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-danger text-white">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <strong class="me-auto">Ошибка</strong>
                    <small class="text-white-50">Сейчас</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="toast show toast-warning" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-warning text-dark">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong class="me-auto">Внимание</strong>
                    <small>Сейчас</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('warning') }}
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="toast show toast-info" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-info text-white">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong class="me-auto">Инфо</strong>
                    <small class="text-white-50">Сейчас</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('info') }}
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-hide toasts after 5 seconds with animation
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('.toast.show');
            toasts.forEach(function(toast) {
                // Add entrance animation
                toast.style.animation = 'slideInRight 0.3s ease';

                // Auto-hide after 5 seconds
                setTimeout(function() {
                    toast.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(function() {
                        toast.classList.remove('show');
                    }, 300);
                }, 5000);
            });
        });
    </script>

    <style>
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .toast {
            min-width: 300px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
            border-radius: 0.5rem;
        }

        .toast-header {
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .toast-body {
            background: white;
            border-radius: 0 0 0.5rem 0.5rem;
        }

        .toast-success .toast-body {
            border-left: 4px solid #198754;
        }

        .toast-error .toast-body {
            border-left: 4px solid #dc3545;
        }

        .toast-warning .toast-body {
            border-left: 4px solid #ffc107;
        }

        .toast-info .toast-body {
            border-left: 4px solid #0dcaf0;
        }
    </style>

    @stack('scripts')
</body>
</html>
