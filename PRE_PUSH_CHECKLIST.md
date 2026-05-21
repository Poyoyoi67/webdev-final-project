# Pre-push checklist (Railway)

- [ ] `.env` is **not** in `git status` (secrets stay local)
- [ ] `railway.env.example` reviewed — you will paste vars into Railway after deploy
- [ ] New production `APP_SECRET` and `JWT_PASSPHRASE` generated (not copied from local `.env`)
- [ ] MySQL will be added on Railway; `DATABASE_URL=${{MySQL.MYSQL_URL}}`
- [ ] After first deploy, set `DEFAULT_URI` in Railway to your public URL (optional, for emails/links)

```powershell
cd HealthCare
git status
# .env must NOT appear

git add .
git commit -m "Railway-ready HealthCare deployment"
git push -u origin main
```

Health check URL after deploy: `https://YOUR-DOMAIN.up.railway.app/health`
