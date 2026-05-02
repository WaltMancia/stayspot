#!/bin/sh
set -e

echo "🚀 StaySpot backend starting..."

# Genera APP_KEY si no existe
if [ -z "$APP_KEY" ]; then
    echo "⚙️  Generating APP_KEY..."
    php artisan key:generate --force
fi

# Espera MySQL con reintentos
echo "⏳ Waiting for MySQL..."
MAX_TRIES=30
COUNT=0
until php artisan migrate:status > /dev/null 2>&1; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "❌ MySQL not ready after $MAX_TRIES tries. Exiting."
        exit 1
    fi
    echo "   Retry $COUNT/$MAX_TRIES..."
    sleep 3
done
echo "✅ MySQL ready!"

# Migraciones
echo "📦 Running migrations..."
php artisan migrate --force

# Seeders si la BD está vacía
USER_COUNT=$(php -r "
    require '/var/www/html/vendor/autoload.php';
    \$app = require '/var/www/html/bootstrap/app.php';
    \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    echo \App\Models\User::count();
" 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed --force
fi

# Cache de producción
echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Ready!"
exec "$@"