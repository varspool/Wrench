#!/usr/bin/env bash

set -e

function cleanup() {
    popd

    if [[ ! -z "$http" ]]; then
        echo "Stopping HTTP server" >&2
        kill "$http" &
    fi

    if [[ ! -z "$ws" ]]; then
        echo "Stopping Websocket server" >&2
        kill "$ws" &
    fi
}

trap cleanup EXIT

pushd "$( dirname "${BASH_SOURCE[0]}" )"

echo "Starting HTTP server" >&2
( php -S 127.0.0.1:8080 -t coffeescript ) &
http=$!

echo "Starting Websocket server" >&2
( php server.php ) & # runs on :8000
ws=$!

echo "Open http://127.0.0.1:8080/index.html in a browser to view page"

wait
