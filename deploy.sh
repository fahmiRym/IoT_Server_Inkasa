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

# Fix ownership/permissions of storage folder before any git operation
fix_storage_permissions() {
  log INFO "Fixing storage permissions..."
  # Change ownership to the current user (who runs the script)
  sudo chown -R $(whoami):$(whoami) storage
  # Ensure directories are writable and files readable
  find storage -type d -exec chmod 775 {} \;
  find storage -type f -exec chmod 664 {} \;
}


# ---------- 1. Pull latest code ----------
log INFO "[1/9] Pulling latest code from Git..."
# Ensure storage folder is writable before git operations
fix_storage_permissions

git reset --hard HEAD
if ! git pull origin main; then
  log ERROR "Failed to pull latest code"
  exit 1
fi

# ---------- 2. Build & start containers ----------
log INFO "[2/9] Building and starting Docker containers..."
# Docker CLI does not need sudo if user is in the docker group
if ! docker compose up -d --build; then
  log ERROR "Docker compose failed"
  exit 1
fi

# ---------- 3. Laravel Optimizations ----------
log INFO "[3/9] Running Laravel optimisations..."
# Docker commands can be run without sudo if the user belongs to the docker group
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

# ---------- 4. Database backup ----------
log INFO "[4/9] Creating database backup..."
# Ensure mysqldump exists inside the container
if ! docker compose exec -T app which mysqldump > /dev/null; then
  log ERROR "mysqldump not found in app container"
  exit 1
fi

# Load DB credentials from .env (inside container)
DB_USER=$(docker compose exec -T app printenv DB_USERNAME)
DB_PASS=$(docker compose exec -T app printenv DB_PASSWORD)
DB_HOST=$(docker compose exec -T app printenv DB_HOST)
DB_NAME=$(docker compose exec -T app printenv DB_DATABASE)
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="backup_${DB_NAME}_${TIMESTAMP}.sql"
BACKUP_PATH="storage/app/${BACKUP_FILE}"

# Build mysqldump command – use simple flags and handle empty password
PASS_OPT=""
if [ -n "$DB_PASS" ]; then
  PASS_OPT="--password=${DB_PASS}"
fi

# Build mysqldump command with proper flags
HOST_BACKUP_PATH="$(pwd)/storage/app/${BACKUP_FILE}"
CMD="mysqldump --user=${DB_USER} ${PASS_OPT} --host=${DB_HOST} ${DB_NAME}"
log INFO "Running backup command: $CMD > $HOST_BACKUP_PATH"
if ! docker compose exec -T app bash -c "$CMD" > "$HOST_BACKUP_PATH"; then
  log ERROR "Database backup failed"
  exit 1
fi
log SUCCESS "Database backup saved to $BACKUP_PATH"     

# ---------- 5. Cleanup Docker cache ----------
log INFO "[5/9] Cleaning Docker caches..."
# Docker commands without sudo (user must be in docker group)
docker system prune -af
docker builder prune -af

# ---------- 6. Cleanup Docker container logs ----------
log INFO "[6/9] Truncating Docker container logs..."
find /var/lib/docker/containers/ -name "*-json.log" -exec truncate -s 0 {} \;

# ---------- 7. Reload aplikasi ----------
log INFO "[7/9] Reloading application..."
# Clear Laravel config cache
docker compose exec -T app php artisan config:clear || { log ERROR "Config clear failed"; exit 1; }
# CATATAN: sensor:fetch & sensor:live (polling Blynk) DIHAPUS -> penguras kuota.
# Perekaman sensor lewat ESP32 push langsung ke POST /api/sensor/store.
# Penjadwalan Laravel ditangani supervisor (program: laravel-schedule = schedule:work).

# ---------- 8. Deployment status ----------
log SUCCESS "[8/9] Deployment completed!"

echo -e "${GREEN}------------------------------------------${NC}"
docker compose ps
echo -e "${GREEN}------------------------------------------${NC}"

echo -e "${GREEN}Disk usage:${NC}"
df -h /

# ---------- 9. Final separator ----------
echo -e "${GREEN}==========================================${NC}"