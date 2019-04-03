FROM composer AS composer

COPY composer.* /app/

RUN composer install --ignore-platform-reqs

FROM php:7.2-cli

COPY . .

COPY --from=composer /app/vendor/ vendor/

LABEL name="phpyorkshire/crossbar-php"

# We need python as well so lets merge the python 3-slim image
COPY --from=python:3-slim / /

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        expat \
        build-essential \
        libssl-dev \
        libffi-dev \
        libzmq3-dev \
        libunwind-dev \
        libsnappy-dev \
        libbz2-dev \
    && pip install --no-cache-dir --upgrade pip \
    && pip install crossbar \
    && apt-get remove -y wget expat build-essential \
    && rm -rf ~/.cache \
    && rm -rf /var/lib/apt/lists/*

# Test crossbar works, means we can exit early if not
RUN crossbar version

# We need to install and enable the zmq php extension
RUN pecl install zmq-beta && \
    pecl install redis && \
    docker-php-ext-enable zmq && \
    docker-php-ext-enable redis

WORKDIR /app

COPY entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

CMD ["crossbar", "start" , "--logdir=/app/logs/", "--logtofile", "--loglevel=debug"]
