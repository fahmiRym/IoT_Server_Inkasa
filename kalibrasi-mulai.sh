#!/usr/bin/env bash
# ============================================================================
# Aktifkan mode kalibrasi hardware sensor:
#  1. Laravel maintenance mode  -> dashboard tampil halaman 503 kustom
#  2. ESP32 POST /api/sensor/store return 503 -> data kalibrasi TIDAK masuk DB
#  3. Telegram alert dari SensorController TIDAK trigger
#  4. Container motion-detector di-stop -> Telegram MOTION juga aman
#
# Jalankan dari /home/noc-suhu/IoT_Server_Inkasa
# ============================================================================
set -e

cd "$(dirname "$0")"

echo ">>> [1/2] Aktifkan Laravel maintenance mode ..."
docker compose exec -T app php artisan down --refresh=30

echo ">>> [2/2] Stop container motion-detector ..."
docker compose stop motion-detector

echo ""
echo "============================================================"
echo " MODE KALIBRASI AKTIF"
echo "============================================================"
echo " - Dashboard      : halaman maintenance kustom"
echo " - Data sensor    : TIDAK masuk DB (POST /api/sensor/store = 503)"
echo " - Telegram alert : DIMATIKAN (sensor + motion)"
echo ""
echo " Silakan kalibrasi hardware sekarang."
echo " Setelah selesai, jalankan: ./kalibrasi-selesai.sh"
echo "============================================================"
