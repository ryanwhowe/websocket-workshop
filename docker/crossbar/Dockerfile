# Start with a base image
FROM python:3-slim

LABEL name="phpyorkshire/crossbar"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        expat \
        build-essential \
        libssl-dev \
        libffi-dev \
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

WORKDIR /app

COPY entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

CMD ["crossbar", "start" , "--logdir=/app/logs/", "--logtofile", "--loglevel=debug"]
