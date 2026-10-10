# Server security checks

Checks to run **on the production server**, by someone with SSH access. None
of them was run by the October 2026 audit, which had no shell on the server:
that audit saw the application's code and the site from the outside only. A
box ticked here is ticked by whoever ran the command, not by the audit.

Every command below is read-only unless it says otherwise, and none prints a
secret. Where a file holds secrets the command prints its permissions or a
yes/no, never its contents. Do not paste `.env`, key files or full config
dumps into tickets or chat.

Paths assume the app is at `/var/www/yallaspare`, runs as `www-data`, and uses
PHP 8.3. Adjust to the server.

## A. Security — fix what fails

### A1. Secrets and debug mode

```bash
cd /var/www/yallaspare
stat -c '%a %U:%G %n' .env                       # want 640 or 600, owned by the deploy user, group www-data
php artisan tinker --execute='echo config("app.env"), " debug=", var_export(config("app.debug"), true), PHP_EOL;'
                                                 # want: production debug=false
php artisan tinker --execute='foreach (["services.otpiq.webhook_secret","services.fib.webhook_token","services.zaincash.webhook_token"] as $k) echo $k, ": ", filled(config($k)) ? "set" : "EMPTY", PHP_EOL;'
php artisan tinker --execute='echo "online payments: ", var_export(config("payments.customer_online_payments_enabled"), true), PHP_EOL;'
                                                 # want false until the providers are signed off
```

An empty payment webhook token is safe in production (the webhook is refused),
but an empty OTPiQ secret means inbound WhatsApp is refused with 503.

### A2. Nothing private is served

From any machine, not the server:

```bash
for p in /.env /.git/HEAD /storage/logs/laravel.log /composer.json /vendor/autoload.php /artisan /phpunit.xml /storage/backups/; do
  printf '%s %s\n' "$(curl -s -o /dev/null -w '%{http_code}' https://yallaspare.com$p)" "$p"
done                                             # want 404 or 403 on every line
```

On the server, confirm the web root is `public/` and PHP runs only for
`index.php`:

```bash
nginx -T 2>/dev/null | grep -nE '^\s*(root|server_name|listen)\s'
nginx -T 2>/dev/null | grep -nE 'location.*\.php|fastcgi_pass|try_files'
```

There must be no `location ~ \.php$` that would execute an uploaded file under
`/storage/`. `deploy/nginx/yallaspare-upload-hardening.conf.example` is the
intended shape.

### A3. File ownership and permissions

```bash
cd /var/www/yallaspare
find . -path ./node_modules -prune -o -path ./vendor -prune -o -perm -o+w -type f -print | head   # want no output: nothing world-writable
find storage bootstrap/cache -maxdepth 1 -printf '%m %u:%g %p\n'                                  # writable by the PHP user, not by "other"
find storage -name '*.sql*' -printf '%m %u:%g %s bytes %p\n' | tail -5                             # backups: 600/640, never under storage/app/public
ls -ld public/storage && readlink -f public/storage                                               # must resolve to storage/app/public only
```

### A4. Open ports and services

```bash
ss -tlnp | awk 'NR==1 || /LISTEN/'               # which ports listen, and on which address
ufw status verbose 2>/dev/null || iptables -S | head -30
```

Want: 80 and 443 on all interfaces, SSH on all or restricted, and **MySQL
(3306), Redis (6379), PHP-FPM and any admin tool bound to 127.0.0.1 or a
socket only**. Anything else listening on `0.0.0.0` or `[::]` needs a reason.

```bash
systemctl list-units --type=service --state=running --no-pager | grep -vE 'systemd|dbus|cron|ssh|nginx|php|mysql|mariadb|supervisor|ufw'
                                                 # anything left is a service nobody listed
```

### A5. SSH and system updates

```bash
sshd -T 2>/dev/null | grep -E '^(permitrootlogin|passwordauthentication|pubkeyauthentication) '
                                                 # want: permitrootlogin no (or prohibit-password), passwordauthentication no
apt list --upgradable 2>/dev/null | grep -ci security   # pending security updates; want 0
systemctl is-enabled unattended-upgrades 2>/dev/null
```

### A6. TLS

```bash
echo | openssl s_client -connect yallaspare.com:443 -servername yallaspare.com 2>/dev/null | openssl x509 -noout -dates
systemctl list-timers --no-pager | grep -i certbot       # renewal is scheduled
certbot renew --dry-run                                  # (writes nothing) renewal actually works
```

From outside, TLS 1.0 and 1.1 were refused and HSTS was present on
2026-10-10.

### A7. PHP-FPM

```bash
php -i | grep -E '^(expose_php|display_errors|allow_url_include|open_basedir|disable_functions) '
php-fpm8.3 -tt 2>&1 | grep -E 'user|group|listen = |listen.owner|listen.mode|pm.max_children'
```

Want `display_errors` off, `allow_url_include` off, `expose_php` off, the
pool running as an unprivileged user and listening on a socket or 127.0.0.1.

### A8. Database account

```bash
mysql -e "SELECT user, host FROM mysql.user;"                       # no user with host '%' unless it is needed
mysql -e "SHOW GRANTS FOR CURRENT_USER();"                          # run as the app's user: one database, no SUPER / FILE / GRANT
```

## B. Backups — prove a restore, do not assume one

A backup that has never been restored is a hope, not a backup.

```bash
cd /var/www/yallaspare
BACKUPS=$(php artisan tinker --execute='echo config("ops.backup.directory");')
ls -lt "$BACKUPS" | head -5                                          # a dump from last night exists and is not tiny
php artisan tinker --execute='echo "off-site disk: ", config("ops.backup.offsite_disk") ?: "NOT SET", PHP_EOL;'
```

If the off-site disk is not set, every backup lives on the same disk as the
database. Set `DB_BACKUP_OFFSITE_DISK` and confirm a copy arrives there.

Restore rehearsal — into a **scratch database, never the live one**:

```bash
LATEST=$(ls -t "$BACKUPS"/*.sql.gz | head -1)
gzip -t "$LATEST" && echo "archive is intact"
mysql -e "CREATE DATABASE restore_rehearsal;"
gunzip -c "$LATEST" | mysql restore_rehearsal
mysql -e "SELECT (SELECT COUNT(*) FROM restore_rehearsal.orders) AS restored_orders, (SELECT COUNT(*) FROM yallaspare.orders) AS live_orders;"
mysql -e "DROP DATABASE restore_rehearsal;"                        # the only write in this file; it drops the scratch copy
```

The two counts should be close (live has the orders placed since the dump).
Record the date of the rehearsal and how long the restore took.

## C. Operations — how a failure is noticed

```bash
crontab -l -u www-data | grep -c 'schedule:run'                    # want 1: the scheduler runs every minute
php artisan schedule:list | grep -E 'db:backup|queue:alert-failed|orders:release-unpaid'
supervisorctl status                                               # the queue worker is RUNNING
php artisan queue:failed | tail -5                                 # nothing piling up
df -h / /var | awk 'NR==1 || $5+0 > 80'                            # any volume over 80%
du -sh storage/logs storage/backups
```

Without the `schedule:run` cron line none of these happen: the nightly
backup, the failed-job alert, expired mobile tokens being pruned, and
`orders:release-unpaid` handing back the stock of unpaid online orders.

Disk filling up is the failure with no alarm today. Nothing in the
application watches free space; add a host-level alert (the provider's
monitoring, or a cron line that mails when `df` passes 85%).

## D. Performance and hygiene — not security

These were visible from outside on 2026-10-10. None is a vulnerability.

| Item | Check | Want |
| --- | --- | --- |
| Static files uncached | `curl -sI https://yallaspare.com/build/assets/<file>.js \| grep -i cache-control` | `public, max-age=31536000, immutable` |
| JS/CSS not compressed | same, with `-H 'Accept-Encoding: gzip, br'`, look for `content-encoding` | `gzip` or `br` |
| nginx version shown | `curl -sI https://yallaspare.com \| grep -i '^server'` | `nginx` with no version (`server_tokens off;`) |
| `www.` is a second site | `curl -sI https://www.yallaspare.com \| grep -i '^location'` | a 301 to `https://yallaspare.com/…` |
| OPcache after deploy | `php-fpm8.3 -i \| grep opcache.validate_timestamps` | if `0`, `deploy.sh` must reload PHP-FPM, which it does not do today |

`deploy/STATIC_PERFORMANCE.md` and the two nginx examples in `deploy/nginx/`
hold the configuration for the first three.
