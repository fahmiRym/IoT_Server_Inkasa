# 🚀 PROGRESS — Penyempurnaan NOC Dashboard

> Dokumen ini melacak progress penyempurnaan bertahap.
> **Selalu update file ini** setiap kali menyelesaikan atau memulai item baru,
> agar sesi berikutnya bisa lanjut dari titik yang tepat.

**Terakhir diperbarui:** 2026-09-17 (sesi 3 — fix responsif overflow)
**Branch:** `main` · **Framework:** Laravel 12 · **PHP** ^8.2

---

## 🚀 DEPLOY PRODUCTION (2026-10-16 — commit `b04f7bc`)

**Fitur deployed:**
- SYS_CLOCK cyber digital (replace clock header → lebih space)
- Master Log Stream fix overflow (icon-only status)
- Server-Check Reminder system end-to-end:
  - Command `noc:server-check-reminder` scheduled daily 15:00 WIB
  - Scan motion/ dir — kalau 0 motion → kirim Telegram "HIMBAUAN CEK SERVER"
  - UI panel inline di Master Log card
  - Tombol SUDAH DICEK → clear + Telegram konfirmasi
  - Flag `acked_today` → tidak spam reminder lagi hari itu
- Debug command `noc:server-check` (status/clear/ack-reset)

**Deploy flow:**
1. `git pull` di server → `b04f7bc` fast-forward (973 lines +)
2. `docker compose build app` (14s)
3. `docker compose up -d app` (downtime ~3s)
4. `config:clear + view:clear + route:clear + config:cache + route:cache`

**Verifikasi:**
```
/ → 302 (login redirect) · 0.73s
/login → 200
/up → 200 (health)
/api/server-check/status → 401 (expected tanpa auth)
Routes: api/server-check/status + api/server-check/ack ✓
Commands: noc:server-check + noc:server-check-reminder ✓
```

**Catatan:** tidak ada migration baru di release ini. SMTP masih MAIL_MAILER=log
(fallback OTP ke Telegram sudah live sejak hotfix 073a1b6).

**Config server (sudah ada):**
- CACHE_STORE=database ✓ (penting untuk flag server-check persist)
- VAPID keys ✓
- TELEGRAM_* ✓

---

## 🔧 HOTFIX POST-DEPLOY (2026-09-18 — commit `073a1b6`)

**Masalah:** Superadmin (Fahmi) tidak bisa login walau password + OTP benar.

**Root cause:** `.env` prod pakai `MAIL_MAILER=log`. Email OTP HANYA di-tulis ke
`storage/logs/laravel.log` — TIDAK dikirim ke inbox real. Superadmin tidak pernah dapat
kode di Gmail.

**Fix** ([AuthController.php `generateAndSendOtp`](app/Http/Controllers/AuthController.php)):
- `try/catch` di `Mail::send` supaya tidak crash kalau SMTP error
- **Auto-fallback**: kalau `MAIL_MAILER=log`, kirim OTP ke Telegram bot yang sudah
  aktif untuk alarm sensor. Format Markdown: user, kode (backtick), expiry, waktu
- Bisa juga force paralel Email + Telegram via `NOC_OTP_TELEGRAM=1` di `.env`
- Aman: chat Telegram bot NOC hanya bisa dilihat admin

**Deploy:** `git pull` → `docker build app` (20s) → `up -d` → `config:clear + view:clear +
route:clear + config:cache`

**Cara pakai:** login pakai `fahmi` / `bismillah` → kode OTP muncul di chat Telegram bot NOC

**Follow-up (belum critical):** setup SMTP proper (Gmail App Password/Postmark) untuk kirim
OTP ke inbox real, terutama kalau ada admin baru yang belum akses Telegram bot.

---

## 🚀 DEPLOY PRODUCTION (2026-09-18 — Round 11: Live)

**Target:** `noc-suhu@192.168.11.178` · `/home/noc-suhu/IoT_Server_Inkasa`
**Domain publik:** `https://noc-room.inkalum.com` (via Cloudflare Tunnel)
**Commit:** `5578646` — feat: major release — auth, PWA, reports, push, role, sec, UI polish

### 📦 Ringkasan Deploy
- 69 files changed · +10,715 · −925 lines
- Method: **git push + git pull** (bukan pscp file-by-file — terlalu banyak file)

### 🔧 Langkah yang Dijalankan
1. **Local** — `.gitignore` update (exclude `cloudflared.exe`, `deploy-maintenance.ps1`,
   `_finish-deploy.txt`, `.claude/`, `noc-dashboard.zip`)
2. **Local** — `git add -A` + `git commit` (single big commit) + `git push origin main`
3. **Server** — Backup DB: `mysqldump` via `docker compose exec -T db` → `backup_pre_deploy_20260918_024458.sql` (161 MB)
4. **Server** — `git stash --include-untracked` (stash all `.bak.*` + pscp-deployed files)
5. **Server** — `git pull origin main` (Fast-forward `e8f657a..5578646`)
6. **Server** — `docker compose build app` (23s, image `iot_server_inkasa-app:latest`)
7. **Server** — `docker compose up -d app` (recreate container, downtime ~4s)
8. **Server** — `.env` fixes:
   - `APP_ENV=local` → **production**
   - `APP_DEBUG=true` → **false**
   - Append `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` via `noc:generate-vapid --append`
   - Sync via `docker exec cat > .env.new && mv` (bind-mount inode issue workaround)
9. **Server** — `docker compose restart app` + `config:clear && view:clear && config:cache && route:cache`

### ✅ Migrasi Terapply (Batch 4 baru)
- `2026_09_18_000001_rename_operator_role_to_admin` — enum role updated, data migrated
- `2026_09_18_000002_create_push_subscriptions_table` — 9 kolom siap terima subscription

**Verifikasi role:** `{"role":"superadmin","c":1},{"role":"admin","c":2}` — rename OK

### ✅ Verifikasi Publik
```
GET https://noc-room.inkalum.com/ ................ 302 (redirect to login) · 0.196s
GET /login ..................................... 200
GET /up ........................................ 200 (health)
GET /maintenance.html .......................... 200 (fallback)
GET /icons/icon-192.png ........................ 200 (PWA icon)
GET /css/noc-theme.css ......................... 200 (theme baru)
GET /js/noc-cyber.js ........................... 200 (cyber UI library)
GET /js/noc-pwa.js ............................. 200 (PWA library)
```

### 📋 Post-Deploy TODO (belum critical)
- [ ] Install extension `intl` di Dockerfile (untuk `Number::format` — hanya kena `db:table` command)
- [ ] User akses browser → buka `/devices` → aktifkan **NOTIFIKASI** → test `php artisan noc:test-push --danger`
- [ ] Rotasi password default `bismillah` di NocUsersSeeder (semua user WAJIB ganti password)
- [ ] Bersihkan file `.bak.*` di server (stash bisa di-drop kalau tidak diperlukan)

### 🛡 Rollback Plan (kalau ada masalah)
1. `mysql < backup_pre_deploy_20260918_024458.sql` (restore DB pre-migrate)
2. `git reset --hard e8f657a && docker compose build app && docker compose up -d app`
3. `sed -i 's/APP_ENV=production/APP_ENV=local/' .env && sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env`

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-18 — Round 10: Web Push End-to-End Live)

### 🔧 Fix VAPID Generator (openssl Windows issue)
[app/Console/Commands/GenerateVapidKeys.php](app/Console/Commands/GenerateVapidKeys.php)

**Masalah sebelumnya:** `openssl_pkey_new` gagal di Windows Laragon dgn error
`No such process` — karena PHP openssl butuh `openssl.cnf` yg tidak selalu ter-config.

**Fix:** Command sekarang PREFER pakai `Minishlink\WebPush\VAPID::createVapidKeys()`
(library web-push sudah handle EC key generation dgn library sendiri, tidak butuh openssl.cnf).
Fallback openssl PHP native masih ada + auto-detect openssl.cnf di beberapa lokasi umum Laragon.

### 📤 Backend Push Sender (baru)
[app/Services/NocPushSender.php](app/Services/NocPushSender.php)

- `broadcast(array $payload)` — kirim ke semua subscription aktif
- Auto-handle expired subscriptions (HTTP 404/410 → hapus dari DB)
- Update `last_used_at` saat sukses kirim
- Log warning kalau VAPID belum di-set / library belum diinstal (silent fail)
- Payload: `{title, body, severity, url, tag}` — sesuai format SW handler

### 🔗 Integrate ke SensorController
[app/Http/Controllers/SensorController.php](app/Http/Controllers/SensorController.php)

Method baru `sendWebPush($isDanger, $data)` dipanggil setiap kali state alarm berubah
(NORMAL ↔ DANGER):

- **Transisi ke DANGER**: push dengan `title: "🚨 ALARM: NOC Command Center"`,
  body detail alasan (FIRE, Suhu XX°C, Asap YYY PPM), severity=danger →
  SW show notification dengan `requireInteraction: true` + vibrate
- **Transisi ke NORMAL**: push "✅ NOC Kembali NORMAL" severity=info

### 🧪 Test Command
[app/Console/Commands/TestPush.php](app/Console/Commands/TestPush.php)

```bash
php artisan noc:test-push                                  # push info default
php artisan noc:test-push --danger                         # push severity danger
php artisan noc:test-push --title="TEST" --body="hello"    # custom message
```

Berguna untuk verify VAPID + subscription setup sebelum menunggu alarm real.

### 📋 Cara Setup Push (untuk user)

```bash
# 1. Generate VAPID keys (di server prod atau local)
php artisan noc:generate-vapid --append

# 2. Clear config cache
php artisan config:clear

# 3. Buka /devices di browser → klik "AKTIFKAN NOTIFIKASI"
#    (Grant permission → subscribe otomatis)

# 4. Test kirim
php artisan noc:test-push --danger

# 5. Notif muncul di system tray/lock screen device
#    Alarm asli otomatis trigger saat sensor melewati batas
```

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-18 — Round 9: Nav Lengkap + VAPID Command + Composer Note)

### 🧭 Navigation Complete
- [users/index.blade.php](resources/views/users/index.blade.php) — tambah link **Devices** di topbar
- [users/form.blade.php](resources/views/users/form.blade.php) — tambah link **Devices** di topbar
- [auth/profile.blade.php](resources/views/auth/profile.blade.php) — refactor topbar dari 2 tombol
  (Dashboard + Logout) → **5 tombol** (Kembali · Dashboard · Users [superadmin] · Devices · Logout),
  styling konsisten dgn users/index

### 🔑 VAPID Generator Command (fallback tanpa Node)
File: [app/Console/Commands/GenerateVapidKeys.php](app/Console/Commands/GenerateVapidKeys.php)

```bash
php artisan noc:generate-vapid              # print keys ke stdout
php artisan noc:generate-vapid --append     # auto-tulis ke .env
```

- Pure PHP + openssl (tidak butuh Node.js / web-push CLI)
- Generate P-256 EC key pair (standar VAPID)
- Encode base64 URL-safe (format yang dipakai PushManager.subscribe)
- Auto-append ke .env dengan flag `--append` (VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT)

### ⚠ Composer web-push Install Issue

**Error yang muncul:**
```
maennchen/zipstream-php 3.2.1 requires php-64bit ^8.3
- your php-64bit version (8.2.12) does not satisfy that requirement.
```

**Penyebab:** `maatwebsite/excel` → `phpspreadsheet` → `zipstream-php` — Composer coba
resolve ke `zipstream-php 3.2.1` (butuh PHP 8.3), padahal PHP di Laragon Anda 8.2.

**Fix:** pin `zipstream-php` ke versi 2.x yang support PHP 8.2:
```bash
composer require minishlink/web-push:^9 maennchen/zipstream-php:^2.1 -W
```

Kalau masih error, coba lock manual dulu:
```bash
composer require maennchen/zipstream-php:^2.1
composer require minishlink/web-push:^9
```

**Alternatif tanpa install package:** infrastruktur push (endpoint subscribe, tabel,
SW handler, VAPID generator) sudah SIAP semua. Package `minishlink/web-push` cuma
diperlukan untuk **backend sender** (kirim push dari server saat alarm). Bisa di-defer
sampai butuh — semua yang lain (subscribe/unsubscribe/toggle di UI) sudah jalan.

### 🔄 Cache Buster
- `UI_VER` → `2026-09-18-r9`

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-18 — Round 8: Fix Master Log Stream Overflow)

**Masalah:** Kolom "STATUS" di Master Log Stream ter-cut (badge "SECURE" jadi "SEC"),
kolom "IDENTIFIER_TIME" panjang bikin table overflow di kolom kanan yang sempit.

### 🔧 Fix
- [dashboard.blade.php](resources/views/dashboard.blade.php):
  - Header column dipersingkat: `IDENTIFIER_TIME` → `WAKTU`, `TEMP/HUM/AQS` → `°C/%/PPM`,
    `STATUS` → `ST` (title attr untuk tooltip)
  - Table pakai `table-layout: fixed` + explicit column widths (`.col-time 38%`,
    `.col-num 18%`, `.col-stat 12%`) — konsisten walau content beda-beda
  - Padding cell dikompres: `9px 6px` (dari `11px 14px`)
  - Font `.68rem` di sempit, `.78rem` di ≥1300px
  - `white-space: nowrap + text-overflow: ellipsis` — safety
- Status column baru: **`.log-status`** pill icon-only 22×22px lingkaran
  - `.ok` = hijau ✓ (SECURE)
  - `.warn` = kuning ⚠ (MENDEKATI AMBANG — 90% threshold)
  - `.err` = merah ⚠ (CRITICAL — temp/smoke over atau flame)
  - Full text status di attr `title` (tooltip on hover) + tiap row `title` juga menampilkan detail
- JS `updateCoreLogs()` diupdate:
  - Kelas warning baru (mendekati threshold — bantu deteksi dini)
  - Format waktu shorter `hh:mm:ss` explicit
  - Tooltip lengkap di `<tr title>` — hover row lihat semua nilai + status label

**Hasil:** Log stream muat penuh di kolom kanan tanpa horizontal scrollbar. Icon status
lebih glanceable, warning tier baru (kuning) bantu operator antisipasi sebelum critical.

### 🔄 Cache Buster
- `UI_VER` → `2026-09-18-r8`

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-18 — Round 7: Rename Role + PWA Android Enhance)

### 🔄 Rename Role `operator` → `admin`
- Migration baru [`2026_09_18_000001_rename_operator_role_to_admin.php`](database/migrations/2026_09_18_000001_rename_operator_role_to_admin.php)
  — 3-step untuk MySQL (expand enum → UPDATE data → shrink enum); SQLite hanya UPDATE data
- [UserController.php](app/Http/Controllers/UserController.php) — validation rules `in:superadmin,admin`
- [AuthController.php](app/Http/Controllers/AuthController.php) — komentar "Operator" → "Admin"
- [users/index.blade.php](resources/views/users/index.blade.php) — badge, dropdown filter, class `.role-badge.admin`, icon `fa-user-shield`
- [users/form.blade.php](resources/views/users/form.blade.php) — radio role option `admin` + deskripsi lebih detail
- [dashboard.blade.php](resources/views/dashboard.blade.php) — user-chip badge OPERATOR → ADMIN
- [noc-theme.css](public/css/noc-theme.css) — `.badge-operator` → `.badge-admin`

**Cara apply di server:** `php artisan migrate` (di container: `docker compose exec -T app php artisan migrate --force`)

### 📱 PWA — Android/Mobile App-like Enhancement

**Manifest** ([public/manifest.webmanifest](public/manifest.webmanifest))
- Tambah `id`, `display_override`, `lang`, `categories`
- **App shortcuts** (long-press icon di home screen): Live · CCTV · Logs · Profil
- Screenshots wide + narrow (untuk install prompt Google Play-style)
- `share_target`: NOC bisa jadi target share (kirim URL/text ke NOC dari app lain)
- `handle_links: preferred`

**Library baru** [public/js/noc-pwa.js](public/js/noc-pwa.js) — global `window.NocPwa`
- **Install prompt banner** custom (dismiss 7 hari, animasi slide-in) — non-intrusive
- **iOS Safari hint** — muncul instruksi "tap share > Add to Home Screen"
- **Online/Offline detection** — auto NocCyber toast (`WARNING: OFFLINE MODE` / `SUCCESS: ONLINE`)
- **SW update notification** — modal confirm saat SW baru terinstal
- **Vibrate on alarm** — `NocPwa.alarmVibrate()` (patterned 200-100-200-100-400)
- **Wake Lock API** — jaga layar nyala saat monitoring aktif
- **Web Share API** — `NocPwa.share(title, text, url)` untuk share PDF report ke WA dsb
- CSS helper: `.only-pwa` / `.only-web` untuk conditional render

Auto-loaded di [layouts/app.blade.php](resources/views/layouts/app.blade.php) setelah noc-cyber.

### 🔐 Device & Session Management (baru)

**Controller** [app/Http/Controllers/DeviceController.php](app/Http/Controllers/DeviceController.php)
- Query `sessions` table Laravel (butuh `SESSION_DRIVER=database`, sudah default)
- Parse user-agent → device name + icon (iPhone/Android/Windows/Mac/Linux) + browser
- Endpoint: `GET /devices`, `DELETE /devices/{id}`, `DELETE /devices/others`

**View** [resources/views/devices/index.blade.php](resources/views/devices/index.blade.php)
- List semua session aktif user login
- Card per device: icon device + nama + browser + IP + last seen + human-readable time
- Badge "AKTIF SEKARANG" hijau untuk session current
- Tombol **LOGOUT** per device (kecuali current)
- Tombol **LOGOUT SEMUA DEVICE LAIN** di header (data-confirm cyber)
- Topbar + breadcrumb konsisten dgn halaman users
- Empty-state / unsupported message elegan

Link ke `/devices`:
- User-chip di dashboard header (icon `fa-mobile-alt` hijau)
- Bottom-nav mobile (icon Devices)

### 🔔 Web Push Notification (infrastruktur)

**Controller** [app/Http/Controllers/PushController.php](app/Http/Controllers/PushController.php)
- `GET /push/vapid-key` — return VAPID public key ke frontend
- `POST /push/subscribe` — simpan PushSubscription (endpoint + p256dh + auth)
- `POST /push/unsubscribe` — hapus by endpoint

**Migration** [`2026_09_18_000002_create_push_subscriptions_table.php`](database/migrations/2026_09_18_000002_create_push_subscriptions_table.php)
- Tabel `push_subscriptions` — user_id, endpoint, p256dh, auth, user_agent, last_used_at

**Config** [config/services.php](config/services.php) — block `push` (VAPID public/private/subject)

**Service Worker** [public/sw.js](public/sw.js) — bump cache `noc-v5`
- Handle `push` event → `showNotification()` dengan vibrate, icon, badge, actions (Open/Dismiss)
- Handle `notificationclick` → fokus tab NOC atau buka `/`
- Handle `pushsubscriptionchange` → placeholder untuk resubscribe

**Frontend toggle** di `/devices` — tombol "Aktifkan Notifikasi" — request permission, fetch VAPID, subscribe via PushManager, kirim ke `/push/subscribe`

**Next step** (belum): backend sender pakai package `minishlink/web-push` untuk push notif saat SensorController transisi ke DANGER. Tambah `.env`:
```
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
```
Generate: `web-push generate-vapid-keys` (Node) atau lib PHP.

### 🔒 OTP Page Enhancement — Mobile-Friendly

[resources/views/auth/otp.blade.php](resources/views/auth/otp.blade.php)
- **Tombol "TEMPEL DARI CLIPBOARD"** — `navigator.clipboard.readText()`,
  auto-fill 6 digit + auto-submit
- **Resend cooldown 30s** — tombol Kirim Ulang di-disable + label countdown "TUNGGU 25s"
  (persist di localStorage supaya bertahan refresh)
- **Auto-submit** saat 6 digit terisi (tidak perlu klik VERIFIKASI)
- **WebOTP API** (Android Chrome + Safari 16.4+) — auto-fill dari SMS
  (butuh format SMS `<OTP>\n\n@your-domain #otp-code`)

### 🔄 Cache Buster
- `UI_VER` → `2026-09-18-r7`
- SW cache `noc-v5`

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-17 — Round 6: Role & Navigation Fix)

### 🐛 Bug Fixes
- **`{{ '@' . $u->username }}`** — di [users/index.blade.php](resources/views/users/index.blade.php)
  Blade `@{{ }}` sebelumnya mem-print literal `{{ $u->username }}` (bukan `@fahmi`).
  Fix: `<div class="user-username">{{ '@' . $u->username }}</div>`

### 🧭 Navigasi & Back Button
- **users/index.blade.php** — hapus `.topbar { display: none }` (yang bikin topbar tidak
  tampil), rebuild sebagai `.noc-topbar` dengan tombol **Kembali** (cyan highlight) +
  Dashboard + Users (active) + Profil + Logout, plus **breadcrumb**
  (Home › Dashboard › Manajemen User)
- **users/form.blade.php** — extend topbar dengan tombol Kembali (cyan) + Dashboard +
  Users + Profil + Logout, tambah breadcrumb 4-level

### 🔐 Permission Hardening — Operator ≠ Superadmin
**Sebelum:** Operator bisa akses `/db/export`, `/db/import`, dan lihat DATABASE MANAGEMENT
card di dashboard. Padahal ini action berbahaya.

**Sesudah** (di [routes/web.php](routes/web.php)):
```php
Route::middleware('superadmin')->group(function () {
    Route::get('/db/export', ...);
    Route::post('/db/import', ...);
    Route::prefix('users')->name('users.')->group(...);  // sudah ada sebelumnya
});
```

**Matrix akses (final):**

| Fitur                | Operator | Superadmin |
|----------------------|:--------:|:----------:|
| Dashboard live-data  | ✅       | ✅         |
| Export CSV/PDF report| ✅       | ✅         |
| Profile edit         | ✅       | ✅         |
| DB Backup/Restore    | ❌       | ✅         |
| User Management CRUD | ❌       | ✅         |

**Dashboard UI berubah untuk operator:**
- Card DATABASE MANAGEMENT sekarang wrap `@if(isSuperAdmin)` dgn fallback banner:
  "🔒 Fitur backup & restore hanya untuk SUPERADMIN — Hubungi admin jika perlu backup"
  (opacity .7, badge kuning "TERBATAS")
- User-chip di header: SUPERADMIN badge merah `<i.crown>`, OPERATOR badge cyan `<i.user>`
- Bottom-nav mobile: menu "Users" hanya tampil untuk superadmin (grid auto-flow column
  supaya jumlah menu bisa 5 atau 6 tanpa hardcode)

### 🎨 Style Baru
- `.noc-topbar` — sticky top, gradient bg, backdrop-blur, tombol nav-link modern
- Mobile-responsive: `<span>` di dalam nav di-hide di `< 640px`, ganti icon only
- Breadcrumb minimalis JetBrains Mono dgn separator `›`

### 🔄 Cache Buster
- Bump `UI_VER` ke `2026-09-17-r6`

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-17 — Round 5: Report Excel & PDF Pro)

### 📊 Excel Report — Multi-Sheet (NocSensorReport)
File baru: `app/Exports/NocSensorReport.php` + `app/Exports/Sheets/*.php` (4 sheet).

**Sheet 1 — Ringkasan** ([SummarySheet.php](app/Exports/Sheets/SummarySheet.php))
- Header perusahaan (PT Inkasa) + subtitle report + periode
- Info meta: tanggal cetak, total record, interval agregasi, batas alarm
- KPI Cards row: MAX/MIN/AVG Temp, Avg Hum, Max Smoke, Critical Event (6 kartu berwarna)
- Sensor Breakdown: peak S1/S2/S3 + status Flame 1/2 (count trigger)
- Threshold Breach Analysis: event count + persentase per kategori (Temp/Smoke/Fire)
- Final Assessment banner: hijau (normal) atau merah (attention required)

**Sheet 2 — Detail Data** ([DetailSheet.php](app/Exports/Sheets/DetailSheet.php))
- Data hasil agregasi (5m/15m/1h/1d otomatis sesuai lebar periode)
- 11 kolom: NO, TIMESTAMP, TEMP, HUM, SMOKE, S1, S2, S3, F1, F2, STATUS
- Conditional formatting:
  - STATUS: CRITICAL merah bg + text, STABLE hijau text
  - F1/F2: FIRE merah bold
  - TEMP > tLimit: orange bold
  - SMOKE > sLimit: merah bold
- Freeze pane + Auto Filter di header

**Sheet 3 — Rekap Per Jam/Hari** ([HourlySheet.php](app/Exports/Sheets/HourlySheet.php))
- Grouping otomatis: per jam (≤ 3 hari) atau per hari (> 3 hari)
- Kolom: Periode, Avg/Max Temp, Avg Hum, Avg/Max Smoke, Flame count, Critical count, Status
- Conditional STATUS: CRITICAL / WARNING (mendekati threshold) / STABLE
- Freeze pane + Auto Filter

**Sheet 4 — Log Insiden** ([IncidentSheet.php](app/Exports/Sheets/IncidentSheet.php))
- Semua record yg melewati batas (limit 200 teratas)
- Diurutkan: Fire → Smoke tertinggi → Temp tertinggi
- Kolom: Timestamp, Temp, Smoke, Flame, Penyebab (narasi), Keparahan (DANGER/HIGH/MEDIUM)
- Conditional Keparahan: bg berwarna sesuai level
- Empty-state banner hijau jika tidak ada insiden

### 📄 PDF Report — Multi-Page Professional
File: [resources/views/reports/sensor_pdf.blade.php](resources/views/reports/sensor_pdf.blade.php)

**Halaman 1 — Ringkasan Eksekutif**
- Header perusahaan + doc title corner
- Meta bar (total record, interval, grouping, batas alarm)
- KPI Grid 6 kartu berwarna (Max/Min/Avg Temp, Avg Hum, Max Smoke, Insiden)
- Verdict banner (hijau/merah)
- Ringkasan naratif (paragraf yang generate otomatis dari data)
- Tabel Analisis Pelanggaran Ambang (Temp/Smoke/Fire) + badge severity

**Halaman 2 — Analisis Tren**
- Grafik 1: Trend Suhu & Kelembaban (dual-axis + threshold line)
- Grafik 2: Kualitas Udara Multi Sensor (S1/S2/S3 + MAX + threshold)
- Grafik 3: Pelanggaran Ambang (bar chart)
- Grafik 4: Distribusi Insiden per periode
- Semua chart render via QuickChart.io (base64 embedded)

**Halaman 3 — Rekap Per Jam/Hari**
- Tabel lengkap dgn status color-coded (critical/warning/stable)
- Kolom: Periode, Avg/Max Temp, Avg Hum, Avg/Max Smoke, Flame, Insiden, Status

**Halaman 4 — Log Insiden Terperinci** (hanya jika ada insiden)
- Tabel 200 insiden teratas dgn narasi penyebab + severity badge

### ⚙️ Controller Enhancement
[app/Http/Controllers/DashboardController.php](app/Http/Controllers/DashboardController.php)
- New method `buildReportContext()` — centralized context builder (Excel & PDF pakai sama)
- Compute: overall stats, threshold breach + %, aggregated series, hourly rekap, incident list
- New method `renderReportCharts()` — generate 4 chart via QuickChart.io
- New method `quickChart()` — helper POST ke quickchart.io dengan devicePixelRatio 2
- `export()` sekarang pakai `NocSensorReport` (multi-sheet)
- `exportPdf()` sekarang pakai enhanced blade dengan 4 chart

**Backward-compat:** file lama `SaktiSensorExport.php`, `FreshSensorExport.php`,
`SensorExport.php`, `UltraSensorExport.php` masih ada — tidak dihapus supaya bisa dibalikkan
kalau perlu.

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-17 — Round 4: Visual Polish + Mobile UX)

### 🎨 Metric Cards
- Background: dual gradient overlay (subtle highlight + panel tint)
- Border-radius naik ke 14px, padding fluid
- Hover: `translateY(-3px)`, cyan border + shadow glow, radial glow di sudut kanan-atas
- Skeleton pulse animation `.is-loading` untuk value placeholder
- Corner accent gradient (radial glow di top-right)

### 🎨 Sidebar / Glass Card
- Background dual gradient + panel tint (lebih rich)
- Border-radius 12px, padding lebih longgar (14px)
- Top highlight line (`::after`) — subtle horizon line untuk depth
- `card-title` dapat left-marker bar (3px lebar, glow cyan) + fade gradient line
- Health-matrix items: hover state cyan glow, background subtle

### 🎨 Buttons (cyber-btn)
- Gradient bi-tone (dari-ke) untuk depth
- Hover: translateY(-1px), icon scale 1.15, inset+outer shadow
- Konsisten padding & letter-spacing (Orbitron 700, 1.5px)

### 🎨 Log Table
- Header: gradient bg cyan (top→dim), letter-spacing 2px, JetBrains Mono
- Row hover cyan tint
- `row-danger`: gradient bg + left border pink (3px)
- Border tipis cyan-tinted, transisi smooth

### 🎨 CCTV Frame
- 4 corner brackets futuristic (::before, ::after, .corner-tr, .corner-bl)
- Border-radius 10px + inset shadow untuk depth
- Cyan glow di border (subtle)

### 📱 Mobile Bottom Navigation (baru)
- Sticky bottom nav `<nav id="bottom-nav">` — 5 items:
  Live · CCTV · Logs · Profil · Keluar (logout form)
- Hanya tampil di `<= 900px` (`display: none` di desktop)
- `env(safe-area-inset-bottom)` — safe untuk iPhone notch/home indicator
- Backdrop-filter blur + border-top cyan-tinted
- Auto-hide `.user-actions` di header saat mobile (sudah ada di bottom nav)
- Scale FAB naik ke `bottom: 72px` supaya tidak overlap
- Body auto `padding-bottom: 68px` biar konten terakhir tidak ketutup
- Anchor IDs: `#top` (metrics), `#cctv`, `#logs` untuk smooth-scroll navigation
- `scroll-behavior: smooth` di `html` global

### 🔄 Cache Buster
- Bump `UI_VER` ke `2026-09-17-r4` — user next-load auto-invalidate SW cache lama

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-17 — Round 3: SW Cache + Bulletproof Lock)

**Root cause definitif:** `public/sw.js` menerapkan **cache-first** strategy untuk asset
same-origin (case #4). Setelah service worker terinstal, browser mengambil
`css/noc-theme.css` DARI CACHE walau query `?v=X` sudah di-bump. Hasilnya: semua
perbaikan responsif di CSS TIDAK PERNAH SAMPAI ke browser user.

Ditemukan juga: layouts.app `<main>` masih apply `padding: clamp(12px, 2vw, 24px)` via
inline style — walaupun CSS override `padding: 0 !important` sudah ada, ada kemungkinan
browser dgn CSS lama tidak menerapkan (SW cache).

### 🔧 Perbaikan Round 3
- [x] `public/sw.js` — bump `CACHE = 'noc-v4'` (dari `noc-v2`)
  → activate handler otomatis hapus cache `noc-v2/v3` → SW re-download semua asset
- [x] `resources/views/layouts/app.blade.php` — cache-bust:
  - `noc-theme.css?v=4`
  - `noc-cyber.js?v=3`
- [x] `resources/views/dashboard.blade.php`:
  - **Refactor `.container`**: `max-width: min(1800px, calc(100vw - 8px))` — hard cap
    memakai `calc` supaya tidak pernah melebihi viewport walau ada bug perhitungan grid
  - Sidebar FIXED explicit `width: 260px` (tidak lagi `minmax(0, 260px)`) supaya grid
    track tidak bisa memanjang
  - `container-type: inline-size` → enable container queries di metrics-hud
  - `@container (max-width: 900px)` → 2 kolom metrics
  - `@container (max-width: 500px)` → 1 kolom metrics
  - Sidebar cyber-lock: `aside { width: 260px; overflow: hidden }` + semua descendant
    dipaksa `min-width: 0; max-width: 100%; overflow: hidden`
  - **SAFETY LOCK block di akhir @push('styles')** — kumpulan aturan `!important`
    (html/body/main/container/metrics-hud/metric-glow) yang tidak bisa di-override
    inline style atau library CSS lain

**Instruksi user setelah pull:**
1. Hard-refresh browser: `Ctrl+Shift+R` (Chrome) atau `Ctrl+F5`
2. Kalau masih overflow: buka DevTools → Application → Service Workers → **Unregister**,
   lalu reload. SW baru akan install dengan cache `noc-v4`.
3. Kalau tetap: buka DevTools → Network tab → checkbox `Disable cache` → reload.

### 🔧 Round 3b — Auto Cache-Buster (fallback tanpa aksi manual user)
- [x] `resources/views/dashboard.blade.php` — script inline di `@push('head')`:
  - Simpan `UI_VER = '2026-09-17-r3'` di localStorage
  - Kalau versi berbeda dari terakhir → auto **unregister semua Service Worker** +
    `caches.delete()` semua CacheStorage → auto reload sekali
  - Guard dgn `sessionStorage` supaya tidak infinite loop
  - Setelah cache bersih, register SW baru
- [x] `resources/views/layouts/app.blade.php` — tambah 3 meta http-equiv:
  - `Cache-Control: no-cache, no-store, must-revalidate`
  - `Pragma: no-cache`
  - `Expires: 0`
  → Force browser fetch fresh HTML tiap load, tidak boleh serve dari disk cache

**Cara pakai:** bump `UI_VER` string di dashboard.blade.php setiap ada perubahan
CSS/JS besar → user berikutnya buka page, script auto-clear cache & reload.

**Hasil:** Setelah SW cache di-invalidasi, semua CSS baru muncul. Kartu metrik adapt
otomatis via container query (bukan viewport media query) — jadi walau ada browser zoom,
kartu tetap mengikuti lebar parent-nya.

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-17 — Round 2: Zoom-aware Layout)

**Masalah lanjutan:** Setelah round 1 fix, saat user browser-zoom (Ctrl+/-) card tidak
menyesuaikan. Root cause:
1. `scale FAB` pakai `style.zoom` non-standar → konflik dgn browser zoom
2. Date input di sidebar punya intrinsic min-width ~150px → paksa sidebar melebar walau
   `grid-template-columns: minmax(240px, 280px)`, isinya overflow horizontal
3. `min-height: clamp(200px, 18vw, 260px)` di metric-glow bikin card kaku, isi tidak
   proporsional saat viewport mengecil
4. Chart.js tidak resize otomatis saat browser zoom

### 🔧 Perbaikan Round 2
- [x] `resources/views/dashboard.blade.php`:
  - Scale FAB: refactor pakai `document.documentElement.style.fontSize` (bukan `zoom`)
    → viewport size tetap sama, media queries tetap valid, TIDAK konflik dgn browser zoom
  - Tambah `window.addEventListener('resize', ...)` → trigger `chart.resize()` untuk semua
    Chart.js instance saat browser zoom (debounce 120ms)
  - Expose `window.charts` supaya scale FAB bisa akses instance
  - `main { padding: 0 !important; max-width: 100%; overflow-x: hidden }` — kill layout main
  - `html, body { overflow-x: hidden; max-width: 100% }` — hard cap width
  - **Fix date/month input paksa sidebar overflow:**
    `.container input[type="date"], input[type="month"] { flex: 1 1 0; min-width: 0; font-size: clamp(.62rem, .65vw, .75rem) }`
  - **Hapus** `min-height: clamp(...)` di `.metric-glow` — biarkan konten yang mendikte tinggi
  - `.metrics-hud` → `repeat(auto-fit, minmax(min(220px, 100%), 1fr))` — `min(220px, 100%)` cegah
    kartu overflow saat parent < 220px (fallback ke 100% container-width)
  - `aside > * { min-width: 0; overflow: hidden }` — safety net elemen sidebar
  - Sidebar grid columns dibuat lebih kompak: `minmax(0, 260px)` (dari 240-280)
- [x] `public/css/noc-theme.css`:
  - `html { max-width: 100%; overflow-x: hidden }` — root cap
  - Body ganti `align-items: center` → `align-items: stretch` (jangan bikin main narrower dari
    content). Login/OTP override sendiri sudah aman.
  - Tambah `main { min-width: 0; max-width: 100% }` global
- [x] `resources/views/layouts/app.blade.php` — bump cache-bust ke `?v=3` supaya browser reload

**Hasil:** Sekarang saat browser zoom in/out:
- Media queries fire pada breakpoint yang benar (viewport tidak dipalsukan oleh `zoom`)
- Kartu metrik auto-adapt 4→3→2→1 kolom mengikuti lebar container
- Chart.js re-render dengan ukuran baru (via `resize()`)
- Sidebar shrink dgn benar tanpa dipaksa date-input intrinsic width
- Scale FAB & browser zoom bisa dipakai bersamaan (efek berbeda: FAB skala UI relatif,
  zoom skala viewport)

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-17 — Round 1: Fix Overflow Wide)

**Masalah:** Setelah sesi 2, dashboard overflow ke kanan di layar wide (~1920px).
Root cause: **double-padding** dari layout `<main>` + dashboard `.container`, plus
root font-size clamp yg naik ke 18px bikin semua rem membesar 12.5% -> content > viewport.

### 🔧 Perbaikan
- [x] `public/css/noc-theme.css` — root font-size clamp direvisi lebih konservatif:
  `clamp(13px, 0.15vw + 12.5px, 16px)` (cap di 16px, tidak membesar di layar wide)
- [x] `resources/views/dashboard.blade.php`:
  - **Fix root overflow**: `main { padding: 0 !important; max-width: 100vw; overflow-x: hidden }`
    di dashboard @push('styles') — kill double-padding dari layouts.app
  - `.container` — `max-width: min(1800px, 100vw)`, sidebar `minmax(240px, 280px)`
  - `.metrics-hud` — pakai `grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))`,
    otomatis stack sesuai lebar (4 kolom di ≥1200px, 2 kolom di ~700-1100px, 1 kolom di sempit)
  - `.metric-glow` — `min-width: 0; overflow: hidden; min-height: clamp(200px, 18vw, 260px)`
  - `.val-large` — font clamp lebih kecil, `white-space: nowrap` + `text-overflow: ellipsis`
  - Kunci font-size inline di FLAME SENSOR yg override clamp (hapus `font-size:2.2rem` inline)
  - Header: `flex-wrap: wrap; gap: 12px; min-width: 0` — cegah items overflow
  - `.brand-container` & `.brand` — `min-width: 0; flex: 1 1 auto`
  - Clock: `font-size: clamp(1.4rem, 2vw, 2.2rem)` (dari 2.4rem fixed) + `min-width: 0`
  - `.user-chip` — `max-width: 100%; min-width: 0; flex-shrink: 1`
  - `.user-name` & `.user-role` — `white-space: nowrap; overflow: hidden; text-overflow: ellipsis`
- [x] Media queries dashboard disederhanakan: hapus grid-template-columns override yang
  mengalahkan auto-fit (bikin konflik), sisakan hanya container + header/aside layout adjust

**Hasil:** Metrics-hud sekarang auto-adapt: 4-3-2-1 kolom otomatis tanpa media query manual,
sesuai lebar container yg tersedia. Tidak ada lagi overflow horizontal.

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-15 — Bagian 2: Responsivitas + Innovasi UI)

### 🎯 Cyber UI Library (baru — zero-dependency)
- [x] `public/js/noc-cyber.js` — library terpusat, global object `window.NocCyber`
  - `NocCyber.toast(msg, {type,duration,title,icon})` — toast kanan-atas
  - `NocCyber.success/error/warning/info(msg)` — shortcut
  - `NocCyber.alert(msg)` — modal informatif (tanpa cancel)
  - `NocCyber.confirm(msg)` → Promise<bool> — dialog konfirmasi (warna kuning)
  - `NocCyber.danger(msg)` → Promise<bool> — dialog konfirmasi (warna pink, ada shake icon)
  - `NocCyber.prompt(msg)` → Promise<string|null> — input dialog
  - `NocCyber.loading(label, sub)` → close-fn — full-screen spinner
  - Auto-hook: `<form data-confirm="msg" data-confirm-type="danger">` otomatis munculkan konfirmasi
  - Support keyboard (Enter=confirm, Esc=cancel), overlay-click dismiss
  - Auto scale di mobile (< 520px), respect `prefers-reduced-motion`
- [x] `resources/views/layouts/app.blade.php` — auto-load `noc-cyber.js`,
  auto-flash `session('success|error|warning|info')` → NocCyber toast,
  tambah `<meta name="csrf-token">` + `viewport-fit=cover` (iPhone notch)

### 📱 CSS Responsive Global
- [x] `public/css/noc-theme.css`:
  - `html { font-size: clamp(14px, 0.9vw + 12px, 18px) }` — root scaling fluid
  - Fluid typography helpers: `.fluid-h1/.fluid-h2/.fluid-lead/.fluid-sm`
  - Container utilities: `.container-fluid/.container-narrow/.container-wide`
  - Auto-fit grid: `.grid-auto-fit`, `.grid-auto-fit-sm`
  - Breakpoint aware kompres: `.topbar`, `.glass-card`, `.btn`, `.cyber-table`
  - Touch target ≥44px (WCAG) via `@media (pointer: coarse)`
  - `@media (prefers-reduced-motion: reduce)` — matikan animasi + scanline

### 🖥️ Dashboard — Responsivitas & Innovasi
- [x] Fix typo aneh `apa say` di CSS `.cctv-stage video`
- [x] `.container` grid pakai `clamp(260px, 22vw, 320px)` — sidebar auto-scale
- [x] `.metrics-hud` `minmax(0, 1fr)` — cegah overflow di mobile
- [x] `.val-large`, `.brand h1`, `.metric-glow` pakai `clamp()` untuk font & padding
- [x] Breakpoint baru:
  - `<1200`: metrics 2 kolom (dari 4)
  - `<1100`: layout stack sidebar-bawah
  - `<900`: brand mengecil, cctv-grid 1 kolom
  - `<700`: header stacked, metrics 1 kolom
  - `<480`: health-matrix 1 kolom, padding kompak
- [x] **Scale Controller FAB** (kanan-bawah):
  - Tombol `+` / `-` / label / reset
  - Persist ke `localStorage['noc-ui-scale']`
  - Shortcut keyboard: `Ctrl/Cmd + Alt + =/-` / `0` (reset)
  - Terapkan via `document.documentElement.style.zoom`
- [x] **Pull-to-refresh** di mobile (swipe-down > 90px di scroll-top → reload)
- [x] **Cyber-toast on alarm transition**:
  - Saat NORMAL → DANGER: `NocCyber.error()` sebagai kabar samping (selain overlay)
  - Saat DANGER → NORMAL: `NocCyber.success()` "Kondisi kembali NORMAL"
- [x] Ganti browser `confirm()` restore DB → `data-confirm` + `data-confirm-type=danger`
  (auto-hook NocCyber render modal cyber)

### 👥 User Management — Responsivitas
- [x] `resources/views/users/index.blade.php`:
  - Modal delete manual dihapus, ganti pakai `NocCyber.danger()` (Promise-based)
  - Semua `<td>` diberi `data-label` — di mobile (< 780px) table jadi card-view
  - Pagination-wrap column-flex di mobile
  - Filter bar full-width di mobile

### 🔧 Fix Layout Login & OTP (dari sesi sebelumnya)
- [x] `resources/views/auth/login.blade.php` — override `main{display:flex; center; min-h:100vh}` + `margin: 0 auto` di `.login-card`
- [x] `resources/views/auth/otp.blade.php` — override `main` yg sama, plus fix bug OTP digit tidak tampil (padding global `input[type=text]` bikin content-width negatif, override dgn `padding: 0 !important` + selector `.otp-inputs input.otp-digit`)

---

## ✅ SUDAH DIKERJAKAN (Sesi 2026-09-15 — Bagian 1: Hardening & Backup)

### 🎨 Halaman Error Kustom (senada tema cyber)
- [x] `resources/views/errors/404.blade.php` — Signal Lost + glitch effect
- [x] `resources/views/errors/403.blade.php` — Access Denied (role guard)
- [x] `resources/views/errors/419.blade.php` — Session Expired (CSRF)
- [x] `resources/views/errors/429.blade.php` — Throttled (rate-limit)
- [x] `resources/views/errors/500.blade.php` — Core Malfunction
- [x] `resources/views/errors/503.blade.php` — sudah ada sebelumnya, TIDAK diubah

### 🔐 Keamanan & Rate-Limit
- [x] `app/Providers/AppServiceProvider.php` — daftar named rate-limiter:
  - `sensor-ingest`: 120 req/menit per IP + 180 req/menit per API key
  - `login`: 6 req/menit per IP + per username
  - `otp`: 3 req/menit per session
- [x] `routes/api.php` — apply `throttle:sensor-ingest` di POST `/api/sensor/store`
- [x] `routes/web.php` — apply `throttle:login` di POST `/login` & `/otp/verify`,
  `throttle:otp` di POST `/otp/resend`
- [x] `SensorController::store()` — pakai `hash_equals()` (constant-time) untuk cek API key,
  log 401 attempt dengan IP+UA, validasi range angka (temp -40..125, hum 0..100, smoke 0..4095),
  handle `ValidationException` → 422 terpisah, jangan bocorkan pesan exception di 500

### 🧱 Model Hardening
- [x] `app/Models/SensorLog.php` — ganti `$guarded=[]` → `$fillable` eksplisit + `$casts`
  (temp/hum float, smoke int, fire/flame boolean)

### 🩺 Performa & Cache
- [x] `DashboardController::getLiveMetrics()`:
  - Cache key `daily_sensor_stats` sekarang include tanggal → auto-reset saat ganti hari
  - Tambah cache `latest_sensor_reading` (TTL 3 dtk) — tahan burst polling 5 dtk
  - Baca `temp_limit`/`smoke_limit` via `config()` (bukan `env()`) — kompatibel dgn config:cache
  - Response payload tambah `alarm_state` & `server_time` (bantu debug drift)
- [x] `SensorController::store()` — invalidate `latest_sensor_reading` saat insert baru

### 📦 Backup & Maintenance
- [x] `app/Console/Commands/BackupDatabase.php` — command `php artisan db:backup`
  - Dump via mysqldump `--single-transaction --quick --routines --triggers`
  - Rotasi otomatis (default keep 14 versi)
  - Deteksi Windows vs Linux binary
- [x] `app/Console/Commands/PruneMotionClips.php` — command `php artisan motion:prune`
  - Hapus `.mp4/.jpg/.jpeg/.png` di `storage/app/motion/` yg umur > N hari (default 7)
- [x] `routes/console.php` — jadwalkan otomatis:
  - `db:backup` daily 02:15 · `motion:prune` daily 03:00

### ⚙️ Exception Rendering
- [x] `bootstrap/app.php` — `shouldRenderJsonWhen()` untuk route `api/*` & JSON request
  (biar client dapet JSON, browser dapet halaman blade cyber)

### 📄 Documentation & Env
- [x] `.env.example` — sekarang lengkap: DB MySQL, MAIL SMTP (OTP), semua env NOC
  (SENSOR_*, TELEGRAM_*, CCTV_*, MOTION_*, YOLO_*, DARK_*, CF_TUNNEL_TOKEN)
  dengan komentar cara generate & sumber
- [x] `app/Providers/AppServiceProvider.php` — hilangkan indentasi salah di block HTTPS force

---

## 🔜 BACKLOG BERIKUTNYA (rekomendasi prioritas)

### 🔴 HIGH — Sebelum push ke publik / merge ke prod
- [ ] **Rotasi kredensial** yang mungkin bocor di `.env` prod:
  - `TELEGRAM_BOT_TOKEN`, `SENSOR_API_KEY`, `CF_TUNNEL_TOKEN`, DB password
  - Cek `git log -p .env` → kalau pernah ter-commit, ganti + hapus dari history
    (`git filter-repo` atau `bfg`)
- [ ] **Ganti default password** user di `NocUsersSeeder` (saat ini `bismillah`) —
  buat migrasi one-time yang force reset password + kirim email OTP setup
- [ ] **Verify header validation** di `DatabaseController::import` (restore SQL) —
  saat ini limit 64MB, tambah sanity check bahwa file benar-benar dump DB kita

### 🟡 MED — Fitur & UX
- [ ] **User roles matang** — sekarang cuma superadmin/user; tambah `viewer` (read-only,
  tidak boleh export/import DB). Update `IsSuperAdmin` → generic `role:xxx` middleware
- [ ] **Audit log** — tabel `audit_logs` (user_id, action, target, ip, ua, created_at).
  Log event: login, logout, profile update, user CRUD, db:export, db:import, artisan down/up
- [ ] **Threshold per sensor bisa diubah dari UI** (bukan cuma `.env`) —
  tabel `sensor_configs` (key, value) + form settings di dashboard (superadmin only)
- [ ] **Web Push PWA** (bukan cuma Telegram) — `laravel-notification-channels/webpush`
- [ ] **Dark/light theme toggle** — sekarang hardcoded dark cyber
- [ ] **Multi-lokasi** — kolom `location_id` di `sensor_logs`, filter per lokasi di dashboard

### 🟢 LOW — Reliability & Nice-to-have
- [ ] **PHPUnit test** untuk:
  - `SensorController::store` (auth OK/reject, validation, debounce state transition)
  - `AuthController::login` (throttle setelah 6x salah)
  - `DatabaseController::import` (reject file non-SQL)
- [ ] **Health-check endpoint** `/up` sudah aktif (Laravel 12) → sambungkan ke Uptime Kuma
- [ ] **Backup ke storage cloud** (S3/Backblaze) — sekarang cuma local disk
- [ ] **Docker healthcheck** di `docker-compose.yml` untuk app & db container
- [ ] **Log rotation** — `storage/logs/laravel.log` bisa membengkak; tambah rotate config
  (channel `daily` di `config/logging.php` dgn `days => 14`)

---

## 📋 CATATAN OPERASIONAL (buat sesi lanjutan)

### File yang paling sering disentuh
- `app/Http/Controllers/SensorController.php` — ingest ESP32 + Telegram
- `app/Http/Controllers/DashboardController.php` — API live-data + export
- `app/Providers/AppServiceProvider.php` — rate-limiter registrations
- `resources/views/dashboard.blade.php` — UI utama (~1500 baris CSS+JS inline)
- `resources/views/errors/*.blade.php` — error pages (semua senada cyber theme)
- `routes/console.php` — schedule daily backup + prune

### Cara test manual di Laragon
```powershell
# 1. Migrate + seed
php artisan migrate --seed

# 2. Test error pages
php artisan serve --port=8000
# 404: http://127.0.0.1:8000/tidak-ada-halaman-ini
# 500: sengaja buat exception di controller
# 419: expired session (tunggu > SESSION_LIFETIME menit)
# 429: hit /login 7x cepat

# 3. Test backup command
php artisan db:backup --keep=3
ls storage/app/backups/

# 4. Test prune command
php artisan motion:prune --days=1

# 5. Test rate-limit ESP32
# Kirim 150 POST /api/sensor/store dari IP yg sama dlm 1 menit
# Harusnya request ke-121+ dapat HTTP 429

# 6. Test responsivitas & scale controller
# Buka DevTools -> Device Toolbar (Ctrl+Shift+M)
# Coba: iPhone SE (375px), iPad (768px), iPad Pro (1024px), Desktop (1440px)
# - Metrics HUD harus adjust jumlah kolom otomatis
# - Sidebar harus stack di <1100px
# - Scale FAB kanan-bawah: coba tekan +/-, reload page -> skala tetap
# - Shortcut Ctrl+Alt+= / Ctrl+Alt+- / Ctrl+Alt+0
# - Di mobile: swipe-down di scroll top -> muncul toast + reload

# 7. Test NocCyber di browser console
# NocCyber.success('Test success')
# NocCyber.error('Test error')
# NocCyber.confirm('Yakin?').then(ok => console.log(ok))
# NocCyber.danger('Delete?').then(ok => console.log(ok))
# NocCyber.prompt('Nama?', { input: { placeholder: 'nama...' } }).then(v => console.log(v))
# NocCyber.loading('Processing...') // close: NocCyber.hideLoading()
```

### File baru / berubah (sesi 2)
- `public/js/noc-cyber.js` (NEW) — library toast/alert/confirm/prompt/loading
- `public/css/noc-theme.css` — tambah 60+ baris responsive utility di bawah
- `resources/views/layouts/app.blade.php` — load noc-cyber.js + auto-flash session
- `resources/views/dashboard.blade.php` — clamp() fluid, breakpoint 1200/1100/900/700/480,
  scale FAB, pull-to-refresh, cyber-toast on alarm transition, `data-confirm` di form restore
- `resources/views/users/index.blade.php` — mobile card-view (data-label), NocCyber.danger delete
- `resources/views/auth/login.blade.php` & `otp.blade.php` — fix layout centering + OTP digit visibility

### Deploy ke server produksi (`noc-suhu@192.168.11.178`)
Ikuti pola di `memory/session-noc-deploy.md`:
1. Backup file lama (`*.bak.<timestamp>`)
2. `pscp` upload ke host
3. `docker cp` ke running container (efek live segera)
4. `docker compose exec -T app php artisan view:clear && config:clear && config:cache`
5. `docker compose build app` + `up -d app`
6. Verify MD5 host vs container

**PENTING sebelum deploy sesi ini:**
- Rate-limiter butuh `CACHE_STORE=database` atau `redis` (jangan `array` — reset tiap request)
- `config('services.sensor.temp_limit')` dibaca via config — WAJIB `config:cache` refresh
- Kalau prod pakai `APP_DEBUG=false`, error 500 akan tampil blade 500.blade.php baru ✓

### Yang TIDAK diubah (sengaja, jangan disentuh tanpa alasan)
- `resources/views/errors/503.blade.php` — sudah bagus, tema sama dgn login
- `motion-detector/detect.py` — dark-filter logic sudah teruji anti-false-alarm
- `docker/nginx.conf` — sudah handle `/cctv-hls/` proxy + `error_page 502 504 → maintenance.html`
- `public/maintenance.html` — fallback saat container app mati (bind-mount)
- Struktur multi-sensor (smoke1..3, flame1..2) di migration

---

## 🎯 CARA MELANJUTKAN DI SESI BERIKUTNYA

1. Baca file ini dari atas — lihat mana yang `[ ]` (belum) di section BACKLOG
2. Pilih item HIGH dulu, MED, baru LOW
3. Setiap selesai satu item: pindahkan checklistnya ke section "SUDAH DIKERJAKAN"
  dan tambah tanggal sub-heading
4. Kalau menemukan bug atau ide baru saat implementasi, TULIS di section BACKLOG
  supaya tidak lupa
5. Kalau deploy ke prod, catat perubahan di `memory/session-noc-deploy.md` (via memory)

**Prinsip:** eksekusi tanpa penjelasan panjang, tetap simpan progres ke MD ini.
