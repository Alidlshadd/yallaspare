# Storefront Hero MP4 Upload Runbook

The admin storefront hero video accepts only MP4 uploads up to 50MB.

## Laravel checks

```bash
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Permissions

Use the PHP-FPM user used by the server. On Ubuntu with Nginx this is usually
`www-data`.

```bash
sudo mkdir -p /var/www/yallaspare/storage/app/public/home/hero
sudo chown -R www-data:www-data /var/www/yallaspare/storage /var/www/yallaspare/bootstrap/cache
sudo find /var/www/yallaspare/storage /var/www/yallaspare/bootstrap/cache -type d -exec chmod 775 {} \;
sudo find /var/www/yallaspare/storage /var/www/yallaspare/bootstrap/cache -type f -exec chmod 664 {} \;
sudo -u www-data test -w /var/www/yallaspare/storage/app/public/home/hero
```

## PHP-FPM

Copy `deploy/php/99-yallaspare-uploads.ini.example`:

```bash
sudo cp deploy/php/99-yallaspare-uploads.ini.example /etc/php/8.3/fpm/conf.d/99-yallaspare-uploads.ini
sudo php-fpm8.3 -t
sudo systemctl reload php8.3-fpm
php -i | grep -E "upload_max_filesize|post_max_size|max_execution_time|max_input_time|memory_limit"
```

## Nginx

Merge `deploy/nginx/yallaspare-upload-hardening.conf.example` into the HTTPS
server block. The important values are:

```nginx
client_max_body_size 64M;
fastcgi_read_timeout 120s;
fastcgi_send_timeout 120s;
fastcgi_request_buffering on;
```

Then reload:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Troubleshooting

Laravel writes upload diagnostics to `storage/logs/laravel.log` with these
messages:

- `Storefront hero video upload attempt`
- `Storefront hero video upload failed`
- `Storefront hero video uploaded`

Useful production checks:

```bash
tail -n 200 storage/logs/laravel.log
sudo tail -n 200 /var/log/nginx/error.log
sudo journalctl -u php8.3-fpm -n 200 --no-pager
ls -la public/storage storage/app/public/home/hero
```
