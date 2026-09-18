#!/usr/bin/env bash
# ============================================================================
# Selesai kalibrasi -> kembalikan sistem ke normal:
#  1. Clear cache alarm state -> sample pertama pasca-kalibrasi tidak trigger
#     alert palsu (state NORMAL/DANGER dari sebelumnya dibuang)
#  2. Nyalakan container motion-detector
#  3. Nonaktifkan Laravel maintenance mode
#
# Jalankan dari /home/noc-suhu/IoT_Server_Inkasa
# ============================================================================
set -e

cd "$(dirname "$0")"

echo ">>> [1/3] Clear cache (reset state alarm sensor.alarm.state) ..."
docker compose exec -T app php artisan cache:clear

echo ">>> [2/3] Start container motion-detector ..."
docker compose start motion-detector

echo ">>> [3/3] Matikan Laravel maintenance mode ..."
docker compose exec -T app php artisan up

echo ""
echo "============================================================"
echo " SISTEM NORMAL KEMBALI"
echo "============================================================"
echo " - Dashboard      : LIVE"
echo " - Data sensor    : ter-log ke DB"
echo " - Telegram alert : AKTIF (sensor + motion)"
echo ""
echo " Verifikasi:"
echo "  docker compose ps"
echo "  curl -s http://localhost/api/live-data | head"
echo "============================================================"
