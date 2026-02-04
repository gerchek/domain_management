# Установка Domain Management System

## Требования

- Docker и Docker Compose
- Git
- Сервер с Linux (Ubuntu/Debian) для продакшена

---

## Часть 1: Локальная установка (для разработки)

### 1. Клонировать репозиторий

```bash
git clone <URL_РЕПОЗИТОРИЯ> domain_management
cd domain_management
```

### 2. Настроить окружение

```bash
cp .env.example .env
nano .env
```

Изменить в `.env`:
```env
DB_PASSWORD=your_password    # придумать пароль для базы данных
```

### 3. Запустить контейнеры

```bash
docker compose up -d
```

### 4. Установить Laravel

```bash
# Установить PHP зависимости
docker compose exec app composer install

# Сгенерировать ключ приложения
docker compose exec app php artisan key:generate

# Подождать 30 сек пока MySQL запустится, затем выполнить миграции
docker compose exec app php artisan migrate

# Создать администратора
docker compose exec app php artisan db:seed

# Перезапустить queue worker
docker compose restart queue
```

### 5. Готово

Открыть: http://localhost:8080

Вход:
- Email: `admin@admin.com`
- Password: `password`

phpMyAdmin: http://localhost:8081

---

## Часть 2: Установка на сервер с доменом (продакшен)

### Шаг 1: Подготовить сервер

Подключиться к серверу по SSH:
```bash
ssh root@IP_СЕРВЕРА
```

Установить Docker (если не установлен):
```bash
curl -fsSL https://get.docker.com | sh
```

Установить Git:
```bash
apt update && apt install -y git
```

### Шаг 2: Направить домен на сервер

В панели регистратора домена (Dynadot, Namecheap, GoDaddy и т.д.) добавить DNS запись:

```
Тип записи: A
Имя/Host: @ (или оставить пустым для корневого домена)
Значение/Points to: IP_вашего_сервера (например: 203.0.113.1)
TTL: 3600 (или Auto)
```

Если нужен поддомен (например panel.example.com):
```
Тип записи: A
Имя/Host: panel
Значение/Points to: IP_вашего_сервера
```

**Подождать 5-30 минут** пока DNS обновится.

Проверить что домен указывает на сервер:
```bash
ping yourdomain.com
```

### Шаг 3: Клонировать проект на сервер

```bash
cd /var/www
git clone <URL_РЕПОЗИТОРИЯ> domain_management
cd domain_management
```

### Шаг 4: Настроить окружение

```bash
cp .env.example .env
nano .env
```

Изменить в `.env`:
```env
APP_URL=http://yourdomain.com    # ваш домен
DB_PASSWORD=strong_password_123   # надёжный пароль для базы данных
```

### Шаг 5: Запустить в продакшен режиме

```bash
docker compose -f docker-compose.prod.yml up -d
```

### Шаг 6: Установить Laravel

```bash
# Установить зависимости
docker compose exec app composer install --optimize-autoloader --no-dev

# Сгенерировать ключ
docker compose exec app php artisan key:generate

# Подождать 30 сек пока MySQL запустится
sleep 30

# Выполнить миграции
docker compose exec app php artisan migrate --force

# Создать администратора
docker compose exec app php artisan db:seed

# Перезапустить queue
docker compose restart queue

# Оптимизировать для продакшена
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### Шаг 7: Проверить

Открыть в браузере: `http://yourdomain.com`

Вход:
- Email: `admin@admin.com`
- Password: `password`

**Важно:** После первого входа сменить пароль администратора!

---

## Часть 3: Настройка HTTPS (SSL сертификат)

### Шаг 1: Остановить контейнеры

```bash
cd /var/www/domain_management
docker compose -f docker-compose.prod.yml down
```

### Шаг 2: Установить Certbot и получить сертификат

```bash
apt install -y certbot
certbot certonly --standalone -d yourdomain.com
```

Certbot спросит email и попросит согласиться с условиями. После успешного получения сертификаты будут в `/etc/letsencrypt/live/yourdomain.com/`

### Шаг 3: Настроить Nginx для HTTPS

```bash
nano docker/nginx/default.conf
```

Заменить ВСЁ содержимое на:

```nginx
server {
    listen 80;
    server_name yourdomain.com;

    # Редирект HTTP -> HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name yourdomain.com;

    # SSL сертификаты
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # SSL настройки
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /var/www/public;
    index index.php;

    # Логи
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Важно:** Заменить `yourdomain.com` на ваш реальный домен (3 места в файле).

### Шаг 4: Обновить APP_URL в .env

```bash
nano .env
```

Изменить:
```env
APP_URL=https://yourdomain.com
```

### Шаг 5: Запустить контейнеры

```bash
docker compose -f docker-compose.prod.yml up -d
```

### Шаг 6: Очистить кэш

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan config:cache
```

### Шаг 7: Проверить

Открыть: `https://yourdomain.com`

Должен быть зелёный замок в браузере.

---

## Автоматическое обновление SSL сертификата

Сертификаты Let's Encrypt действуют 90 дней. Настроить автообновление:

```bash
crontab -e
```

Добавить строку:
```
0 3 * * * certbot renew --quiet && docker restart domain_nginx
```

Это будет проверять сертификат каждый день в 3:00 и обновлять если нужно.

---

## Сравнение режимов

| Параметр | Локальный | Продакшен |
|----------|-----------|-----------|
| Файл | `docker-compose.yml` | `docker-compose.prod.yml` |
| Команда | `docker compose up -d` | `docker compose -f docker-compose.prod.yml up -d` |
| Порт | 8080 | 80/443 |
| URL | http://localhost:8080 | http(s)://yourdomain.com |
| phpMyAdmin | Да (порт 8081) | Нет |
| MySQL извне | Да (порт 3307) | Нет |
| Для чего | Разработка | Боевой сервер |

---

## Основные команды

```bash
# Запустить (локально)
docker compose up -d

# Запустить (продакшен)
docker compose -f docker-compose.prod.yml up -d

# Остановить
docker compose down
# или для продакшена:
docker compose -f docker-compose.prod.yml down

# Перезапустить
docker compose restart
# или для продакшена:
docker compose -f docker-compose.prod.yml restart

# Посмотреть логи
docker compose logs -f

# Посмотреть логи конкретного контейнера
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f queue

# Войти в контейнер приложения
docker compose exec app bash

# Выполнить artisan команду
docker compose exec app php artisan <команда>

# Очистить все кэши
docker compose exec app php artisan optimize:clear
```

---

## Обновление проекта

```bash
cd /var/www/domain_management

# Остановить
docker compose -f docker-compose.prod.yml down

# Получить обновления
git pull

# Запустить
docker compose -f docker-compose.prod.yml up -d

# Обновить зависимости
docker compose exec app composer install --optimize-autoloader --no-dev

# Выполнить новые миграции
docker compose exec app php artisan migrate --force

# Очистить и пересоздать кэш
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

---

## После установки

1. **Сменить пароль администратора** - зайти в профиль и изменить пароль
2. **Settings** → Добавить Dynadot API ключ
3. **Servers** → Добавить сервер для деплоя сайтов
4. **Buyers** → Создать байера

Для настройки целевых серверов (куда деплоятся сайты) см. [SERVER_SETUP.md](SERVER_SETUP.md)

---

## Работа с базой данных на сервере

На сервере phpMyAdmin отключен для безопасности. Есть 2 способа работать с базой:

### Способ 1: Командная строка MySQL (на сервере)

```bash
# Подключиться к серверу
ssh root@IP_СЕРВЕРА

# Войти в MySQL
cd /var/www/domain_management
docker compose exec mysql mysql -u root -p
# Ввести пароль из .env (DB_PASSWORD)

# Выбрать базу
USE domain_management;

# Примеры команд:
SHOW TABLES;
SELECT * FROM users;
SELECT * FROM servers;
SELECT * FROM domains LIMIT 10;
```

### Способ 2: SSH туннель + локальный клиент (рекомендуется)

Этот способ позволяет использовать удобные программы (DBeaver, MySQL Workbench, HeidiSQL, TablePlus) на своём компьютере.

**Шаг 1:** Создать SSH туннель

```bash
ssh -L 3307:localhost:3306 root@IP_СЕРВЕРА
```

Эта команда пробрасывает порт 3306 с сервера на локальный порт 3307.

**Шаг 2:** Подключиться локальным клиентом

Настройки подключения:
```
Host: 127.0.0.1
Port: 3307
User: root
Password: <пароль из .env>
Database: domain_management
```

**Или одной командой в DBeaver/другом клиенте:**

Использовать SSH туннель встроенный в клиент:
- SSH Host: IP_СЕРВЕРА
- SSH User: root
- SSH Password/Key: ваш SSH ключ
- Database Host: localhost
- Database Port: 3306
- Database User: root
- Database Password: пароль из .env

### Полезные MySQL команды

```sql
-- Показать все таблицы
SHOW TABLES;

-- Показать структуру таблицы
DESCRIBE users;
DESCRIBE domains;

-- Посмотреть пользователей
SELECT id, name, email, role FROM users;

-- Посмотреть серверы
SELECT id, name, ip_address FROM servers;

-- Посмотреть домены
SELECT id, name, status FROM domains LIMIT 20;

-- Посмотреть деплойменты
SELECT id, domain, status FROM domain_deployments ORDER BY id DESC LIMIT 20;

-- Бэкап базы данных
-- (выполнить на сервере, не в MySQL)
docker compose exec mysql mysqldump -u root -p domain_management > backup.sql

-- Восстановить из бэкапа
docker compose exec -T mysql mysql -u root -p domain_management < backup.sql
```

---

## Решение проблем

### Ошибка "Access denied" к MySQL

Пароль в `.env` не совпадает с паролем в базе. Решение:
```bash
docker compose down -v  # Удалит данные базы!
docker compose up -d
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

### Бесконечные редиректы (ERR_TOO_MANY_REDIRECTS)

Очистить сессии и кэш:
```bash
docker compose exec app php artisan optimize:clear
docker compose exec mysql mysql -u root -p<PASSWORD> <DB_NAME> -e "TRUNCATE TABLE sessions;"
```
Также очистить cookies в браузере.

### Сайт не открывается после настройки домена

1. Проверить что DNS обновился: `ping yourdomain.com`
2. Проверить что контейнеры запущены: `docker compose ps`
3. Проверить логи: `docker compose logs -f nginx`

### Ошибка SSL сертификата

Убедиться что:
1. Домен указывает на сервер
2. Порт 80 и 443 открыты в firewall
3. Путь к сертификатам правильный в nginx конфиге
