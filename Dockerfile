# Dockerfile
FROM php:8.3-cli-alpine

# balíčky + SQLite driver
RUN apk add --no-cache git unzip sqlite-dev \
  && docker-php-ext-install pdo pdo_sqlite

WORKDIR /app

# composer do obrazu
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

EXPOSE 8080
