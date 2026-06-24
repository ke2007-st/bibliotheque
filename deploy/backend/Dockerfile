FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx \
    && docker-php-ext-install pdo pdo_mysql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'session.cookie_httponly = 1'; \
        echo 'session.use_strict_mode = 1'; \
        echo 'expose_php = Off'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
    } >> "$PHP_INI_DIR/conf.d/security.ini"

WORKDIR /var/www/html

COPY src/api ./api
COPY deploy/backend/nginx-api.conf.template /etc/nginx/templates/default.conf.template
COPY deploy/backend/start.sh /start.sh
RUN chmod +x /start.sh \
    && mkdir -p /run/nginx

EXPOSE 8080

CMD ["/start.sh"]
