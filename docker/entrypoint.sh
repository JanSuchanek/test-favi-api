#!/usr/bin/env sh
set -e
composer install --no-interaction
php bin/console doctrine:migrations:migrate -n
php -S 0.0.0.0:8080 -t public