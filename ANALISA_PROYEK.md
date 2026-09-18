# 📊 ANALISA PROYEK — NOC Dashboard Inkasa

> Dokumen analisa dibuat: **2026-08-12**
> Branch: `main` · Framework: **Laravel 12.48.1** · PHP `^8.2`

---

## 1. Ringkasan Singkat

**NOC Dashboard** adalah aplikasi monitoring **suhu, kelembaban, asap, dan api** ruang server Inkasa. Sensor (ESP32 + DHT + MQ-2/3 + flame sensor) mengirim data ke backend Laravel, ditampilkan real-time di dashboard, dengan alert Telegram, live CCTV (HLS via mediamtx), dan motion detector YOLO.

Stack utama:

| Layer | Tools |
|---|---|
| Backend | Laravel 12, PHP 8.3-fpm, MySQL 8 |
| Frontend | Blade + Chart.js + hls.js (tanpa build framework berat) |
| Streaming | mediamtx (RTSP → HLS LL) + hls.js |
| Motion AI | Python + YOLO (motion-detector container) |
| Notif | Telegram Bot API |
| Export | maatwebsite/excel + barryvdh/laravel-dompdf |
| Deploy | Docker Compose (app / db / nginx / mediamtx / tunnel / motion) |
| Tunnel | Cloudflare Tunnel → `noc-room.inkalum.com` |
| PWA | `public/sw.js` (installable, offline shell) |

---

## 2. Arsitektur (Container)

```
                   ┌───────────────────────┐
   ESP32 sensor ──►│ /api/sensor/store     │
                   │  (SensorController)   │
                   └──────────┬────────────┘
                              │ insert
                              ▼
                        ┌──────────┐
                        │  MySQL   │◄──────── DatabaseController
                        └────┬─────┘          (mysqldump / mysql restore)
                             │
                             ▼
       ┌─────────────────────────────────────┐
       │  DashboardController::getLiveMetrics│  ← polling 3-5 detik
       │  + Cache 2m stats / 5m total_count  │
       └─────────────────┬───────────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │  dashboard.blade.php (Chart.js)│
        │  + CCTV player (hls.js)        │
        │  + Login / Profile page        │
        │  + PWA (sw.js manifest)        │
        └────────────────────────────────┘
                         ▲
                         │ HLS /cctv-hls/*
              nginx (reverse proxy) ──► mediamtx (RTSP→HLS-LL)
                         ▲                          ▲
                         │                          │
                    Cloudflare Tunnel        CCTV (RTSP)
                                                   │
                                                   ▼
                                     motion-detector (YOLO)
                                              │
                                              ▼
                                        Telegram (alert)
```

---

## 3. Struktur Direktori Kunci

```
noc-dashboard/
├── app/Http/Controllers/
│   ├── AuthController.php        # login + profile + foto avatar
│   ├── DashboardController.php   # /api/live-data + export CSV/PDF
│   ├── SensorController.php      # POST /api/sensor/store (API-key)
│   └── DatabaseController.php    # backup & restore SQL (Windows/Linux aware)
├── app/Models/
│   ├── SensorLog.php             # temp, hum, smoke, fire, smoke1..3, flame1..2
│   └── User.php                  # + username + photo (avatar)
├── database/migrations/
│   ├── 2026_01_23  create_sensor_logs
│   ├── 2026_03_04  add fire
│   ├── 2026_03_11  add multi sensors (smoke1..3, flame1..2)
│   ├── 2026_06_15  add username & photo ke users
├── resources/views/
│   ├── dashboard.blade.php       # main dashboard + CCTV + chart
│   ├── auth/{login, profile}.blade.php
│   └── reports/{sakti_excel, sensor_pdf}.blade.php
├── docker/
│   ├── nginx.conf                # + /cctv-hls/ reverse proxy
│   ├── entrypoint.sh, supervisord.conf, php-uploads.ini
├── motion-detector/              # container Python YOLO
├── mediamtx/mediamtx.yml         # RTSP→HLS LL config
└── routes/web.php                # semua route (auth-protected group)
```

---

## 4. Fitur Yang Sudah Berjalan

- ✅ **Login username/email + remember me** (`AuthController::login`)
- ✅ **Profile edit + upload avatar** (disk `public`)
- ✅ **Dashboard real-time** (`/api/live-data` di-cache Redis-friendly)
- ✅ **Multi-sensor** (3 smoke + 2 flame) dengan agregasi `smoke = max()`, `fire = OR()`
- ✅ **Debounce Telegram alert** — trigger hanya saat state NORMAL↔DANGER berubah (2 sample streak)
- ✅ **Export Excel** dengan grouping otomatis (5m / 15m / 1h / 1d) sesuai rentang
- ✅ **Export PDF** dengan 2 chart (Air Quality + Env Trend) via quickchart.io
- ✅ **Backup / Restore SQL** via `mysqldump` / `mysql` — deteksi biner Windows vs Linux
- ✅ **CCTV LL-HLS** (delay ~1-3 detik) di-proxy via nginx → mediamtx
- ✅ **Motion detector YOLO** → snapshot + video 8s → Telegram
- ✅ **PWA installable** (`public/sw.js`, offline shell)
- ✅ **Cloudflare Tunnel** → `noc-room.inkalum.com` (tanpa expose port publik)

---

## 5. Yang Masih Perlu Diperhatikan (Backlog / Utang Teknis)

| Prioritas | Item | Catatan |
|---|---|---|
| 🔴 HIGH | Rotasi kredensial | `TELEGRAM_BOT_TOKEN`, `BLYNK_TOKEN`, `SENSOR_API_KEY`, `CF_TUNNEL_TOKEN` bocor di `.env` (ter-commit? cek dgn `git ls-files .env`) |
| 🔴 HIGH | Password `alhamdulillah` di dokumen publik | ganti sebelum push ke repo publik |
| 🟡 MED | `SensorLog` tanpa `fillable` (`$guarded=[]`) | rentan mass-assignment kalau route ditambah tanpa validasi |
| 🟡 MED | Cache key `daily_sensor_stats` tidak reset per tanggal | statistik hari kemarin bisa nyantol max 2 menit setelah pergantian hari |
| 🟡 MED | Belum ada `.env.example` yang sesuai | onboarding developer baru repot |
| 🟢 LOW | Tidak ada test | `tests/` masih default Laravel |
| 🟢 LOW | Tidak ada rate-limit di `/api/sensor/store` | selain API-key, tidak ada throttle |
| 🟢 LOW | Halaman error kustom (404, 500) belum ada | hanya default Laravel |
| 🟢 LOW | Halaman **maintenance** kustom belum ada | ← **dibuat sekarang** (503.blade.php) |

---

## 6. Halaman Maintenance (Baru)

Laravel 12 menggunakan **`resources/views/errors/503.blade.php`** sebagai tampilan default saat aplikasi masuk mode maintenance.

**Cara aktifkan (local test):**
```bash
php artisan down --refresh=15
```

**Cara aktifkan dengan bypass (biar tetap bisa akses saat kalibrasi):**
```bash
php artisan down --secret="kalibrasi-noc-2026" --refresh=30
# lalu buka: http://127.0.0.1:8000/kalibrasi-noc-2026
```

**Cara matikan:**
```bash
php artisan up
```

**Di production (Docker):**
```bash
docker-compose exec app php artisan down --refresh=30
docker-compose exec app php artisan up
```

---

## 7. Cara Test Lokal (Laragon)

```powershell
# 1. Pastikan MySQL Laragon aktif, DB `noc_monitor` ada
# 2. Install deps (kalau belum)
composer install
npm install; npm run build

# 3. Jalankan server
php artisan serve --port=8000

# 4. Test maintenance page
php artisan down --refresh=15
# buka http://127.0.0.1:8000

# 5. Selesai kalibrasi
php artisan up
```

---

## 8. Roadmap Pengembangan (Saran)

**Fase 1 — Stabilisasi** (1-2 hari)
- [ ] Rotasi semua kredensial di `.env` + hapus dari git history (`git filter-repo`)
- [ ] Tambah `.env.example` dengan placeholder aman
- [ ] Tambah `fillable` eksplisit di `SensorLog`
- [ ] Rate-limit `POST /api/sensor/store` (mis. 60/min per IP)
- [ ] Halaman error 404/500 kustom (senada dgn tema cyber)

**Fase 2 — Feature** (1-2 minggu)
- [ ] User roles (admin / viewer) — sekarang semua user bisa import DB
- [ ] Audit log (siapa export, siapa restore, siapa edit profile)
- [ ] Threshold per sensor bisa diubah dari UI (bukan hanya `.env`)
- [ ] Notifikasi push PWA (Web Push API) — bukan hanya Telegram
- [ ] Dashboard multi-lokasi (kalau ada NOC cabang lain)

**Fase 3 — Reliability** (ongoing)
- [ ] Test PHPUnit untuk `SensorController::store` (auth, validation, telegram debounce)
- [ ] Health-check endpoint `/up` sudah ada dari Laravel 12 — sambungkan ke uptime monitor (mis. Uptime Kuma)
- [ ] Backup DB otomatis (cron) → simpan ke storage cloud (bukan cuma manual)
- [ ] Log rotation storage (`storage/app/motion/*.mp4` bisa penuh)

---

## 9. Referensi Cepat

- Deploy guide → [DEPLOY.md](DEPLOY.md)
- ESP32 hemat kuota Blynk → [docs/esp32_hemat_kuota.ino](docs/esp32_hemat_kuota.ino)
- Motion detector → [motion-detector/detect.py](motion-detector/detect.py)
- Nginx config → [docker/nginx.conf](docker/nginx.conf)
- MediaMTX config → [mediamtx/mediamtx.yml](mediamtx/mediamtx.yml)

---

**Kesimpulan:** proyek sudah cukup mature untuk production-lite (1 ruang server). Sebelum di-scale ke multi-lokasi atau di-open source-kan, fokus dulu ke **kredensial hygiene**, **rate-limit**, dan **role-based access**. Sisanya sudah oke.
