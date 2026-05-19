#!/bin/bash

# ==============================
# AUTO RUN - NOC COMMAND
# ==============================

GREEN='\033[0;32m'
BLUE='\033[0;34m'    
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}==========================================${NC}"
echo -e "${BLUE}   AUTO RUN - NOC COMMAND INKASA      ${NC}"
echo -e "${BLUE}==========================================${NC}"

# 1. Restart Compose
echo -e "${BLUE}[1/2] Restart Compose...${NC}"
sudo systemctl restart docker

# 2. Record Sensor Data Fetch & Live
echo -e "${BLUE}[2/2] Clear Cache & Restart Sensor...${NC}"
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