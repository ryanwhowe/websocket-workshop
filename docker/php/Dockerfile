FROM composer AS composer

COPY composer.* /app/

RUN composer install --dev

FROM php:7.2-cli

COPY . .

COPY --from=composer /app/vendor/ vendor/
