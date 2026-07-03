# YallaSpare — Sunucu Güvenlik Kontrol Listesi

VPS: Ubuntu/Debian · nginx + PHP 8.3-FPM + Supervisor + MySQL · `/var/www/yallaspare` · yallaspare.com (72.62.93.151)

Her maddeyi sunucuda SSH ile çalıştırıp sonucu doğrulayın. **Beklenen** sonuç ✅ ile işaretli.
Kod tarafı (CSP, ödeme doğrulama, rate limit vb.) zaten tamam — bu liste **sunucu + operasyon** katmanı içindir.

> Öncelik sırası: önce 🔴 Kritik, sonra 🟠 Yüksek, sonra 🟡 Orta.

---

## 🔴 KRİTİK — bunlar açıksa gerisi anlamsız

### 1. Hassas dosyalar web'den erişilemiyor mu?
`.env`, `.git`, `storage/`, `composer.json` dışarıdan çekilememeli.

```bash
for p in .env .git/config composer.json storage/logs/laravel.log; do
  echo -n "$p → "; curl -s -o /dev/null -w "%{http_code}\n" "https://yallaspare.com/$p"
done
```
✅ **Hepsi 403 veya 404 dönmeli.** 200 gören herhangi bir satır acil kapatılmalı.

- nginx `root` yalnızca `/var/www/yallaspare/public` olmalı (proje köküne DEĞİL).
  ```bash
  grep -R "root " /etc/nginx/sites-enabled/ | grep yallaspare
  ```
  ✅ `.../yallaspare/public;` ile bitmeli.
- deploy/nginx örneğindeki `location ~ /\.(?!well-known).* { deny all; }` bloğu aktif olmalı.

### 2. `.env` dosya izinleri ve içeriği
```bash
ls -l /var/www/yallaspare/.env
```
✅ `-rw------- ... www-data www-data` (640/600). Herkese okunur (`644`) OLMAMALI.

```bash
grep -E "^(APP_DEBUG|APP_ENV|APP_KEY)=" /var/www/yallaspare/.env
```
✅ `APP_DEBUG=false`, `APP_ENV=production`, `APP_KEY=base64:...` dolu.
> `APP_DEBUG=true` üretimde stack trace + env sızdırır — en sık görülen kritik hata.

### 3. Admin hesapları: 2FA + güçlü parola
- Tüm admin/super-admin hesaplarında e-posta 2FA aktif mi? (`two_factor_preference`)
- Test/örnek admin hesapları silinmiş mi?
```bash
cd /var/www/yallaspare && php artisan tinker --execute="
App\Models\User::whereIn('role',['admin','super_admin'])
  ->get(['id','email','role','two_factor_preference'])->each(fn(\$u)=>print(\$u->email.' | '.\$u->role.' | 2fa:'.\$u->two_factor_preference.PHP_EOL));"
```
✅ Her admin `2fa:email`. `off` olan varsa panelden açtırın.

### 4. MySQL dışarıya kapalı
```bash
ss -tlnp | grep 3306
```
✅ Yalnızca `127.0.0.1:3306` dinlemeli. `0.0.0.0:3306` görürsen internete açıksın → `bind-address = 127.0.0.1` yap, firewall'da 3306'yı kapat.
- DB parolası güçlü ve `.env`'dekiyle aynı, tahmin edilebilir değil.
- `mysql_secure_installation` çalıştırılmış (anonim kullanıcı yok, root uzaktan giriş kapalı).

### 5. Güvenlik duvarı (firewall) açık ve dar
```bash
sudo ufw status verbose
```
✅ `Status: active`, yalnızca **22 (SSH), 80, 443** açık. Başka her port kapalı.
```bash
sudo ufw default deny incoming && sudo ufw allow 22,80,443/tcp && sudo ufw enable
```

---

## 🟠 YÜKSEK

### 6. SSH sertleştirme
`/etc/ssh/sshd_config` içinde:
```bash
sudo sshd -T | grep -E "permitrootlogin|passwordauthentication|pubkeyauthentication"
```
✅ `permitrootlogin no`, `passwordauthentication no` (SSH anahtarı kullanın), `pubkeyauthentication yes`.
- Değiştirdikten sonra: `sudo systemctl reload ssh` (mevcut oturumu kapatmadan yeni oturumla test edin).
- `fail2ban` kurulu ve SSH jail aktif mi?
  ```bash
  sudo fail2ban-client status sshd
  ```

### 7. HTTPS / TLS sağlam
```bash
curl -sI https://yallaspare.com | grep -iE "strict-transport|^HTTP"
```
✅ `HTTP/2 200` + `Strict-Transport-Security: max-age=31536000; includeSubDomains`.
- HTTP → HTTPS zorunlu yönlendirme var mı?
  ```bash
  curl -sI http://yallaspare.com | grep -i location
  ```
  ✅ `Location: https://...`
- Sertifika süresi:
  ```bash
  echo | openssl s_client -connect yallaspare.com:443 2>/dev/null | openssl x509 -noout -dates
  ```
- Let's Encrypt otomatik yenileme çalışıyor mu? `sudo systemctl status certbot.timer`
- (Opsiyonel) SSL Labs testi: https://www.ssllabs.com/ssltest/ → A veya A+ hedefleyin.

### 8. OS ve paketler güncel
```bash
sudo apt update && apt list --upgradable 2>/dev/null | grep -iE "security|php|nginx|mysql|openssl"
```
✅ Güvenlik güncellemeleri kurulu. Otomatik güvenlik yamaları:
```bash
sudo apt install unattended-upgrades && sudo dpkg-reconfigure -plow unattended-upgrades
```
- PHP sürümü destekli mi? `php -v` → 8.2/8.3 aktif olarak yamalanıyor. 8.1 ve altı EOL riskli.

### 9. Dosya sahiplik ve izinleri
```bash
cd /var/www/yallaspare
stat -c "%U:%G %a %n" . storage bootstrap/cache public
```
✅ Kod `www-data` (veya deploy kullanıcısı) sahipli. `storage/` ve `bootstrap/cache/` yazılabilir (775), gerisi 755. Hiçbir şey `777` OLMAMALI.
```bash
find /var/www/yallaspare -type f -perm -o+w -not -path "*/node_modules/*" 2>/dev/null
```
✅ Boş çıktı (dünyaya-yazılabilir dosya yok).

### 10. Yüklenen dosyalar çalıştırılamıyor
deploy/nginx örneğindeki `location ^~ /storage/ { ... location ~ \.php { return 404; } }` aktif mi?
```bash
curl -s -o /dev/null -w "%{http_code}\n" "https://yallaspare.com/storage/test.php"
```
✅ 404/403. `.php` uzantılı yüklenen dosya asla PHP olarak yorumlanmamalı (SecureImageStorage zaten byte doğruluyor, bu ikinci savunma).

### 11. Yedekleme çalışıyor ve geri yüklenebilir
- `.env`'de `BACKUP_MYSQL_ENABLED=true` ve zamanlanmış:
  ```bash
  grep BACKUP /var/www/yallaspare/.env
  ls -lt /var/www/yallaspare/storage/app/backups 2>/dev/null | head
  ```
  ✅ Son 24 saat içinde yeni yedek var.
- Yedekler **sunucu dışında** da tutuluyor mu? (VPS çökerse yedek de gider.) S3/harici konuma kopya alın.
- **Geri yükleme testi yapıldı mı?** Test edilmemiş yedek = yedek yok.

---

## 🟡 ORTA

### 12. Laravel üretim optimizasyonu (aynı zamanda güvenlik)
```bash
cd /var/www/yallaspare
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan about | grep -iE "environment|debug|cache"
```
✅ Environment: production, Debug Mode: OFF.

### 13. Servisler sağlıklı
```bash
sudo systemctl status nginx php8.3-fpm mysql --no-pager | grep -E "Active:"
sudo supervisorctl status yallaspare-worker
```
✅ Hepsi `active (running)` / `RUNNING`. Queue worker düşükse mailler/bildirimler gitmez.

### 14. Log izleme
```bash
tail -n 50 /var/www/yallaspare/storage/logs/security-$(date +%F).log
```
- Güvenlik log kanalı (auth/authz/throttle olayları) yazıyor mu? Düzenli göz atıyor musunuz?
- CSP ihlal raporları (`/csp-report`) geliyor mu — enforce geçişi sonrası beklenmedik ihlal var mı?
- Log dosyaları çok büyümesin: `logrotate` yapılandırılmış mı?

### 15. Gizli anahtar hijyeni
- `.env`'deki API anahtarları (FIB, ZainCash, AWS, Pusher, MAIL) **canlı/gerçek** mi, örnek değer mi?
- Git geçmişinde sır sızmış mı? (bir kez commit edildiyse rotasyon gerekir)
  ```bash
  cd /var/www/yallaspare && git log --all -p -- .env 2>/dev/null | head
  ```
  ✅ Boş — `.env` hiç commit edilmemiş olmalı (`.gitignore`'da).
- Ödeme webhook token'ları sağlayıcı panelinde **header** olarak ayarlı (query string değil — son güvenlik commit'i bunu zorunlu kıldı).

### 16. Rate limit / DDoS
- Cloudflare önde mi? (TrustProxies zaten Cloudflare IP'lerine göre ayarlı.)
  ✅ Öyleyse `TRUSTED_PROXIES=cloudflare` set edilmeli, origin IP gizli olmalı.
- nginx `client_max_body_size 64M` uygulanmış (deploy örneğinde var) — dev upload'ları reddetmek için.

---

## Deploy adımları (her yayına alışta sırayla)

Kod değişikliğini canlıya alırken bu sırayı izleyin. **En sık hata:** `git pull`
yapmadan ya da `npm run build`'i eski kod üzerinde çalıştırıp "deploy ettim"
sanmak — sonuç: eski kod canlıda kalır.

```bash
cd /var/www/yallaspare

# 1) Kodu çek — ÇIKTIYA BAK, sessizce başarısız olmuş olabilir
git status                 # temiz mi? yerel değişiklik / detached HEAD pull'u engeller
git pull origin main
git log --oneline -1       # beklediğiniz commit'i görmelisiniz (doğrulama)

# 2) Bağımlılıklar (yalnızca composer.json/package.json değiştiyse)
composer install --no-dev --optimize-autoloader
npm ci

# 3) Frontend'i derle — blade/JS değiştiyse ŞART
npm run build              # public/build gitignore'da; burada üretilir

# 4) Migration (yeni migration varsa)
php artisan migrate --force

# 5) Önbellekleri tazele — pull sonrası eski derlenmiş blade/config'i temizler
php artisan config:cache
php artisan route:cache
php artisan view:cache      # eski inline script'ler görünüyorsa bunu atlamışsınızdır

# 6) Kuyruk worker'ını yeniden başlat (yeni koddan çalışsın)
sudo supervisorctl restart yallaspare-worker
```

**Doğrulama (deploy sonrası):**
- `git log --oneline -1` → yayınlamak istediğiniz commit görünüyor mu?
- Tarayıcıda `https://yallaspare.com/user/home` → `Ctrl+U` (view-source):
  ✅ `build/assets/storefront-*.js` referansı var, uzun inline JS blokları yok.
- `https://yallaspare.com/build/manifest.json` → beklenen JS girdileri (ör.
  `resources/js/storefront.js`) listede.
- F12 → Console → **hata yok**.

> Notlar:
> - `npm run build` olmadan yalnızca `git pull` yaparsanız blade yeni asset'i
>   arar ama dosya yoktur → sayfa `@vite` hatası verir. Her zaman ikisi birlikte.
> - `config:cache` çalıştırdıysanız `.env` değişikliği ancak yeniden
>   `config:cache` ile devreye girer — deploy'un parçası olarak tekrarlayın.

---

## Hızlı skor
- 🔴 5/5 tamam → temel güvendesiniz, bot saldırılarına kapalısınız.
- 🔴 + 🟠 tamam → hedefli saldırganlar için de ciddi engel.
- Hepsi tamam → ortalama üretim sitesinin üstündesiniz; kalan risk çoğunlukla insan/oltalama.

**En sık atlanan 3 madde:** (2) `APP_DEBUG`, (3) admin 2FA, (11) test edilmemiş yedek.
