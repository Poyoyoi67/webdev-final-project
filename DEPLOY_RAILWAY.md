# Railway deployment — HealthCare

This project is **ready to push**. Deployment uses `dockerfile` (lowercase) and `scripts/railway-start.sh` — `railway.toml` must use `dockerfilePath = "dockerfile"`.

## Before `git push`

1. **Never commit** `.env` (secrets). Use `.env.example` and `railway.env.example` as templates.
2. Copy `railway.env.example` values into Railway **Variables** after creating the project.
3. Generate new `APP_SECRET` and `JWT_PASSPHRASE` for production (do not reuse local dev values).

## Quick push

```powershell
cd C:\Users\Licht\lebbanskie\HealthCare
git init
git add .
git status   # confirm .env is NOT listed
git commit -m "HealthCare: Railway-ready Symfony deployment"
git branch -M main
git remote add origin https://github.com/YOUR_USER/YOUR_REPO.git
git push -u origin main
```

## Railway setup

1. **New Project** → Deploy from GitHub repo.
2. If repo contains `AppDev` + `HealthCare`, set **Root Directory** = `HealthCare`.
3. **+ New** → **MySQL** database.
4. Web service **Variables** — copy from `railway.env.example`:
   - `DATABASE_URL` = `${{MySQL.MYSQL_URL}}`
   - `DEFAULT_URI` = `https://<your-generated-domain>.up.railway.app`
5. **Networking** → **Generate Domain**.
6. Wait for deploy; open `https://<domain>/health` → should return `{"status":"ok",...}`.

## What runs on deploy

| Step | Script |
|------|--------|
| Build | Docker: `composer install`, `assets:install` |
| Start | `railway-start.sh`: JWT keys, migrations (retry), cache warmup, PHP server on `$PORT` |
| Health | Railway pings `/health` |

## Optional: demo data

One-time on empty DB (Railway shell / CLI):

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

## Mobile app

Set `AppDev/src/app/api/config.ts`:

```ts
export const API_BASE = 'https://YOUR-DOMAIN.up.railway.app/api';
```
