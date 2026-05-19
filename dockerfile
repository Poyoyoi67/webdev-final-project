FROM php:8.5-cli

WORKDIR /var/www/html


RUN apt-get update && apt-get install -y git unzip \
    && docker-php-ext-install pdo pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . .

RUN composer install

EXPOSE 8000

CMD ["symfony", "server:start", "--port=8000", "--no-tls"]