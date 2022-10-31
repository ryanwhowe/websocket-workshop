FROM composer AS composertemp

COPY composer.* /app/

RUN composer install

FROM php:7.4-cli-bullseye

COPY . .

COPY --from=composertemp /app/vendor/ vendor/

LABEL name="websocket-workshop/crossbar-php"

# Install stuff we need for thr es
RUN apt-get update && \
  apt-get install -y --no-install-recommends \
    build-essential \
    python3.9-dev \
    python3-pip \
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

# Install crossbar WAMP router
RUN pip3 install --no-cache-dir --upgrade pip && \
  pip3 --version && \
  pip3 install crossbar && \
  apt-get remove -y wget expat build-essential && \
  apt-get autoremove -y && \
  rm -rf ~/.cache && \
  rm -rf /var/lib/apt/lists/*

# Test crossbar works, means we can exit early if not
RUN crossbar version

# Also add Redis
RUN pecl install redis && \
    docker-php-ext-enable redis

WORKDIR /app

COPY entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

CMD ["crossbar", "start" , "--logdir=/app/logs/", "--logtofile", "--loglevel=debug"]
