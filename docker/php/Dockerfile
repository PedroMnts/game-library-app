FROM php:8.3-fpm-alpine

# Paquetes necesarios y extensiones de PHP (pdo_pgsql para PostgreSQL)
RUN apk add --no-cache \
      bash git unzip icu-dev libpq-dev oniguruma-dev autoconf make g++ \
  && docker-php-ext-install intl opcache pdo pdo_pgsql \
  && rm -rf /var/cache/apk/*

# Composer (global)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Usuario no root (mapea con tu UID/GID para evitar problemas de permisos)
ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} app && adduser -D -G app -u ${UID} app
USER app

WORKDIR /var/www/html
