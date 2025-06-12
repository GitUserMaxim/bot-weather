FROM php:8.2-cli

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    libzip-dev libpq-dev curl \
    && docker-php-ext-install zip pdo pdo_pgsql

# Установка composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Создание рабочей директории
WORKDIR /app
COPY . .

# Composer (если нужен)
RUN composer install || true

# Открываем порт
EXPOSE 10000

# Запуск встроенного сервера PHP (важно!)
CMD ["php", "-S", "0.0.0.0:10000", "index.php"]
