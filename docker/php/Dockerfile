FROM php:8.2.25-fpm-alpine3.19

RUN apk add --no-cache $PHPIZE_DEPS postgresql-dev linux-headers \
    && apk add --no-cache libpq \
    && pecl install xdebug-3.3.0 \
    && docker-php-ext-install pdo_pgsql pgsql \
    && docker-php-ext-enable xdebug

RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN rm -rf composer-setup.php

COPY ./ini/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY ./ini/php.ini /usr/local/etc/php/conf.d/docker-php.ini

RUN addgroup -S balance && adduser -S balance -G balance -u 1000

USER balance
