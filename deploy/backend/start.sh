#!/bin/sh
set -e

PORT="${PORT:-8080}"
sed "s/__PORT__/${PORT}/" /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

php-fpm -D
exec nginx -g 'daemon off;'
