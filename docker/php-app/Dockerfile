FROM composer AS composertemp

COPY composer.* /app/

RUN composer install

FROM php:7.2

COPY . .

COPY --from=composertemp /app/vendor/ vendor/

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        build-essential \
        libzmq3-dev \
    && apt-get remove -y wget build-essential \
    && rm -rf ~/.cache \
    && rm -rf /var/lib/apt/lists/*

# We need to install and enable the zmq php extension
RUN pecl install zmq-beta && \
    docker-php-ext-enable zmq
