FROM php:8.1-cli

RUN apt-get update \ 
    && apt-get install -y git zip \
    && apt-get clean \
    && pecl install xdebug \
    && docker-php-ext-install opcache \
    && docker-php-ext-enable xdebug \
    && pecl clear-cache \
    && git config --global --add safe.directory /var/www/deptrac

COPY --from=composer/composer:2-bin /composer /usr/bin/composer
COPY ./docker/php/overrides.ini "$PHP_INI_DIR/conf.d/90-dev-overrides.ini"

WORKDIR /var/www/deptrac
CMD ["bash"]
