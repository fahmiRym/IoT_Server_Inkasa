# Deploy NOC Dashboard ke Server Remote

## Persiapan

Pastikan Anda sudah meng-install Docker dan Docker Compose di server remote (192.168.11.179).

## Cara Deploy

### Langkah 1: Upload File ke Server

Dari komputer lokal Anda, jalankan:

```bash
# ssh ke server
ssh noc-suhu@192.168.11.178

# Buat folder aplikasi
mkdir -p /var/www/noc-dashboard
cd /var/www/noc-dashboard
```

Copy file berikut ke server:
- Dockerfile
- docker-compose.yml
- docker/ (folder)
- Semua file Laravel kecuali vendor & node_modules

Contoh menggunakan scp:
```bash
# Dari lokal - copy semua file kecuali vendor/node_modules
rsync -avz --exclude='vendor' --exclude='node_modules' --exclude='.env' . user@192.168.11.178:/var/www/noc-dashboard/
```

### Langkah 2: Setup Environment

Di server, buat file .env:
```bash
cp .env.example .env
```

Edit .env dengan konfigurasi database Docker:
```env
APP_NAME=NOC-Dashboard
APP_ENV=production
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
APP_DEBUG=false
APP_URL=http://192.168.11.178

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=noc_dashboard
DB_USERNAME=noc_user
DB_PASSWORD=alhamdulillah
```

### Langkah 3: Generate App Key
```bash
php artisan key:generate
```

### Langkah 4: Build & Start Docker

```bash
# Build containers
docker-compose build

# Start services
docker-compose up -d
```

### Langkah 5: Cek Status

```bash
# Lihat logs
docker-compose logs -f app

# Lihat running containers
docker-compose ps
```

### Langkah 6: Akses Aplikasi

Buka browser: http://192.168.11.178:80

---

## Perintah Useful

| Perintah | Fungsi |
|----------|--------|
| `docker-compose up -d` | Start services |
| `docker-compose down` | Stop services |
| `docker-compose restart` | Restart services |
| `docker-compose logs -f` | Lihat logs |
| `docker-compose exec app bash` | Masuk container app |
| `docker-compose exec db mysql -u noc_user -p` | Masuk MySQL |

## Troubleshooting

### Jika database tidak konek:
```bash
# Cek container db sudah running
docker-compose ps

# Lihat logs db
docker-compose logs db
```

### Jika permission error:
```bash
docker-compose exec app chown -R www-data:www-data /var/www
```

---

## Catatan

- Username SSH: `noc-suhu`
- Password: `alhamdulillah`
- Server IP: `192.168.11.178`
- Database password: `alhamdulillah`
- App akan berjalan di port 80
