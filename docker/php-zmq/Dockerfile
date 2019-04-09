FROM composer AS composertemp

COPY composer.* /app/

RUN composer install --ignore-platform-reqs

FROM php:7.2-cli

COPY . .

COPY --from=composertemp /app/vendor/ vendor/

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        build-essential \
        curl \
        libzmq3-dev \
    && apt-get remove -y wget build-essential \
    && rm -rf ~/.cache \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install zmq-beta \
    && docker-php-ext-enable zmq
