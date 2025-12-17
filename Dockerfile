# Используем официальный образ PHP с Apache
FROM php:8.2-apache

# Обновляем систему и устанавливаем расширения PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite

# Копируем код сайта в контейнер
COPY . /var/www/html/

# Настраиваем права доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Указываем порт
EXPOSE 80

# Команда запуска Apache
CMD ["apache2-foreground"]