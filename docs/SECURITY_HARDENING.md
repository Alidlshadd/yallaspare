# YallaSpare Security Hardening Runbook

This app now includes Laravel-side security headers, CORS hardening, throttles, SQL-safe search helpers, an app-level IPS, admin two-factor verification, dependency audit checks, and regression tests.

## Required production environment

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_LEVEL=warning

CORS_ALLOWED_ORIGINS=https://your-domain.com,https://www.your-domain.com
SESSION_SECURE_COOKIE=true

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

ADMIN_TWO_FACTOR_ENABLED=true
ADMIN_TWO_FACTOR_CODE_TTL=10

INTRUSION_PREVENTION_ENABLED=true
INTRUSION_PREVENTION_WINDOW_MINUTES=10
INTRUSION_PREVENTION_BLOCK_MINUTES=30
INTRUSION_PREVENTION_MAX_SCORE=8
```

## Cloudflare/WAF rules

- Put the site behind Cloudflare proxy.
- Enable WAF managed rules, bot fight mode, and DDoS protection.
- Rate-limit `/login`, `/register`, `/forgot-password`, `/api/*`, `/checkout*`, and `/admin/*`.
- Add a stricter challenge rule for suspicious countries or ASNs if the store is regional.
- Block requests for `/.env`, `/wp-admin`, `/wp-login.php`, `/phpmyadmin`, `/vendor/phpunit`, and `/storage/logs`.

## Server-level limits

Use Nginx/Apache rate limiting in addition to Laravel throttles. Laravel only receives traffic after the web server accepts it.

Recommended Nginx concepts:

```nginx
limit_req_zone $binary_remote_addr zone=global:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=auth:10m rate=5r/m;

location / {
    limit_req zone=global burst=30 nodelay;
}

location ~ ^/(login|register|forgot-password) {
    limit_req zone=auth burst=5 nodelay;
}
```

## Database least privilege

Do not run the app as MySQL `root`.

Example:

```sql
CREATE USER 'yallaspare_app'@'localhost' IDENTIFIED BY 'use-a-long-random-password';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES
ON yallaspare.* TO 'yallaspare_app'@'localhost';
FLUSH PRIVILEGES;
```

Use a separate migration/deployment user for schema changes.

## Backups

- Run `scripts/db-backup.ps1` daily from Windows Task Scheduler.
- Store backups outside the web server.
- Encrypt offsite backups.
- Test restore monthly using `scripts/db-restore.ps1`.

## Monitoring

Alert on:

- HTTP 500 spikes.
- HTTP 429 spikes.
- Repeated failed logins.
- Admin two-factor failures.
- IPS warnings in logs.
- CPU, RAM, disk, and database connection spikes.

## Release checks

Run before deployment:

```bash
composer audit
npm audit --audit-level=moderate
php artisan test
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Remaining platform upgrade

Laravel 10 is end-of-life. Upgrade PHP to 8.2+ first, then upgrade Laravel to 12/13 and rerun the full test suite.
