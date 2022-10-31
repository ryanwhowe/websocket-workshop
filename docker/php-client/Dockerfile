FROM composer AS composertemp

COPY composer.* /app/

RUN composer install

FROM php:7.4-cli-bullseye

COPY . .

COPY --from=composertemp /app/vendor/ vendor/
