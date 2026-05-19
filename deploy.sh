#!/usr/bin/env bash

# ---------------------------------------------------------------
# AUTO DEPLOY - NOC COMMAND INKASA
# ---------------------------------------------------------------
# This script pulls the latest code, rebuilds Docker containers,
# runs Laravel optimizations, creates a database backup, cleans up
# Docker artefacts, and restarts the sensor service.
# ---------------------------------------------------------------

set -euo pipefail   # Abort on error, undefined var, or pipeline failure

# ---------- Color helpers ----------
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}==========================================${NC}"
echo -e "${BLUE}   AUTO DEPLOY - NOC COMMAND INKASA   ${NC}"
echo -e "${BLUE}==========================================${NC}"

log() {
  local level="$1"
  local msg="$2"
  local color="$BLUE"
  case "$level" in
    INFO) color="$BLUE" ;;
    SUCCESS) color="$GREEN" ;;
    WARN) color="$RED" ;;
    ERROR) color="$RED" ;;
  esac
  echo -e "${color}[$level] $msg${NC}"
}

# ---------- 1. Pull latest code ----------
log INFO "[1/8] Pulling latest code from Git..."
git reset --hard HEAD
if ! git pull origin main; then
  log ERROR "Failed to pull latest code"
  exit 1
fi

# ---------- 2. Build & start containers ----------
log INFO "[2/8] Building and starting Docker containers..."
if ! sudo docker compose up -d --build; then
  log ERROR "Docker compose failed"
  exit 1
fi

# ---------- 3. Laravel Optimizations ----------
log INFO "[3/8] Running Laravel optimisations..."
sudo docker compose exec -T app php artisan migrate --force
sudo docker compose exec -T app php artisan optimize:clear
sudo docker compose exec -T app php artisan config:cache
sudo docker compose exec -T app php artisan route:cache
sudo docker compose exec -T app php artisan view:cache

# ---------- 4. Database backup ----------
log INFO "[4/8] Creating database backup..."
# Ensure mysqldump exists inside the container
if ! sudo docker compose exec -T app which mysqldump >/dev/null; then
  log ERROR "mysqldump not found in app container"
  exit 1
fi

# Load DB credentials from .env (inside container)
DB_USER=$(sudo docker compose exec -T app printenv DB_USERNAME)
DB_PASS=$(sudo docker compose exec -T app printenv DB_PASSWORD)
DB_HOST=$(sudo docker compose exec -T app printenv DB_HOST)
DB_NAME=$(sudo docker compose exec -T app printenv DB_DATABASE)
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="backup_${DB_NAME}_${TIMESTAMP}.sql"
BACKUP_PATH="storage/app/${BACKUP_FILE}"

# Build mysqldump command – use --skip-ssl for MariaDB client
PASS_OPT=""
if [ -n "$DB_PASS" ]; then
  PASS_OPT="--password=\"$DB_PASS\""
fi

CMD="mysqldump --skip-ssl --user=\"$DB_USER\" $PASS_OPT --host=\"$DB_HOST\" \"$DB_NAME\" > \"$BACKUP_PATH\""

if ! sudo docker compose exec -T app bash -c "$CMD"; then
  log ERROR "Database backup failed"
  exit 1
fi
log SUCCESS "Database backup saved to $BACKUP_PATH"

# ---------- 5. Cleanup Docker cache ----------
log INFO "[5/8] Cleaning Docker caches..."
sudo docker system prune -af
sudo docker builder prune -af

# ---------- 6. Cleanup Docker container logs ----------
log INFO "[6/8] Truncating Docker container logs..."
sudo find /var/lib/docker/containers/ -name "*-json.log" -exec truncate -s 0 {} \;

# ---------- 7. Restart sensor service ----------
log INFO "[7/8] Restarting sensor service..."
# Clear Laravel config cache first
sudo docker compose exec app php artisan config:clear || { log ERROR "Config clear failed"; exit 1; }
# Fetch latest sensor data
sudo docker compose exec app php artisan sensor:fetch || { log ERROR "Sensor fetch failed"; exit 1; }
# Run sensor live inside a detached screen session
if ! sudo docker compose exec app screen -S sensor -dm php artisan sensor:live; then
  log ERROR "Failed to start sensor live"
  exit 1
fi

# ---------- 8. Deployment status ----------
log SUCCESS "[8/8] Deployment completed!"

echo -e "${GREEN}------------------------------------------${NC}"
sudo docker compose ps
echo -e "${GREEN}------------------------------------------${NC}"

echo -e "${GREEN}Disk usage:${NC}"
df -h /

echo -e "${GREEN}==========================================${NC}"