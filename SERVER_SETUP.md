# Настройка сервера для деплоя сайтов

Минимальная настройка сервера для автоматического деплоя сайтов из Domain Management System.

Всё работает через Docker - на сервере запускается контейнер с nginx + php-fpm.

---

## Требования к серверу

| Параметр | Минимум |
|----------|---------|
| ОС | Ubuntu 20.04+ / Debian 11+ |
| RAM | 1 GB |
| Диск | 20 GB |
| Порты | 22, 80, 443 |
| Пакеты | docker, certbot, zip, unzip |

---

## Настройка сервера (один раз)

### 1. Подключиться к серверу

```bash
ssh root@IP_СЕРВЕРА
```

### 2. Запустить команды установки

```bash
# Обновить систему
apt update && apt upgrade -y

# Установить Docker
curl -fsSL https://get.docker.com | sh
systemctl enable docker
systemctl start docker

# Установить Certbot
apt install -y certbot

# Установить zip/unzip (для Offers - распаковка ZIP архивов)
apt install -y zip unzip

# Создать директории
mkdir -p /var/www
mkdir -p /var/www/nginx-sites

# Создать docker-compose.yml
cat > /var/www/docker-compose.yml << 'EOF'
services:
  web:
    image: webdevops/php-nginx:8.2
    container_name: sites_web
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/www:/var/www
      - /var/www/nginx-sites:/etc/nginx/conf.d/sites
      - /etc/letsencrypt:/etc/letsencrypt:ro
    environment:
      - PHP_MEMORY_LIMIT=256M
      - PHP_MAX_EXECUTION_TIME=300
EOF

# Запустить контейнер
cd /var/www
docker compose pull
docker compose up -d

# Добавить include для sites в nginx (внутри контейнера)
docker exec sites_web sh -c 'echo "include /etc/nginx/conf.d/sites/*.conf;" > /etc/nginx/conf.d/sites.conf'
docker exec sites_web nginx -s reload

# Настроить файрвол
apt install -y ufw
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
```

### 3. Проверить установку

```bash
docker ps
# Должен показать контейнер sites_web
```

---

## Добавление сервера в Domain Management

1. Войти в админку
2. Перейти в **Servers**
3. Нажать **Add Server**
4. Заполнить:
   - **Name:** Название сервера
   - **IP Address:** IP адрес
   - **SSH Username:** `root`
   - **SSH Password:** пароль или **SSH Private Key**
   - **SSH Port:** 22
   - **Max Domains:** максимум доменов на сервере

---

## Как работает деплой

1. Система подключается к серверу по SSH
2. Создаёт директорию `/var/www/domain.com/public_html/`
3. Загружает сгенерированные файлы сайта
4. Создаёт nginx конфиг в `/var/www/nginx-sites/`
5. Перезагружает nginx в Docker контейнере
6. Получает SSL сертификат через Certbot

---

## Структура на сервере

```
/var/www/
├── docker-compose.yml
├── nginx-sites/
│   ├── domain1.com.conf
│   └── domain2.com.conf
├── domain1.com/
│   ├── Xn4z.php              # Tracking файл (вне public_html для безопасности)
│   ├── logs/                  # Логи
│   └── public_html/
│       ├── index.php          # Palladium фильтр (или White site если Offer)
│       ├── mainpage.php       # White site (если Palladium)
│       ├── newspage/          # Black site
│       │   └── index.php
│       ├── css/
│       └── js/
└── domain2.com/
    └── ...
```

---

## Типы Black Site деплоя

### Palladium
- Использует Palladium фильтр в `index.php`
- White site перемещается в `mainpage.php`
- Требует настройку Keitaro (khost, kapitoken)
- Admin создаёт Palladium конфиги, Buyer выбирает из списка

### Offer
- ZIP архив распаковывается напрямую в `newspage/`
- Не использует Palladium фильтр
- White site остаётся в `index.php`
- Требует установленный `unzip` на сервере
- Admin загружает ZIP архивы, Buyer выбирает из списка

---

## Перезапуск после рестарта сервера

Контейнер настроен на автозапуск (`restart: unless-stopped`), но если нужно перезапустить вручную:

```bash
cd /var/www
docker compose up -d
docker exec sites_web sh -c 'echo "include /etc/nginx/conf.d/sites/*.conf;" > /etc/nginx/conf.d/sites.conf'
docker exec sites_web nginx -s reload
```

---

## Лимиты Let's Encrypt

| Лимит | Значение |
|-------|----------|
| Сертификатов на домен | 50 в неделю |
| Неудачных запросов | 5 в час |

При большом количестве доменов используйте несколько серверов.
