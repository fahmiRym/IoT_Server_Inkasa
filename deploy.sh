#!/bin/bash

# Warna untuk output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}==========================================${NC}"
echo -e "${BLUE}   AUTO DEPLOY - NOC COMMAND INKASA      ${NC}"
echo -e "${BLUE}==========================================${NC}"

# 1. Pull latest code from Git
echo -e "${BLUE}[1/5] Menarik kode terbaru dari Git...${NC}"
git pull origin main || { echo -e "${RED}Gagal menarik kode dari Git${NC}"; exit 1; }

# 2. Rebuild and restart container
echo -e "${BLUE}[2/5] Membangun ulang Container Docker...${NC}"
sudo docker-compose up -d --build

# 3. Cleanup Docker & Logs (Mencegah Disk Full)
echo -e "${BLUE}[3/5] Membersihkan sisa build & mengosongkan log...${NC}"
sudo docker system prune -f
# Mengosongkan log Docker
sudo truncate -s 0 /var/lib/docker/containers/*/*-json.log
# Mengosongkan log Laravel (opsional, agar storage tidak penuh)
sudo truncate -s 0 ./storage/logs/*.log

# 4. Laravel Optimization
echo -e "${BLUE}[4/5] Optimasi Laravel & Migrasi...${NC}"
sudo docker-compose exec -T app php artisan migrate --force
sudo docker-compose exec -T app php artisan config:clear
sudo docker-compose exec -T app php artisan cache:clear

# 5. Check Status
echo -e "${GREEN}[5/5] Selesai! Mengecek status layanan...${NC}"
echo -e "${GREEN}------------------------------------------${NC}"
sudo docker-compose ps
echo -e "${GREEN}------------------------------------------${NC}"
sudo docker-compose exec -T app supervisorctl status
echo -e "${GREEN}==========================================${NC}"
