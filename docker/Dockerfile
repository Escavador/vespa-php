FROM php:7.3-fpm AS composer

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /build

COPY . /build

RUN apt update \
    && rm /etc/apt/preferences.d/no-debian-php \
    && apt update -yqq \
    && apt install -y --force-yes --no-install-recommends \
    build-essential \
    curl \
    git \
    locales

RUN composer install

FROM php:7.3-fpm

WORKDIR /var/www

SHELL ["/bin/bash", "-c"]

# Install the xdebug extension
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Copy xdebug configration for remote debugging
ADD ./docker/php/xdebug.ini /usr/local/etc/php/conf.d

COPY ./docker/run.sh /usr/local/bin/run-server

RUN chmod +x /usr/local/bin/run-server

COPY ./docker/codestyle.sh /usr/local/bin/codestyle

RUN chmod +x /usr/local/bin/codestyle

# Coloca o código do projeto na imagem
COPY --chown=www-data . /var/www

COPY --from=composer /build/vendor .

CMD        [ "run-server" ]
