#!/usr/bin/env bash

rm -f /app/.crossbar/node.pid
rm -f /app/logs/*

# Hand off to the CMD
exec "$@"
