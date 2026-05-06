#!/bin/bash
set -e

echo "Starting entrypoint script..."

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate
else
    # Check if key is base64 encoded (Laravel format)
    if [[ ! "$APP_KEY" =~ ^base64: ]]; then
        echo "Generating valid APP_KEY..."
        php artisan key:generate
    fi
fi

# Wait for database to be ready
echo "Waiting for database..."
until nc -z "$DB_HOST" 3306 || mysqladmin ping -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 2
done

echo "Database is up!"

# Run migrations if not already done
php artisan migrate --force --no-interaction

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Entrypoint completed successfully!"

exec "$@"
