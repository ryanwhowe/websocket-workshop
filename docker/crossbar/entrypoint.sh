#!/usr/bin/env bash

rm -f /app/.crossbar/node.pid

# Hand off to the CMD
exec "$@"
