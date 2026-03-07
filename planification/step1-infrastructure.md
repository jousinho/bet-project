# Step 1 - Infrastructure Setup

## 1. Estructura de directorios

```
project/
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   └── nginx/
│       └── default.conf
├── app/                  # código Symfony
├── docker-compose.yml
└── .env.example
```

## 2. Dockerfile (docker/php/Dockerfile)

```dockerfile
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        intl \
        bcmath \
        opcache

RUN docker-php-ext-configure zip \
    && docker-php-ext-install zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/symfony.ini

WORKDIR /var/www/html
```

## 3. php.ini (docker/php/php.ini)

```ini
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 60
date.timezone = Europe/Madrid

opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
```

## 4. Nginx (docker/nginx/default.conf)

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass php:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

## 5. docker-compose.yml

```yaml
services:
  php:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    container_name: bet-project-php
    volumes:
      - ./app:/var/www/html
    networks:
      - app
    depends_on:
      postgres:
        condition: service_healthy

  php-cli:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    container_name: bet-project-php-cli
    command: ["tail", "-f", "/dev/null"]
    volumes:
      - ./app:/var/www/html
    networks:
      - app
    depends_on:
      postgres:
        condition: service_healthy

  nginx:
    image: nginx:alpine
    container_name: bet-project-nginx
    ports:
      - "8080:80"
    volumes:
      - ./app:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - app
    depends_on:
      - php

  postgres:
    image: postgres:16-alpine
    container_name: bet-project-postgres
    environment:
      POSTGRES_USER: ${POSTGRES_USER:-app}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-secret}
      POSTGRES_DB: ${POSTGRES_DB:-bet_project}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    networks:
      - app
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER:-app} -d ${POSTGRES_DB:-bet_project}"]
      interval: 5s
      timeout: 5s
      retries: 5

  postgres_test:
    image: postgres:16-alpine
    container_name: bet-project-postgres-test
    environment:
      POSTGRES_USER: ${POSTGRES_USER:-app}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-secret}
      POSTGRES_DB: ${POSTGRES_DB_TEST:-bet_project_test}
    volumes:
      - postgres_test_data:/var/lib/postgresql/data
    ports:
      - "5433:5432"
    networks:
      - app
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER:-app} -d ${POSTGRES_DB_TEST:-bet_project_test}"]
      interval: 5s
      timeout: 5s
      retries: 5

networks:
  app:
    driver: bridge

volumes:
  postgres_data:
  postgres_test_data:
```

## 6. .env.example

```env
POSTGRES_USER=app
POSTGRES_PASSWORD=secret
POSTGRES_DB=bet_project
POSTGRES_DB_TEST=bet_project_test
```

## 7. Instalar Symfony

Primero levantar solo postgres para que esté disponible durante la instalación:

```bash
cp .env.example .env
docker compose up -d --build postgres
```

Crear el proyecto Symfony en `app/` (debe estar vacío):

```bash
docker compose run --rm php-cli composer create-project symfony/skeleton . "8.0.*"
```

Instalar dependencias adicionales:

```bash
docker compose exec php-cli composer require \
    doctrine/doctrine-bundle \
    doctrine/doctrine-migrations-bundle \
    doctrine/orm \
    symfony/http-client \
    symfony/property-access \
    symfony/serializer \
    symfony/twig-bundle \
    symfony/uid

docker compose exec php-cli composer require --dev \
    phpunit/phpunit \
    symfony/browser-kit
```

## 8. Configurar DATABASE_URL

En `app/.env`, ajustar la variable que genera Symfony por defecto:

```env
DATABASE_URL="postgresql://app:secret@postgres:5432/bet_project?serverVersion=16&charset=utf8"
```

Para tests, en `app/.env.test`:

```env
DATABASE_URL="postgresql://app:secret@postgres_test:5432/bet_project_test?serverVersion=16&charset=utf8"
```

## 9. Levantar todo

```bash
docker compose up -d
```

Verificar que todo está en pie:

```bash
docker compose ps
```
