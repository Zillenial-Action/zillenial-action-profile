#!/bin/sh

cd /var/www/html

PORT="${PORT:-80}"

echo "[entrypoint] === Starting container with PORT=${PORT} ==="

# Generate /etc/nginx/nginx.conf at runtime using PORT env var
# NOTE: $ signs for nginx vars must be escaped with \
cat > /etc/nginx/nginx.conf << NGINXEOF
user root;
worker_processes 1;
pid /tmp/nginx.pid;
error_log /dev/stderr warn;

events {
    worker_connections 512;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    access_log off;

    sendfile        on;
    keepalive_timeout 65;
    client_max_body_size 20M;

    server {
        listen ${PORT} default_server;
        server_name _;
        root /var/www/html/public;
        index index.php index.html;

        add_header X-Frame-Options "SAMEORIGIN";
        add_header X-Content-Type-Options "nosniff";

        charset utf-8;

        location / {
            try_files \$uri \$uri/ /index.php?\$query_string;
        }

        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        location ~ \.php$ {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
            fastcgi_read_timeout 120;
            include        fastcgi_params;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
}
NGINXEOF

echo "[entrypoint] nginx.conf written for port ${PORT}"

# Validate nginx config - if this fails, print the config and exit
if ! /usr/sbin/nginx -t 2>&1; then
    echo "[entrypoint] ERROR: nginx config test failed. Config dump:"
    cat /etc/nginx/nginx.conf
    exit 1
fi
echo "[entrypoint] nginx config validated OK"

# ---------- Laravel bootstrap ----------
if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
    echo "[entrypoint] Copied .env.example to .env"
fi

if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --force && echo "[entrypoint] APP_KEY generated"
fi

php artisan package:discover --ansi 2>&1 || true

php artisan config:clear  2>&1 || true
php artisan config:cache  2>&1 || echo "[entrypoint] config:cache skipped"
php artisan route:cache   2>&1 || echo "[entrypoint] route:cache skipped"
php artisan view:cache    2>&1 || echo "[entrypoint] view:cache skipped"

if [ "${RUN_MIGRATIONS}" = "true" ]; then
    php artisan migrate --force 2>&1 || echo "[entrypoint] migrate failed"
fi

php artisan storage:link 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# ---------- Persistent uploads (Railway Volume di-mount ke /var/www/html/public/image) ----------
# Volume ter-mount sebagai root, jadi folder harus dibuat & di-set writable oleh www-data saat start.
mkdir -p public/image
chown -R www-data:www-data public/image 2>/dev/null || true
chmod -R 775 public/image 2>/dev/null || true
echo "[entrypoint] public/image ready for uploads"

# ---------- Seed gambar lama ke volume (sekali saja) ----------
# Mengisi Railway Volume (public/image) dengan gambar lama dari arsip. Hanya jalan SEKALI:
# setelah sukses, dibuat marker public/image/.seeded sehingga deploy berikutnya tidak seed lagi.
# tar hanya MENAMBAH/menimpa file dari arsip; tidak menghapus upload baru dari admin.
# Prasyarat: Railway Volume HARUS ter-mount di /var/www/html/public/image agar hasil seed
# dan upload berikutnya persisten (tidak hilang saat redeploy).
if [ -f docker/seed/images.tar.gz ] && [ ! -f public/image/.seeded ]; then
    echo "[entrypoint] Seeding gambar lama ke volume..."
    if tar -xzf docker/seed/images.tar.gz -C public/image; then
        # Kompat: bila arsip ternyata berisi folder image/ di dalamnya, ratakan strukturnya.
        if [ -d public/image/image ]; then
            cp -rn public/image/image/. public/image/ 2>/dev/null || true
            rm -rf public/image/image
        fi
        touch public/image/.seeded
        chown -R www-data:www-data public/image 2>/dev/null || true
        echo "[entrypoint] Seed gambar lama selesai"
    else
        echo "[entrypoint] WARN: gagal extract seed images"
    fi
fi

# ---------- Start PHP-FPM ----------
echo "[entrypoint] Starting PHP-FPM in background..."
/usr/local/sbin/php-fpm -D 2>&1
echo "[entrypoint] PHP-FPM daemonized"

# ---------- Queue worker (email tiket & job async lain) ----------
# Worker digabung di container web (bukan service terpisah). Dijalankan di background dengan
# loop restart supaya bila worker mati/di-kill, otomatis hidup lagi. Butuh QUEUE_CONNECTION=database
# dan tabel `jobs` (jalankan migrate). Job yang tetap gagal setelah semua retry masuk ke failed_jobs.
if [ "${QUEUE_CONNECTION:-database}" != "sync" ]; then
    echo "[entrypoint] Starting queue worker in background..."
    (
        while true; do
            php artisan queue:work --queue=default --sleep=3 --tries=5 --max-time=3600 --backoff=60 2>&1
            echo "[entrypoint] queue worker exited, restarting in 3s..."
            sleep 3
        done
    ) &
    echo "[entrypoint] queue worker launched"
else
    echo "[entrypoint] QUEUE_CONNECTION=sync, queue worker not started"
fi

# ---------- Scheduler (cron Laravel) ----------
# Tanpa proses ini, jadwal di App\Console\Kernel (mis. transaksi:expire-pending tiap 15 menit)
# TIDAK PERNAH jalan. schedule:work adalah proses long-running yang memicu schedule:run tiap
# menit — pengganti entry cron sistem "* * * * * php artisan schedule:run". Dijalankan di
# background dengan loop restart supaya bila mati otomatis hidup lagi.
echo "[entrypoint] Starting scheduler (schedule:work) in background..."
(
    while true; do
        php artisan schedule:work 2>&1
        echo "[entrypoint] scheduler exited, restarting in 3s..."
        sleep 3
    done
) &
echo "[entrypoint] scheduler launched"

# ---------- Start nginx in foreground (becomes PID 1) ----------
echo "[entrypoint] Starting nginx on port ${PORT}..."
exec /usr/sbin/nginx -g "daemon off;"