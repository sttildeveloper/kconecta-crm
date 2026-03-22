#!/bin/sh
set -e

cd /var/www/html

mkdir -p public/img/uploads public/video/uploads storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache public/img/uploads public/video/uploads
chmod -R ug+rwX storage bootstrap/cache public/img/uploads public/video/uploads
umask 0002

php artisan optimize:clear
php artisan config:cache

exec apache2-foreground
