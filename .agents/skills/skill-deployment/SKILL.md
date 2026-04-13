---
name: skill-deployment
description: "**WORKFLOW SKILL** — Professional deployment of pure PHP applications on VPS and limited hosting panels. USE FOR: VPS deployment (Ubuntu/Debian/CentOS with Nginx + PHP-FPM or Apache); shared hosting deployment (cPanel, Plesk, DirectAdmin, generic shared hosting); .htaccess configuration for Apache; Nginx server blocks; PHP-FPM pool configuration; SSL/TLS with Let's Encrypt (Certbot); environment variables on restricted hosts; zero-downtime deployment scripts; file permissions; cron jobs on VPS and cPanel; database migration on deploy; deployment via Git pull or FTP/SFTP; systemd services; firewall configuration (UFW); PHP OPcache configuration; production hardening; rollback strategy; subdomain and subdirectory deployment. DO NOT USE FOR: Docker/Kubernetes; CI/CD pipelines; cloud-native infrastructure."
---

# Deployment — PHP on VPS & Limited Hosting Panels

## Core Philosophy

- **Same codebase, different environments**: One set of code runs on a VPS with full control and on a cPanel shared host with `.htaccess` only.
- **Environment variables, never hardcoded config**: `.env` on VPS, `config.php` fallback on restricted panels.
- **Public directory only**: Only `public/` is web-accessible; everything else is above the web root.
- **Zero-downtime by design**: Atomic symlink deployment; no mid-deploy broken state.
- **Security before functionality**: Correct permissions before the first request.

---

## Deployment Checklist

```
Pre-deploy:
  [ ] All tests pass locally
  [ ] .env.production reviewed
  [ ] Database migration scripts ready
  [ ] Storage directories writable
  [ ] OPcache will be cleared after deploy

Deploy:
  [ ] Backup database
  [ ] Pull/upload new code
  [ ] Run migrations
  [ ] Clear caches
  [ ] Verify OPcache reset

Post-deploy:
  [ ] Smoke test: login, core feature
  [ ] Check error logs
  [ ] Verify cron jobs running
```

---

## VPS Deployment (Ubuntu/Debian)

### Initial Server Setup

```bash
# Update system
apt update && apt upgrade -y

# Install required packages
apt install -y nginx php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
               php8.2-curl php8.2-gd php8.2-zip php8.2-intl php8.2-apcu \
               mariadb-server certbot python3-certbot-nginx git unzip ufw

# Firewall
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw enable

# Create application user
useradd --system --shell /bin/bash --create-home --home /var/www biblioteca
```

### Directory Structure on VPS

```bash
/var/www/biblioteca/
├── releases/
│   ├── 20240115_143000/    ← each deploy is a timestamped folder
│   └── 20240116_090000/
├── shared/
│   ├── .env                ← never in releases; symlinked
│   └── storage/            ← uploads, cache, logs; symlinked
└── current -> releases/20240116_090000/   ← symlink to active release
```

### Zero-Downtime Deploy Script

```bash
#!/usr/bin/env bash
# deploy.sh — run from CI or manually via SSH
set -euo pipefail

APP_DIR="/var/www/biblioteca"
REPO="git@github.com:user/biblioteca.git"
BRANCH="${1:-main}"
RELEASE="${APP_DIR}/releases/$(date +%Y%m%d_%H%M%S)"
SHARED="${APP_DIR}/shared"
CURRENT="${APP_DIR}/current"
KEEP_RELEASES=5

echo "==> Creating release: ${RELEASE}"
git clone --depth 1 --branch "${BRANCH}" "${REPO}" "${RELEASE}"

echo "==> Linking shared resources"
ln -nfs "${SHARED}/.env"     "${RELEASE}/.env"
ln -nfs "${SHARED}/storage"  "${RELEASE}/storage"

echo "==> Setting permissions"
find "${RELEASE}" -type f -exec chmod 644 {} \;
find "${RELEASE}" -type d -exec chmod 755 {} \;
chmod 750 "${RELEASE}/deploy.sh" 2>/dev/null || true

echo "==> Running database migrations"
php "${RELEASE}/bin/migrate.php"

echo "==> Clearing OPcache"
php -r "if (function_exists('opcache_reset')) opcache_reset();" 2>/dev/null || true
# Or via web: curl -s http://localhost/opcache-clear.php (protected endpoint)

echo "==> Switching to new release (atomic)"
ln -nfs "${RELEASE}" "${CURRENT}"

echo "==> Reloading PHP-FPM"
systemctl reload php8.2-fpm

echo "==> Cleaning old releases (keep ${KEEP_RELEASES})"
ls -1dt "${APP_DIR}/releases"/*/ | tail -n +$((KEEP_RELEASES + 1)) | xargs rm -rf

echo "==> Deploy complete: ${RELEASE}"
```

### Nginx Configuration

```nginx
# /etc/nginx/sites-available/biblioteca
server {
    listen 80;
    listen [::]:80;
    server_name biblioteca.example.com;

    # Redirect HTTP to HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name biblioteca.example.com;

    root /var/www/biblioteca/current/public;
    index index.php;
    charset utf-8;

    # SSL (managed by Certbot)
    ssl_certificate     /etc/letsencrypt/live/biblioteca.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/biblioteca.example.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;
    ssl_session_cache   shared:SSL:10m;

    # Security headers
    add_header X-Frame-Options            "SAMEORIGIN"                always;
    add_header X-Content-Type-Options     "nosniff"                   always;
    add_header X-XSS-Protection           "1; mode=block"             always;
    add_header Referrer-Policy            "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security  "max-age=31536000; includeSubDomains" always;

    # Logs
    access_log /var/log/nginx/biblioteca_access.log;
    error_log  /var/log/nginx/biblioteca_error.log;

    # Max upload size
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass   unix:/run/php/php8.2-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_buffer_size          128k;
        fastcgi_buffers              4 256k;
        fastcgi_read_timeout         60;
    }

    # Block access to sensitive files
    location ~ /\.(env|git|htaccess|sql|bak|log) {
        deny all;
    }

    location ~* \.(env|log|sql|bak|ini|conf)$ {
        deny all;
    }

    # Static file cache
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
```

### PHP-FPM Pool Configuration

```ini
# /etc/php/8.2/fpm/pool.d/biblioteca.conf
[biblioteca]
user  = biblioteca
group = biblioteca

listen = /run/php/php8.2-fpm-biblioteca.sock
listen.owner = www-data
listen.group = www-data
listen.mode  = 0660

; Process management
pm                   = dynamic
pm.max_children      = 20
pm.start_servers     = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 8
pm.max_requests      = 500

; Timeouts
request_terminate_timeout = 60s

; PHP settings for this pool
php_admin_value[error_log]         = /var/log/php/biblioteca.log
php_admin_flag[log_errors]         = on
php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size]     = 22M
php_admin_value[memory_limit]      = 128M
php_admin_value[max_execution_time] = 30
php_admin_value[open_basedir]      = /var/www/biblioteca/:/tmp/
```

### OPcache Configuration

```ini
# /etc/php/8.2/fpm/conf.d/10-opcache.ini
opcache.enable                 = 1
opcache.memory_consumption     = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files  = 10000
opcache.revalidate_freq        = 0      ; 0 = never recheck in production
opcache.validate_timestamps    = 0      ; disable in production (clear on deploy)
opcache.save_comments          = 0
opcache.enable_cli             = 0
opcache.jit_buffer_size        = 64M
opcache.jit                    = tracing
```

### SSL with Let's Encrypt

```bash
# Obtain certificate
certbot --nginx -d biblioteca.example.com

# Auto-renewal (already installed by certbot)
systemctl status certbot.timer

# Manual renewal
certbot renew --dry-run
certbot renew
```

### Cron Jobs on VPS

```bash
# Edit crontab for biblioteca user
crontab -u biblioteca -e

# Example crons for library system
# Daily: send overdue notifications at 8am
0 8 * * * php /var/www/biblioteca/current/bin/notify-overdue.php >> /var/www/biblioteca/shared/storage/logs/cron.log 2>&1

# Every hour: check reservations
0 * * * * php /var/www/biblioteca/current/bin/check-reservations.php >> /var/www/biblioteca/shared/storage/logs/cron.log 2>&1

# Weekly: cleanup soft-deleted records
0 2 * * 0 php /var/www/biblioteca/current/bin/cleanup.php >> /var/www/biblioteca/shared/storage/logs/cron.log 2>&1
```

---

## Shared Hosting Deployment (cPanel / Plesk / DirectAdmin)

### The Fundamental Problem

On shared hosting, the web root is fixed (e.g. `public_html/`). The solution: place the app **above** `public_html` and point `public_html` to our `public/` folder.

```
/home/username/          ← home directory (not web-accessible)
├── biblioteca/          ← full application here
│   ├── src/
│   ├── config/
│   ├── views/
│   ├── storage/
│   └── public/          ← this is what the web must see
└── public_html/         ← web root (cPanel) — redirect to biblioteca/public/
```

### Option 1 — Redirect via public_html/index.php

```php
<?php
// public_html/index.php — redirect to app public dir
define('BASE_PATH', dirname(__DIR__) . '/biblioteca');
require BASE_PATH . '/public/index.php';
```

```apache
# public_html/.htaccess
Options -Indexes
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Option 2 — Symlink public_html to public/ (if allowed)

```bash
# Via SSH on cPanel server
rm -rf ~/public_html
ln -s ~/biblioteca/public ~/public_html
```

### .htaccess for Shared Hosting

```apache
# public/.htaccess
Options -Indexes -MultiViews
RewriteEngine On

# Block access to .env and config files
RewriteRule ^\.env$          - [F,L]
RewriteRule ^config/         - [F,L]
RewriteRule ^src/            - [F,L]
RewriteRule ^storage/        - [F,L]
RewriteRule ^views/          - [F,L]

# Route all requests through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers (if mod_headers enabled)
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header unset X-Powered-By
</IfModule>

# PHP settings via .htaccess (if allowed)
<IfModule mod_php8.c>
    php_value upload_max_filesize 20M
    php_value post_max_size 22M
    php_value memory_limit 128M
    php_flag display_errors Off
    php_flag log_errors On
    php_value error_log /home/username/biblioteca/storage/logs/php.log
</IfModule>

# Deny access to sensitive file types
<FilesMatch "\.(env|sql|bak|log|ini|conf|sh)$">
    Require all denied
</FilesMatch>
```

### Environment Config Without .env (Fallback)

```php
<?php
// config/environment.php — for hosts that can't set env vars
// Place ABOVE public_html; never commit with real credentials

return [
    'APP_ENV'     => 'production',
    'APP_DEBUG'   => false,
    'APP_URL'     => 'https://biblioteca.example.com',
    'APP_KEY'     => 'base64:...',  // 32-byte random key

    'DB_HOST'     => 'localhost',
    'DB_PORT'     => '3306',
    'DB_DATABASE' => 'user_biblioteca',
    'DB_USERNAME' => 'user_dbuser',
    'DB_PASSWORD' => 'strong_password',

    'MAIL_HOST'   => 'mail.example.com',
    'MAIL_PORT'   => '587',
    'MAIL_USER'   => 'no-reply@example.com',
    'MAIL_PASS'   => 'mail_password',
];
```

```php
// bootstrap.php — load config/environment.php if .env not present
if (!file_exists(BASE_PATH . '/.env')) {
    $config = require BASE_PATH . '/config/environment.php';
    foreach ($config as $key => $value) {
        $_ENV[$key] = $value;
    }
}
```

### Cron Jobs on cPanel

```
# cPanel Cron Jobs → Add New Cron Job
# Command format: php /home/username/biblioteca/bin/script.php

# Overdue notifications (daily at 8:00)
Minute: 0  Hour: 8  Day: *  Month: *  Weekday: *
Command: php /home/username/biblioteca/bin/notify-overdue.php >> /home/username/biblioteca/storage/logs/cron.log 2>&1

# Reservation check (hourly)
Minute: 0  Hour: *  Day: *  Month: *  Weekday: *
Command: php /home/username/biblioteca/bin/check-reservations.php
```

### FTP/SFTP Deployment Script

```bash
#!/usr/bin/env bash
# local-deploy.sh — upload via SFTP to shared hosting
set -euo pipefail

HOST="ftp.example.com"
USER="username"
REMOTE_DIR="/home/username/biblioteca"

# Exclude dev files
rsync -avz --delete \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='storage/logs/*' \
    --exclude='storage/cache/*' \
    --exclude='storage/uploads' \
    --exclude='node_modules' \
    ./ "${USER}@${HOST}:${REMOTE_DIR}/"

echo "Upload complete."

# Run migrations remotely via SSH
ssh "${USER}@${HOST}" "php ${REMOTE_DIR}/bin/migrate.php"
echo "Migrations done."
```

---

## Database Migration Script

```php
<?php
// bin/migrate.php — run on every deploy
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

$db = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $_ENV['DB_HOST'], $_ENV['DB_DATABASE']),
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Migrations table
$db->exec("CREATE TABLE IF NOT EXISTS migrations (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    migration  VARCHAR(255) NOT NULL,
    ran_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_migrations_name (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$ran = $db->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
$files = glob(BASE_PATH . '/database/migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file, '.sql');
    if (in_array($name, $ran, true)) continue;

    echo "Running migration: {$name}\n";
    $db->exec(file_get_contents($file));
    $stmt = $db->prepare("INSERT INTO migrations (migration) VALUES (?)");
    $stmt->execute([$name]);
    echo "Done: {$name}\n";
}

echo "All migrations complete.\n";
```

---

## File Permissions

```bash
# VPS — correct permissions
find /var/www/biblioteca/current -type f -exec chmod 644 {} \;
find /var/www/biblioteca/current -type d -exec chmod 755 {} \;

# Writable directories
chmod -R 775 /var/www/biblioteca/shared/storage/
chown -R biblioteca:www-data /var/www/biblioteca/

# Shared hosting
find ~/biblioteca -type f -exec chmod 644 {} \;
find ~/biblioteca -type d -exec chmod 755 {} \;
chmod -R 755 ~/biblioteca/storage/
```

---

## Rollback

```bash
# List releases
ls -lt /var/www/biblioteca/releases/

# Rollback to previous release
ln -nfs /var/www/biblioteca/releases/20240115_143000 /var/www/biblioteca/current
systemctl reload php8.2-fpm
echo "Rolled back."
```

---

## Production Hardening

```php
<?php
// Verify in production bootstrap
if ($_ENV['APP_ENV'] === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('expose_php', '0');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
}
```

---

## Workflow

1. **Backup DB antes de cada deploy** — `mysqldump` automático en el script de deploy.
2. **`.env` nunca en el repo** — Usar `.env.example` con claves vacías; `.env` solo en servidor.
3. **Probar en staging primero** — Mismo tipo de servidor que producción (VPS o shared hosting).
4. **Symlink atómico en VPS** — El `ln -nfs` garantiza zero-downtime al cambiar `current`.
5. **Limpiar OPcache tras deploy** — Código nuevo + OPcache viejo = comportamiento extraño.
6. **Verificar logs post-deploy** — `tail -f /var/log/nginx/biblioteca_error.log` durante 5 minutos.
7. **Rollback inmediato si algo falla** — No intentar parchear en producción; volver atrás y arreglar localmente.
8. **Permisos correctos siempre** — `644` para archivos, `755` para directorios, `775` solo para `storage/`.
