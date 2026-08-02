#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
command -v php >/dev/null 2>&1 || { echo "PHP 8.1+ is required." >&2; exit 1; }
echo "Game Account Lookup tester: http://127.0.0.1:8080"
php -S 127.0.0.1:8080 -t template
