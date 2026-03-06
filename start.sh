#!/usr/bin/env bash
set -euo pipefail

sudo service apache2 start
sudo service mariadb start

cd "$(dirname "$0")"

trap 'kill 0' SIGINT SIGTERM EXIT

/usr/bin/php -S localhost:8080 -t Frontend/public &
/usr/bin/php -S localhost:8000 -t API/public &

wait