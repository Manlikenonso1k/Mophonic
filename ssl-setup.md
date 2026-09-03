# SSL Setup Runbook (Reconstructed from this server)

## What is currently in use (confirmed)

- **TLS method used:** Let's Encrypt via **Certbot** on the EC2 instance (not ACM on ALB/CloudFront, and not manually uploaded PEMs).
  - Evidence:
    - `certbot 4.0.0` installed at `/usr/bin/certbot`
    - Certificate lineage in `/etc/letsencrypt/renewal/vanderhaagss.com.conf`
    - Nginx points to `/etc/letsencrypt/live/...`
- **Web server:** **nginx** (`nginx/1.28.3`)
- **Active cert name:** `vanderhaagss.com`
- **Domains on cert (SAN):** `vanderhaagss.com`, `www.vanderhaagss.com`
- **Expiry:** `2026-11-10 01:03:30+00:00`
- **TLS termination location:** **on this EC2 host**
  - `vanderhaagss.com` and `www.vanderhaagss.com` resolve to `13.53.248.107`
  - EC2 metadata public IP is `13.53.248.107`
  - Remote certificate presented by `:443` is Let's Encrypt cert for `vanderhaagss.com`

## 1) Prerequisites before starting

1. DNS A records must already resolve to this server's public IP:
   - `vanderhaagss.com -> <EC2_PUBLIC_IP>`
   - `www.vanderhaagss.com -> <EC2_PUBLIC_IP>`
2. Inbound network access must allow:
   - `80/tcp` (required for HTTP-01 challenge attempts)
   - `443/tcp` (HTTPS serving)
3. Nginx vhost should already exist for the app root:
   - `root /home/ubuntu/project-y/public;`
4. Do **not** expose secrets in commands/output:
   - Keep private keys and `.env` values out of docs and terminal captures.

## 2) Step-by-step install (exact command sequence used)

These are the commands that were run on this server, in order of the SSL work:

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d vanderhaagss.com -d www.vanderhaagss.com
sudo ufw status
dig +short vanderhaagss.com
sudo systemctl stop nginx
sudo certbot certonly --standalone -d vanderhaagss.com -d www.vanderhaagss.com
sudo ss -lptn 'sport = :80'
sudo docker stop $(sudo docker ps -a -q)
sudo certbot certonly --standalone -d vanderhaagss.com -d www.vanderhaagss.com
sudo certbot certonly --manual --preferred-challenges dns -d vanderhaagss.com -d www.vanderhaagss.com
sudo systemctl start nginx
sudo systemctl enable nginx
sudo nginx -t
sudo systemctl reload nginx
```

Result: certificate issuance finally succeeded with:

```bash
sudo certbot certonly --manual --preferred-challenges dns -d vanderhaagss.com -d www.vanderhaagss.com
```

## 3) Web server config changes (before/after)

Config file in use:

- `/etc/nginx/sites-available/project_y`
- symlinked by `/etc/nginx/sites-enabled/project_y`

### Before (HTTP only, reconstructed from current file + command/log history)

```nginx
server {
    listen 80;
    server_name vanderhaagss.com www.vanderhaagss.com;
    root /home/ubuntu/project-y/public;

    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
    }
}
```

### After (actual live config)

```nginx
server {
    listen 80;
    server_name vanderhaagss.com www.vanderhaagss.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name vanderhaagss.com www.vanderhaagss.com;
    root /home/ubuntu/project-y/public;

    ssl_certificate /etc/letsencrypt/live/vanderhaagss.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/vanderhaagss.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html index.htm;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## 4) Renewal (actual state + how to operate it)

### Current state on this host

- `certbot.timer` exists and is enabled:
  - unit: `/usr/lib/systemd/system/certbot.timer`
  - service executes: `/usr/bin/certbot -q renew --no-random-sleep-on-renew`
- Renewal config is manual DNS:
  - `/etc/letsencrypt/renewal/vanderhaagss.com.conf`
  - `authenticator = manual`
  - `pref_challs = dns-01,`
- Certbot issuance log explicitly states:
  - `This certificate will not be renewed automatically... --manual-auth-hook was not provided`

### Has renewal dry-run been tested?

- **No evidence of a prior dry run** (`certbot renew --dry-run`) was found in shell history or available logs.

### How to verify renewal path now

```bash
sudo systemctl status certbot.timer --no-pager
sudo certbot renew --dry-run
```

### How to force renewal

Because this cert was issued with manual DNS challenge and no auth hook, forcing renewal requires re-running manual issuance:

```bash
sudo certbot certonly --manual --preferred-challenges dns -d vanderhaagss.com -d www.vanderhaagss.com
```

Then reload nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## 5) Verification (cert is live + full chain)

```bash
# Check cert details on disk
sudo certbot certificates
sudo openssl x509 -in /etc/letsencrypt/live/vanderhaagss.com/cert.pem -noout -subject -issuer -dates -ext subjectAltName

# Check what remote clients actually get
echo | openssl s_client -connect vanderhaagss.com:443 -servername vanderhaagss.com 2>/dev/null | openssl x509 -noout -subject -issuer -dates
echo | openssl s_client -connect www.vanderhaagss.com:443 -servername www.vanderhaagss.com 2>/dev/null | openssl x509 -noout -subject -issuer -dates

# Quick HTTP->HTTPS + service checks
curl -I http://vanderhaagss.com
curl -I https://vanderhaagss.com
```

## 6) Troubleshooting from actual errors hit on this server

### Error 1: Certbot nginx challenge failed (HTTP-01 timeout)

Observed in `/var/log/letsencrypt/letsencrypt.log.3.gz`:

- `Certbot failed to authenticate some domains (authenticator: nginx)`
- `Timeout during connect (likely firewall problem)`
- Failed URLs:
  - `http://vanderhaagss.com/.well-known/acme-challenge/...`
  - `http://www.vanderhaagss.com/.well-known/acme-challenge/...`

Fix actions taken:

```bash
sudo ufw status
dig +short vanderhaagss.com
```

This confirmed challenge accessibility/DNS needed attention before HTTP-01 could work.

### Error 2: Standalone mode could not bind port 80

Observed in `/var/log/letsencrypt/letsencrypt.log.3.gz`:

- `Could not bind TCP port 80 because it is already in use by another process`
- `Problem binding to port 80: [Errno 98] Address already in use`

Fix actions taken:

```bash
sudo systemctl stop nginx
sudo ss -lptn 'sport = :80'
sudo docker stop $(sudo docker ps -a -q)
sudo certbot certonly --standalone -d vanderhaagss.com -d www.vanderhaagss.com
```

### Error 3: Repeated challenge method retries, switched to manual DNS

After nginx/standalone attempts, issuance succeeded only after switching method:

```bash
sudo certbot certonly --manual --preferred-challenges dns -d vanderhaagss.com -d www.vanderhaagss.com
```

Successful issuance evidence in letsencrypt log:

- `Successfully received certificate.`
- `Certificate is saved at: /etc/letsencrypt/live/vanderhaagss.com/fullchain.pem`
- `Key is saved at: /etc/letsencrypt/live/vanderhaagss.com/privkey.pem`

### Error 4: Nginx bind failures during service restarts

Observed in nginx journal (`journalctl -u nginx`) during setup window:

- `bind() to 0.0.0.0:80 failed (98: Address already in use)`
- `bind() to [::]:80 failed (98: Address already in use)`
- `still could not bind()`

Fix actions taken included removing conflicting/default site linkage and restarting after config cleanup:

```bash
sudo rm /etc/nginx/sites-enabled/default
sudo ln -s /etc/nginx/sites-available/project_y /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Related post-SSL app issue during verification

During HTTPS checks, app returned `HTTP/1.1 500 Internal Server Error` while certificate itself was already serving.

Fix actions taken (from history) were app/runtime related, not certificate related:

```bash
npm install
npm run build
sudo apt install -y php8.5-mysql
sudo phpenmod pdo_mysql mysqli mysqlnd
sudo systemctl restart php8.5-fpm nginx
sudo chmod o+x /home/ubuntu
sudo chown -R ubuntu:www-data /home/ubuntu/project-y
sudo chmod -R 775 /home/ubuntu/project-y/storage /home/ubuntu/project-y/bootstrap/cache
```

## 7) Doing this on a new project (same server, different directory)

Use this checklist and only change the values in **bold**:

1. Prepare values:
   - **Domain(s)**: `<new-domain.com>`, `www.<new-domain.com>`
   - **Document root**: `/home/ubuntu/<new-project>/public`
   - **Vhost filename**: `/etc/nginx/sites-available/<new_vhost_name>`
   - **Cert name/path base**: `/etc/letsencrypt/live/<new-domain.com>/...`
2. Ensure DNS A records for the new domain(s) point to this EC2 public IP.
3. Ensure inbound `80` and `443` are open in SG/firewall.
4. Create nginx server block for new app (HTTP + HTTPS pattern as above), then:
   - `sudo ln -s /etc/nginx/sites-available/<new_vhost_name> /etc/nginx/sites-enabled/`
   - `sudo nginx -t && sudo systemctl reload nginx`
5. Issue certificate (expect similar behavior as this host's history):
   - Try nginx method first:
     - `sudo certbot --nginx -d <new-domain.com> -d www.<new-domain.com>`
   - If HTTP-01 or bind errors recur, resolve port/DNS and use manual DNS method:
     - `sudo certbot certonly --manual --preferred-challenges dns -d <new-domain.com> -d www.<new-domain.com>`
6. Set HTTPS cert refs in the new vhost:
   - `ssl_certificate /etc/letsencrypt/live/<new-domain.com>/fullchain.pem;`
   - `ssl_certificate_key /etc/letsencrypt/live/<new-domain.com>/privkey.pem;`
7. Validate:
    - `sudo certbot certificates`
    - `echo | openssl s_client -connect <new-domain.com>:443 -servername <new-domain.com> 2>/dev/null | openssl x509 -noout -subject -issuer -dates`

## 8) Keeping two projects independent on the same EC2 host

Use one Nginx file per domain. Do not edit the existing `project_y` vhost when adding `whitecloudindustry.com`.

For this repo, the new vhost template lives at:

- `backend/deploy/nginx/whitecloudindustry.com.conf`

Enable it alongside the existing site:

```bash
sudo ln -s /etc/nginx/sites-available/whitecloudindustry.com /etc/nginx/sites-enabled/whitecloudindustry.com
sudo nginx -t
sudo systemctl reload nginx
```

If `project_y` is already the default site, leave its config and symlink untouched. The new `server_name whitecloudindustry.com www.whitecloudindustry.com;` block will keep traffic separate as long as the old vhost does not claim those hostnames.

---

## Sensitive data redaction notes

- **Do not paste private key contents** from `/etc/letsencrypt/live/<cert-name>/privkey.pem`.
- **Do not paste `.env` secrets** (`/home/ubuntu/project-y/.env`).
- **Do not paste AWS credentials** (if present in shell/session/env).
- If a command output includes secrets, replace with: `<REDACTED_SECRET>` and mention where the real value is stored.
