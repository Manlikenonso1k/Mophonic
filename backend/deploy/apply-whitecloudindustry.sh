#!/usr/bin/env bash
set -euo pipefail

DOMAIN="whitecloudindustry.com"
WWW_DOMAIN="www.whitecloudindustry.com"
APP_ROOT="${APP_ROOT:-/home/ubuntu/Mophonic}"
BACKEND_HOST="127.0.0.1"
BACKEND_PORT="8010"
NGINX_SITE="/etc/nginx/sites-available/${DOMAIN}"

sudo mkdir -p /var/www/html/.well-known/acme-challenge

cat <<EOF | sudo tee "${NGINX_SITE}" >/dev/null
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} ${WWW_DOMAIN};

    client_max_body_size 50m;

    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}
EOF

sudo ln -sfn "${NGINX_SITE}" "/etc/nginx/sites-enabled/${DOMAIN}"
sudo apt-get update
sudo apt-get install -y certbot python3-certbot-nginx
sudo nginx -t
sudo systemctl reload nginx
sudo certbot certonly --webroot -w /var/www/html -d "${DOMAIN}" -d "${WWW_DOMAIN}" --non-interactive --agree-tos --register-unsafely-without-email

if [ -d "${APP_ROOT}/backend" ]; then
    (
        cd "${APP_ROOT}/backend"
        php artisan migrate --seed --force
        php artisan storage:link || true
    )
fi

cat <<EOF | sudo tee "${NGINX_SITE}" >/dev/null
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} ${WWW_DOMAIN};

    client_max_body_size 50m;

    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${DOMAIN} ${WWW_DOMAIN};

    client_max_body_size 50m;

    root ${APP_ROOT}/frontend/dist;
    index index.html;

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;

    location ^~ /api/ {
        proxy_pass http://${BACKEND_HOST}:${BACKEND_PORT};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location ^~ /admin/ {
        proxy_pass http://${BACKEND_HOST}:${BACKEND_PORT};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location ^~ /storage/ {
        proxy_pass http://${BACKEND_HOST}:${BACKEND_PORT};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location = /up {
        proxy_pass http://${BACKEND_HOST}:${BACKEND_PORT};
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location / {
        try_files \$uri \$uri/ /index.html;
    }
}
EOF

sudo nginx -t
sudo systemctl reload nginx
