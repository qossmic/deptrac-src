FROM php:8.1-cli
RUN apt-get update && apt-get install -y git zip && apt-get clean
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && pecl clear-cache
WORKDIR /var/www/deptrac
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN git config --global --add safe.directory /var/www/deptrac
COPY ./docker/php/overrides.ini "$PHP_INI_DIR/conf.d/90-dev-overrides.ini"

CMD ["bash"]
