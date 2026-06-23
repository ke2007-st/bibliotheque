FROM php:8.2-fpm

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN { \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_samesite = Strict'; \
    echo 'session.use_strict_mode = 1'; \
    echo 'expose_php = Off'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
} >> "$PHP_INI_DIR/conf.d/security.ini"

WORKDIR /var/www/html
