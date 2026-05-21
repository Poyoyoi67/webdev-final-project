# JWT keys (Lexik)

These files are **not** committed (see `.gitignore`):

- `private.pem` — signing key (encrypted with `JWT_PASSPHRASE` from `.env`)
- `public.pem` — verification key

## Generate keys

**Windows (PowerShell):**

```powershell
.\scripts\generate-jwt-keys.ps1
php bin/console cache:clear
```

**Linux / macOS:**

```bash
mkdir -p config/jwt
export JWT_PASSPHRASE="$(grep '^JWT_PASSPHRASE=' .env | cut -d= -f2-)"
openssl genrsa -aes256 -passout pass:"$JWT_PASSPHRASE" -out config/jwt/private.pem 4096
openssl rsa -pubout -in config/jwt/private.pem -passin pass:"$JWT_PASSPHRASE" -out config/jwt/public.pem
php bin/console cache:clear
```

Or (if OpenSSL works in PHP):

```bash
php bin/console lexik:jwt:generate-keypair --overwrite
```

## Verify API login

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"patient@health.com","password":"patient123"}'
```

Expected: JSON with a `token` field.

Patient demo account: `patient@health.com` / `patient123` (after `doctrine:fixtures:load`).
