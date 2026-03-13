#!/bin/sh
set -e

# When the container starts normally (via supervisord) we wait for the HR service
# to respond before running the cache warmup command.
if [ "$1" = "/usr/bin/supervisord" ] || [ "$1" = "supervisord" ]; then
    if [ "${DISABLE_CACHE_WARMUP:-false}" != "true" ]; then
        HR_BASE="${HR_SERVICE_URL:-http://hr-service}"
        HEALTH_PATH="${HR_SERVICE_HEALTH_PATH:-/api/health}"

        case "$HEALTH_PATH" in
            http://*|https://*)
                HEALTH_URL="$HEALTH_PATH"
                ;;
            *)
                HR_BASE_STRIPPED="${HR_BASE%/}"
                HEALTH_URL="${HR_BASE_STRIPPED}${HEALTH_PATH}"
                ;;
        esac

        MAX_ATTEMPTS="${HR_SERVICE_WAIT_ATTEMPTS:-40}"
        SLEEP_SECONDS="${HR_SERVICE_WAIT_INTERVAL:-3}"

        echo "Waiting for HR Service at ${HEALTH_URL} (max ${MAX_ATTEMPTS} attempts)..."
        attempt=1
        until curl -sf "$HEALTH_URL" >/dev/null; do
            if [ "$attempt" -ge "$MAX_ATTEMPTS" ]; then
                echo "HR Service did not become ready after ${MAX_ATTEMPTS} attempts."
                exit 1
            fi
            attempt=$((attempt + 1))
            sleep "$SLEEP_SECONDS"
        done

        echo "HR Service is reachable. Running cache warmup..."
        php artisan cache:warmup
    else
        echo "Skipping cache warmup because DISABLE_CACHE_WARMUP=true"
    fi
fi

exec "$@"
