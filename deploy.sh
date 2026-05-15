#!/bin/bash

echo "Pull latest code..."
git pull origin main || exit 1

echo "Restart container..."
sudo docker-compose up -d

echo "Laravel optimize..."
sudo docker-compose exec -T app php artisan migrate --force
sudo docker-compose exec -T app php artisan optimize:clear
sudo docker-compose exec -T app php artisan config:cache
sudo docker-compose exec -T app php artisan route:cache
sudo docker-compose exec -T app php artisan view:cache

echo "Cleanup Docker..."
sudo docker system prune -af --volumes
sudo docker builder prune -af

echo "Clear Docker logs..."
sudo find /var/lib/docker/containers/ -name "*-json.log" -exec truncate -s 0 {} \;

echo "Done!"