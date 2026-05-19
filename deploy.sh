#!/bin/bash

# ==============================
# AUTO DEPLOY - NOC COMMAND
# ==============================

GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}==========================================${NC}"
echo -e "${BLUE}   AUTO DEPLOY - NOC COMMAND INKASA      ${NC}"
echo -e "${BLUE}==========================================${NC}"

# 1. Pull latest code
echo -e "${BLUE}[1/6] Menarik kode terbaru dari Git...${NC}"

git reset --hard HEAD
git pull origin main || {
    echo -e "${RED}Gagal menarik kode dari Git${NC}"
    exit 1
}

# 2. Jalankan container TANPA rebuild
echo -e "${BLUE}[2/6] Menjalankan Container Docker...${NC}"

sudo docker compose up -d || {
    echo -e "${RED}Gagal menjalankan container${NC}"
    exit 1
}

# 3. Laravel Optimization
echo -e "${BLUE}[3/6] Optimasi Laravel...${NC}"

sudo docker compose exec -T app php artisan migrate --force
sudo docker compose exec -T app php artisan optimize:clear
sudo docker compose exec -T app php artisan config:cache
sudo docker compose exec -T app php artisan route:cache
sudo docker compose exec -T app php artisan view:cache

# 4. Cleanup Docker Cache
echo -e "${BLUE}[4/6] Membersihkan cache Docker...${NC}"

sudo docker system prune -af
sudo docker builder prune -af

# 5. Cleanup Log Docker
echo -e "${BLUE}[5/6] Membersihkan log Docker...${NC}"

sudo find /var/lib/docker/containers/ \
-name "*-json.log" \
-exec truncate -s 0 {} \;

# 6. Status
echo -e "${GREEN}[6/6] Deployment selesai!${NC}"

echo -e "${GREEN}------------------------------------------${NC}"
sudo docker compose ps
echo -e "${GREEN}------------------------------------------${NC}"

echo -e "${GREEN}Storage:${NC}"
df -h /

echo -e "${GREEN}==========================================${NC}"

# 7. Clear Cache And Restart Sensor Record
echo -e "${BLUE}[7/7] Clear Cache & Restart Sensor...${NC}"
sudo docker compose exec app php artisan config:clear || 
{
    echo -e "${RED}Gagal mengeksekusi config clear${NC}"
    exit 1
}
sudo docker compose exec app php artisan sensor:fetch || 
{
    echo -e "${RED}Gagal mengeksekusi sensor fetch${NC}"
    exit 1
}
screen -S sensor -dm php artisan sensor:live || {
    echo -e "${RED}Gagal mengeksekusi sensor live${NC}"
    exit 1
}