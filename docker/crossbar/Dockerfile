# Start with a base image
FROM debian:11-slim

LABEL name="websocket-workshop/crossbar"

RUN apt-get update && \
  apt-get install -y --no-install-recommends \
    build-essential \
    python3.9-dev \
    python3-pip \
    ca-certificates \
    curl \
    expat \
    libbz2-dev \
    libffi-dev \
    libsnappy-dev \
    libssl-dev \
    libunwind-dev

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

WORKDIR /app

COPY entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

CMD ["crossbar", "start" , "--logdir=/app/logs/", "--logtofile", "--loglevel=debug"]
