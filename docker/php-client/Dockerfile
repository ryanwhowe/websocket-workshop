FROM composer AS composertemp

COPY composer.* /app/

RUN composer install

FROM php:7.2-cli

COPY . .

COPY --from=composertemp /app/vendor/ vendor/
