#!/bin/sh
set -e

cd "$(dirname "$0")/.."

echo "==> HealthCare Railway boot (APP_ENV=${APP_ENV:-prod})"

if [ -z "$APP_SECRET" ]; then
  echo "ERROR: APP_SECRET is not set. Add it in Railway Variables."
  exit 1
fi

if [ -z "$DATABASE_URL" ]; then
  echo "ERROR: DATABASE_URL is not set. Link MySQL or add DATABASE_URL in Railway."
  exit 1
fi

mkdir -p var/cache var/log config/jwt
chmod -R 777 var

if [ ! -f .env ]; then
  echo "==> Creating .env from docker/.env.docker (Railway variables override these)..."
  cp docker/.env.docker .env
fi

echo "==> Installing Symfony bundle assets..."
php bin/console assets:install public --no-interaction --env=prod

# JWT keys (gitignored; created on first deploy)
if [ ! -f config/jwt/private.pem ]; then
  if [ -z "$JWT_PASSPHRASE" ]; then
    echo "ERROR: JWT_PASSPHRASE is not set in Railway Variables."
    exit 1
  fi
  echo "==> Generating JWT key pair..."
  openssl genrsa -aes256 -passout "pass:$JWT_PASSPHRASE" -out config/jwt/private.pem 4096
  openssl rsa -pubout -in config/jwt/private.pem -passin "pass:$JWT_PASSPHRASE" -out config/jwt/public.pem
fi

export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"

echo "==> Running database migrations (with retry)..."
attempt=1
max_attempts=10
until php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; do
  if [ "$attempt" -ge "$max_attempts" ]; then
    echo "ERROR: Database migrations failed after $max_attempts attempts."
    exit 1
  fi
  echo "Database not ready (attempt $attempt/$max_attempts), retrying in 5s..."
  attempt=$((attempt + 1))
  sleep 5
done

echo "==> Warming production cache..."
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

PORT="${PORT:-8000}"
echo "==> Starting Symfony on 0.0.0.0:${PORT}"
exec php -S "0.0.0.0:${PORT}" -t public
