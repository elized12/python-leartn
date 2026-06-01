#!/usr/bin/env sh
set -e

QUEUE_NAME="${1:-default}"
WORKER_COUNT="${2:-1}"
TIMEOUT="${3:-120}"

case "$WORKER_COUNT" in
    ''|*[!0-9]*) WORKER_COUNT=1 ;;
esac

if [ "$WORKER_COUNT" -lt 1 ]; then
    WORKER_COUNT=1
fi

PIDS=""

shutdown() {
    for PID in $PIDS; do
        kill "$PID" 2>/dev/null || true
    done

    wait
}

trap shutdown INT TERM

INDEX=1
while [ "$INDEX" -le "$WORKER_COUNT" ]; do
    php artisan queue:work --queue="$QUEUE_NAME" --sleep=1 --tries=1 --timeout="$TIMEOUT" &
    PIDS="$PIDS $!"
    INDEX=$((INDEX + 1))
done

wait
