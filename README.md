# Domain Management System

Laravel система для автоматизации покупки доменов через Dynadot API и генерации сайтов через ChatGPT.

## Возможности

### Фаза 1: Покупка доменов
- Автоматическая покупка доменов через Dynadot API
- Автоматическая настройка DNS на указанный сервер
- Пакетная обработка (до N доменов за запрос)
- Отслеживание прогресса в реальном времени

### Фаза 2: Генерация и деплой сайтов
- Генерация кода сайта через ChatGPT API
- Автоматический деплой на сервера через SSH/SFTP
- Настройка Nginx для каждого домена
- Автоматическая установка SSL через Let's Encrypt
- Один сгенерированный сайт → много доменов

## Требования

- Docker
- Docker Compose

## Быстрый старт

```bash
cd domain_management

# Копировать конфиг
cp .env.example .env
nano .env  # изменить DB_PASSWORD

# Запустить контейнеры
docker-compose up -d

# Установить зависимости
docker-compose exec app composer install

# Сгенерировать ключ
docker-compose exec app php artisan key:generate

# Миграции и сидер
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

## Доступ

После запуска:
- **Приложение:** http://localhost:8080
- **phpMyAdmin:** http://localhost:8081

### Учётные данные по умолчанию

- **Email:** admin@admin.com
- **Password:** password

## Контейнеры

| Контейнер | Описание | Порт |
|-----------|----------|------|
| domain_app | PHP 8.2 FPM | 9000 (internal) |
| domain_nginx | Nginx | 8080 |
| domain_mysql | MySQL 8.0 | 3307 |
| domain_queue | Queue Worker | - |
| domain_phpmyadmin | phpMyAdmin | 8081 |

## Полезные команды Docker

```bash
# Запустить контейнеры
docker-compose up -d

# Остановить контейнеры
docker-compose down

# Логи
docker-compose logs -f

# Shell в контейнер
docker-compose exec app bash

# MySQL консоль
docker-compose exec mysql mysql -u root -p domain_management

# Очистить кэш
docker-compose exec app php artisan cache:clear

# Перезапустить очередь
docker-compose restart queue
```

---

## Функционал

### Супер Админ
- Dashboard со статистикой
- Управление байерами (CRUD)
- Управление серверами (IP, SSH доступы, SSH ключи)
- **Управление промптами** для ChatGPT (CRUD)
- Настройки:
  - Dynadot API ключ
  - **ChatGPT API ключ**
  - **Модель ChatGPT** (gpt-4, gpt-3.5-turbo)
  - Количество доменов за запрос
- Отчёты с фильтрами и экспортом в CSV

### Байер
- Dashboard с личной статистикой
- Загрузка доменов из TXT файла
- Выбор сервера для DNS
- Отслеживание прогресса покупки в реальном времени
- Список всех своих доменов
- **Генерация сайтов:**
  - Выбор доменов со статусом "DNS установлен"
  - Выбор промпта для генерации
  - Отслеживание прогресса генерации и деплоя
  - Просмотр статуса SSL

## Первые шаги после установки

### Для покупки доменов:
1. Войти как admin@admin.com / password
2. **Settings** → добавить Dynadot API ключ
3. **Servers** → добавить сервер (IP адрес куда будут указывать домены)
4. **Buyers** → создать байера
5. Выйти и войти как байер → загрузить TXT с доменами

### Для генерации сайтов:
1. **Settings** → добавить ChatGPT API ключ, выбрать модель
2. **Prompts** → создать промпты для генерации
3. **Servers** → убедиться что SSH доступ настроен (пароль или ключ)
4. Как байер → **Sites** → выбрать домены → выбрать промпт → запустить

**ВАЖНО:** Целевые сервера должны быть подготовлены! См. [SERVER_SETUP.md](SERVER_SETUP.md)

## Структура проекта

```
app/
├── Http/Controllers/
│   ├── Auth/LoginController.php
│   ├── Admin/
│   │   ├── DashboardController.php
│   │   ├── BuyerController.php
│   │   ├── ServerController.php
│   │   ├── SettingsController.php
│   │   ├── ReportController.php
│   │   └── PromptController.php         # NEW
│   └── Buyer/
│       ├── DashboardController.php
│       ├── DomainController.php
│       └── SiteController.php           # NEW
├── Models/
│   ├── User.php
│   ├── Server.php
│   ├── Domain.php
│   ├── DomainBatch.php
│   ├── Setting.php
│   ├── ActivityLog.php
│   ├── Prompt.php                       # NEW
│   ├── SiteProject.php                  # NEW
│   └── SiteDeployment.php               # NEW
├── Jobs/
│   ├── ProcessDomainPurchase.php
│   └── GenerateSiteJob.php              # NEW
└── Services/
    ├── DynadotService.php
    ├── ChatGptService.php               # NEW
    └── DeployService.php                # NEW

docker/
├── nginx/
│   └── default.conf
└── php/
    ├── Dockerfile
    ├── local.ini
    └── supervisord.conf
```

## Таблицы БД

### Основные
- `users` - пользователи (супер админ, байеры)
- `servers` - сервера (IP, SSH данные, SSH ключи)
- `domains` - домены
- `domain_batches` - пачки доменов (с JSON списком pending доменов)
- `settings` - настройки системы
- `activity_logs` - логи действий

### Генерация сайтов (NEW)
- `prompts` - промпты для ChatGPT (название, текст, язык)
- `site_projects` - проекты сайтов (один код на много доменов)
- `site_deployments` - деплои на домены (статус, SSL, ошибки)

## Архитектура генерации сайтов

```
┌─────────────────┐
│  Байер выбирает │
│  домены + промпт│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  SiteProject    │  ← Один проект
│  (status:       │
│   generating)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  ChatGPT API    │  ← Генерация кода
│  generateSite() │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Storage        │  ← Сохранение файлов
│  /sites/        │     storage/app/sites/project_N/
│  project_N/     │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  SiteDeployment (для каждого домена)│
├─────────────────────────────────────┤
│  domain1.com  │  deploying → done   │
│  domain2.com  │  deploying → done   │
│  domain3.com  │  deploying → failed │
└─────────────────────────────────────┘
         │
         ▼
┌─────────────────┐
│  DeployService  │
│  - SSH/SFTP     │
│  - Nginx config │
│  - Let's Encrypt│
└─────────────────┘
```

## Документация

- [INSTALL.md](INSTALL.md) - Подробная инструкция по установке
- [SERVER_SETUP.md](SERVER_SETUP.md) - Настройка целевых серверов для деплоя

## Зависимости

### PHP пакеты (composer)
- Laravel 12.x
- phpseclib/phpseclib ^3.0 (SSH/SFTP)

### Внешние API
- Dynadot API (покупка доменов, DNS)
- OpenAI API (ChatGPT для генерации кода)
