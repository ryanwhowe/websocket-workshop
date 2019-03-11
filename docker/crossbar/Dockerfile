# Start with a base image
FROM php:7.2-cli

LABEL name="phpyorkshire/crossbar"

# We need python as well so lets merge the python 3-slim image
COPY --from=python:3-slim / /

# We need to install and enable the zmq php extension
RUN apt-get -y update && \
	apt-get install -y tree libzmq3-dev && \
	rm -rf /var/lib/apt/lists/*

RUN pecl install zmq-beta && \
    docker-php-ext-enable zmq

# Cross bar installation
RUN pip install crossbar

WORKDIR /app

COPY entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

CMD ["crossbar", "start" , "--logdir=/app/logs/", "--logtofile", "--loglevel=debug"]
