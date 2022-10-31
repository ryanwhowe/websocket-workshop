FROM composer AS composertemp

COPY composer.* /app/

RUN composer install

FROM php:7.4-bullseye

COPY . .

COPY --from=composertemp /app/vendor/ vendor/

RUN apt-get update && \
  apt-get install -y --no-install-recommends \
    build-essential \
    ca-certificates \
    curl \
    expat \
    git \
    libbz2-dev \
    libffi-dev \
    libsnappy-dev \
    libssl-dev \
    libunwind-dev \
    libzmq3-dev

# Build PHP ZMQ
RUN git clone https://github.com/zeromq/php-zmq/ /tmp/php-zmq && \
  cd /tmp/php-zmq && \
  git reset --hard ee5fbc693f07b2d6f0d9fd748f131be82310f386 && \
  phpize && \
  ./configure && \
  make && \
  make test && \
  make install && \
  docker-php-ext-enable zmq && \
  rm -rf /tmp/php-zmq
