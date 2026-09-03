# Agent notes

## What I did

- Installed backend dependencies with `composer install`.
- Created the backend `.env` from `.env.example` when needed.
- Generated the Laravel app key and created the SQLite database file.
- Ran migrations and storage linking.
- Installed frontend dependencies with `npm install`.
- Built the frontend with `npm run build`.
- Fixed the missing PHP GD extension (`php8.5-gd`) so the image-related tests could run.
- Ran the test suite successfully.
- Created a dedicated Nginx/Certbot deployment path for `whitecloudindustry.com` so it stays independent from `project_y`.
- Updated the deploy script to run `php artisan migrate --seed` and `php artisan storage:link` so the production API returns work records and image URLs.

## Deployment fix

The domain was initially falling through to `project_y` because the new vhost was either missing or pointing at the wrong root. I added a separate vhost template and a bootstrap script for the new site only:

- `backend/deploy/nginx/whitecloudindustry.com.conf`
- `backend/deploy/apply-whitecloudindustry.sh`

The final live Nginx setup serves `whitecloudindustry.com` from:

- `/home/ubuntu/Mophonic/frontend/dist`

and proxies Laravel routes to:

- `127.0.0.1:8010`

I used a webroot-based Certbot issuance so the certificate could be created before HTTPS was enabled, avoiding any impact on the existing `project_y` site.

## Verification

- Backend tests passed after installing GD.
- The new domain responded with HTTP 200 after the vhost root was corrected.
- `project_y` was left untouched.
