# Event System - Songkran Festival 2026

Laravel 11 + React project untuk kebutuhan registrasi peserta, ticket QR, admin panel, dan staff scanner Songkran Festival 2026.

README ini ditulis untuk:


- tim yang akan deploy langsung ke VPS lewat SSH
- orang non-DevOps yang perlu menyalakan aplikasi tanpa Docker

Jika kamu hanya butuh jalur paling cepat, baca urutan ini dulu:

1. Local dev:
   - `composer install`
   - `npm install`
   - copy `.env.example` ke `.env`
   - isi `.env`
   - buat file `database/database.sqlite`
   - `php artisan key:generate`
   - `php artisan migrate`
   - `php artisan admin:seed --password=YOUR_PASSWORD`
   - `php artisan scanner:seed --password=YOUR_PASSWORD`
   - jalankan Laravel + Vite
2. Production VPS:
   - install Nginx + PHP 8.2 + Composer + Node.js
   - clone repo
   - buat `.env`
   - upload Firebase JSON ke server
   - buat SQLite database
   - `php artisan key:generate`
   - `php artisan migrate --force`
   - seed akun admin dan scanner
   - `npm ci && npm run build`
   - set permission `storage`, `bootstrap/cache`, dan `database`
   - pasang Nginx
   - pasang SSL
   - test semua URL penting

---

## 1. Ringkasan Project

Project ini punya 4 area utama:

1. Public website dan form registrasi peserta
2. Ticket page dan QR download untuk peserta
3. Admin panel untuk monitoring, attendance, gate management, campaign links, dan export
4. Staff scanner portal untuk check-in peserta di gate

Yang penting dipahami:

- data peserta dan ticket disimpan di Firestore
- database Laravel dipakai untuk kebutuhan internal aplikasi, bukan untuk semua data event
- admin dan scanner staff disimpan di tabel `admins`
- gate scanner disimpan di tabel `scanner_gates`
- campaign links disimpan di tabel `campaign_links`

Artinya:

- single VPS tanpa Docker masih sangat memungkinkan
- SQLite masih aman untuk bootstrap karena data utama peserta ada di Firestore
- kamu tidak wajib setup MySQL/PostgreSQL di hari pertama kalau targetnya hanya membuat aplikasi hidup stabil di satu VPS

---

## 2. Mental Model Arsitektur

Supaya tidak bingung, anggap project ini seperti ini:

### A. Laravel = backend utama

Laravel menangani:

- route web
- validasi request
- login admin dan scanner
- email verification / reset password
- integrasi Firestore
- admin panel Blade
- ticket generation

### B. React + Vite = frontend interaktif untuk public pages dan scanner

React dipakai untuk:

- landing page
- login page
- register page
- forgot QR
- forgot password
- staff login
- staff scanner page

### C. Firestore = sumber data peserta

Firestore dipakai untuk:

- `users`
- `tickets`
- `user_email_index`
- `user_identity_index`
- `scan_logs`
- `admin_activity_logs`

### D. SQLite = storage internal aplikasi

SQLite dipakai untuk:

- tabel `admins`
- tabel `scanner_gates`
- tabel `campaign_links`

---

## 3. URL Penting

Secara default, route pentingnya seperti ini:

| Area | URL |
| --- | --- |
| Landing page | `/` |
| Register form | `/register` |
| Admin login | `/login` |
| Admin panel | `/admin` |
| Staff scanner login | `/staff/login` |
| Staff scanner dashboard | `/staff` |

Catatan penting:

- route project ini saat ini berbasis path, bukan host-based routing
- artinya deploy paling aman adalah satu domain, lalu gunakan path:
  - `https://domainkamu.com/register`
  - `https://domainkamu.com/admin`
  - `https://domainkamu.com/staff`
- kalau kamu punya subdomain juga, boleh diarahkan ke server yang sama, tetapi project ini tetap bekerja dengan path yang sama

---

## 4. Tech Stack

| Layer | Teknologi |
| --- | --- |
| Backend | Laravel 11 |
| Language | PHP 8.2+ |
| Frontend bundler | Vite 6 |
| Frontend UI | React 18 + TypeScript |
| Styling | Tailwind CSS 4 |
| Public scanner library | `html5-qrcode` |
| Primary participant storage | Google Firestore |
| Internal app database | SQLite |
| Mail | SMTP |
| Captcha | Google reCAPTCHA |
| Web server (recommended) | Nginx |

---

## 5. Struktur Folder yang Paling Sering Dipakai

```text
app/
  Http/
    Controllers/
      Admin/          # controller admin panel
      Staff/          # controller staff scanner
      Auth/           # email verification, dsb
      Api/Auth/       # register, forgot password, reset password
  Services/
    Auth/             # register, delivery ticket, verification
    Admin/            # dashboard, export, audit log, campaign links
    Staff/            # logic scanner staff
    Firebase/         # Firestore transport
    Scanner/          # gate helper
    Tickets/          # QR / ticket generation

config/
  admin.php           # config admin panel
  scanner.php         # config scanner staff
  firebase.php        # config Firestore
  services.php        # recaptcha config

database/
  migrations/         # tabel internal Laravel
  seeders/            # seeder admin dan scanner
  database.sqlite     # database SQLite lokal / VPS sederhana

resources/
  js/src/             # React app
  views/              # Blade views admin / email / shell

storage/app/
  fonts/              # font untuk ticket download
  secrets/            # simpan Firebase JSON di sini
```

---

## 6. Prasyarat Local Development

Minimal yang perlu terpasang di laptop developer:

- PHP 8.2 atau lebih baru
- Composer
- Node.js 20 atau lebih baru
- npm
- Git
- SQLite

Kalau pakai Windows:

- Laragon boleh dipakai
- atau PHP + Composer + Node.js manual juga bisa

---



Ikuti urutan ini tanpa loncat langkah.

### Step 1 - Clone repository

```bash
git clone <URL_REPOSITORY>
cd eqwgwegwewge
```

### Step 2 - Install dependency PHP

```bash
composer install
```

### Step 3 - Install dependency JavaScript

```bash
npm install
```

### Step 4 - Copy file environment

PowerShell:

```powershell
Copy-Item .env.example .env
```

macOS / Linux:

```bash
cp .env.example .env
```

### Step 5 - Buat database SQLite

PowerShell:

```powershell
New-Item -ItemType File database/database.sqlite -Force
```

macOS / Linux:

```bash
touch database/database.sqlite
```

### Step 6 - Siapkan Firebase credential lokal

Taruh file JSON Firebase di:

```text
storage/app/secrets/firebase-credentials.json
```

Kalau folder `secrets` belum ada:

PowerShell:

```powershell
New-Item -ItemType Directory storage/app/secrets -Force
```

macOS / Linux:

```bash
mkdir -p storage/app/secrets
```

### Step 7 - Isi `.env` lokal

Minimal edit bagian ini:

| Variable | Wajib? | Contoh | Keterangan |
| --- | --- | --- | --- |
| `APP_URL` | ya | `http://event-system.test` | URL lokal kamu |
| `FIREBASE_PROJECT_ID` | ya | `songkran-festival-prod` | project ID Firebase dari tim |
| `FIREBASE_CREDENTIALS` | ya | `storage/app/secrets/firebase-credentials.json` | path file JSON |
| `FIREBASE_COLLECTION_PREFIX` | ya | `local_andi_` | namespace pribadi biar data tidak tabrakan |
| `MAIL_MAILER` | biasanya tidak | `log` | biarkan `log` kalau tidak test email asli |
| `RECAPTCHA_ENABLED` | biasanya tidak | `false` | matikan di lokal |

Contoh `.env` lokal paling aman:

```dotenv
APP_NAME="Event System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://event-system.test

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_SECURE_COOKIE=false

MAIL_MAILER=log
MAIL_FROM_ADDRESS="no-reply@eventsystem.local"
MAIL_FROM_NAME="${APP_NAME}"

RECAPTCHA_ENABLED=false
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=

FIREBASE_TRANSPORT=rest
FIREBASE_PROJECT_ID=ISI_DARI_TIM
FIREBASE_CREDENTIALS=storage/app/secrets/firebase-credentials.json
FIREBASE_COLLECTION_PREFIX=local_namamu_
FIREBASE_FALLBACK_LOCAL=false

EVENT_CODE=SONGKRAN2026

ADMIN_PANEL_PATH=admin
STAFF_PANEL_PATH=staff
SCANNER_POSTS=Gate A,Gate B
FRONTEND_HOMEPAGE_URL=http://event-system.test
```

### Step 8 - Generate app key

```bash
php artisan key:generate
```

### Step 9 - Jalankan migration

```bash
php artisan migrate
```

Catatan:

- migration `scanner_gates` akan membuat gate awal berdasarkan `SCANNER_POSTS`
- kalau kamu ingin gate default berbeda, ubah `SCANNER_POSTS` sebelum migration pertama

### Step 10 - Seed akun admin dan scanner

Admin panel:

```bash
php artisan admin:seed --password=Admin123!
```

Scanner staff:

```bash
php artisan scanner:seed --password=Scanner123!
```

Seeder ini aman dijalankan ulang karena memakai `updateOrCreate`.

Email default hasil seeder:

- admin: `admin01@songkran.local`, `admin02@songkran.local`, dst
- scanner: `scanner01@songkran.local`, dst

Catatan penting:

- email `.local` ini adalah akun internal bootstrap
- ini bukan email peserta
- kalau tim ingin email admin/scanner yang lebih resmi, koordinasikan dulu sebelum ubah data production

### Step 11 - Jalankan aplikasi

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

### Step 12 - Cek URL lokal

| Hal yang dicek | URL |
| --- | --- |
| Landing | `http://127.0.0.1:8000/` |
| Register | `http://127.0.0.1:8000/register` |
| Admin login | `http://127.0.0.1:8000/login` |
| Staff login | `http://127.0.0.1:8000/staff/login` |

---



Supaya kolaborasi rapi, ikuti aturan sederhana ini:

1. Selalu `git pull` dulu sebelum mulai kerja.
2. Kerja di branch sendiri, jangan langsung di branch utama.
3. Jangan commit `.env`, Firebase JSON, atau file credential lain.
4. Jangan ubah `.env.example` kecuali memang ada env variable baru untuk semua orang.
5. Setelah selesai coding, minimal jalankan:
   - `php artisan test`
   - `npm run build`
6. Kalau menambah env variable baru, dokumentasikan juga di README.
7. Kalau ubah flow scanner, gate, register, atau email, test manual juga.

Checklist sebelum bilang "sudah selesai":

- fitur jalan
- tidak ada error di browser
- tidak ada error di `storage/logs/laravel.log`
- test PHP lolos
- build frontend lolos

---

## 9. Cara Membaca `.env` dengan Benar

### Variabel yang wajib benar di semua environment

| Variable | Fungsi | Kalau salah, efeknya |
| --- | --- | --- |
| `APP_URL` | base URL utama aplikasi | link email verification / reset bisa salah domain |
| `APP_KEY` | key enkripsi Laravel | session / signed URL / cookie bisa rusak |
| `DB_CONNECTION` + `DB_DATABASE` | database internal app | admin/scanner/gates/campaign links gagal |
| `MAIL_*` | pengiriman email | register / forgot password / ticket mail gagal |
| `RECAPTCHA_*` | bot protection | form public bisa reject request |
| `FIREBASE_*` | akses Firestore | registrasi, ticket, scanner log, admin data Firestore gagal |

### Variabel yang boleh dibiarkan default dulu

Biasanya tidak perlu diubah dulu:

- `APP_LOCALE`
- `APP_FALLBACK_LOCALE`
- `APP_FAKER_LOCALE`
- `FIREBASE_*_COLLECTION`
- `ADMIN_BACKUP_*`

### Aturan emas `.env`

1. Local:
   - gunakan `FIREBASE_COLLECTION_PREFIX=local_namamu_`
   - gunakan `MAIL_MAILER=log`
   - gunakan `RECAPTCHA_ENABLED=false`
2. Production:
   - gunakan `APP_DEBUG=false`
   - gunakan `SESSION_SECURE_COOKIE=true`
   - gunakan `FIREBASE_FALLBACK_LOCAL=false`
   - gunakan `MAIL_MAILER=smtp`
   - gunakan `RECAPTCHA_ENABLED=true`
3. Jangan pernah commit:
   - `.env`
   - file JSON Firebase
   - password SMTP
   - secret reCAPTCHA

---

## 10. Rekomendasi Konfigurasi VPS Pertama Kali

Untuk deployment pertama di single VPS tanpa Docker, gunakan mode sederhana ini:

- database: SQLite
- session: file
- cache: file
- queue: sync
- Firebase transport: `rest`
- mail: SMTP
- SSL: Nginx + Certbot

Kenapa ini direkomendasikan?

- lebih sedikit moving parts
- tidak perlu Redis
- tidak perlu Supervisor
- tidak perlu cron scheduler untuk saat ini

Catatan penting:

- `app/Console/Kernel.php` saat ini belum punya scheduled task
- email saat ini dikirim secara synchronous, jadi queue worker belum wajib
- kalau nanti tim sengaja install Redis dan background queue, baru upgrade ke mode Redis

---

## 11. Data yang Harus Sudah Kalian Punya Sebelum Deploy

Sebelum login ke VPS, pastikan dari email kalian sudah punya:

1. akses SSH ke VPS
2. IP address VPS
3. domain utama
4. optional subdomain
5. kredensial Firebase / service account JSON
6. kredensial SMTP
7. key reCAPTCHA site + secret
8. akses DNS domain

Kalau salah satu dari ini belum ada, jangan mulai deploy dulu.

---

## 12. Deploy ke VPS Tanpa Docker - Step by Step Sampai Sukses

Bagian ini diasumsikan untuk:

- Ubuntu 22.04 / 24.04
- akses SSH dengan user yang punya `sudo`
- web server Nginx
- PHP 8.2

### Step 0 - Tentukan domain yang dipakai dulu

Rekomendasi paling sederhana:

- satu domain utama saja, misalnya `https://event.example.com`
- semua route gunakan path:
  - `https://event.example.com/register`
  - `https://event.example.com/login`
  - `https://event.example.com/admin`
  - `https://event.example.com/staff/login`

Kalau tim ingin subdomain juga, jangan jadikan itu blocker deploy pertama.  
Yang paling penting: satu domain utama harus hidup dulu.

### Step 1 - Arahkan DNS ke VPS

Di panel DNS domain:

- buat `A record` untuk domain utama ke IP VPS
- kalau mau alias subdomain, arahkan juga ke IP yang sama

Contoh:

- `event.example.com -> 123.123.123.123`
- `admin.example.com -> 123.123.123.123`
- `staff.example.com -> 123.123.123.123`

Setelah itu tunggu propagasi DNS.

### Step 2 - SSH ke VPS

```bash
ssh youruser@YOUR_SERVER_IP
```

Kalau berhasil, baru lanjut.

### Step 3 - Update package server

```bash
sudo apt update
sudo apt upgrade -y
```

### Step 4 - Install dependency sistem

```bash
sudo apt install -y software-properties-common curl git unzip sqlite3 nginx certbot python3-certbot-nginx
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-common php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl php8.2-sqlite3 php8.2-gd
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Install Composer:

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### Step 5 - Buka firewall dasar

Kalau pakai UFW:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### Step 6 - Siapkan folder project

```bash
sudo mkdir -p /var/www/event-system
sudo chown -R $USER:$USER /var/www/event-system
cd /var/www/event-system
```

### Step 7 - Clone repository

```bash
git clone <URL_REPOSITORY> .
```

Kalau repository private, pastikan SSH key / PAT sudah benar dulu.

### Step 8 - Buat file `.env`

Jangan copy `.env.production.example` mentah-mentah tanpa membaca.  
File itu hanya referensi, dan default-nya mengarah ke Redis / gRPC yang belum tentu ingin kalian pakai di deploy pertama.

Gunakan ini:

```bash
cp .env.example .env
```

Lalu edit:

```bash
nano .env
```

Kalau belum pernah pakai `nano`:

- save = `Ctrl + O`
- Enter untuk konfirmasi
- keluar = `Ctrl + X`

### Step 9 - Isi `.env` produksi untuk single VPS

Gunakan contoh ini, lalu ganti semua nilai yang relevan:

```dotenv
APP_NAME="Event System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://event.example.com
APP_ROUTING_MODE=subdomain
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/event-system/database/database.sqlite

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_COOKIE=event_system_session
SESSION_DOMAIN=.example.com
SESSION_SECURE_COOKIE=true

CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=ISI_DARI_EMAIL
MAIL_PORT=587
MAIL_USERNAME=ISI_DARI_EMAIL
MAIL_PASSWORD=ISI_DARI_EMAIL
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@event.example.com"
MAIL_FROM_NAME="${APP_NAME}"

RECAPTCHA_ENABLED=true
RECAPTCHA_SITE_KEY=ISI_DARI_EMAIL
RECAPTCHA_SECRET_KEY=ISI_DARI_EMAIL

FIREBASE_TRANSPORT=rest
FIREBASE_API_BASE_URL=https://firestore.googleapis.com/v1
FIREBASE_TIMEOUT_SECONDS=30
FIREBASE_PROJECT_ID=ISI_DARI_EMAIL
FIREBASE_CREDENTIALS=/var/www/event-system/storage/app/secrets/firebase-credentials.production.json
FIREBASE_DATABASE=(default)
FIREBASE_COLLECTION_PREFIX=prod_
FIREBASE_USERS_COLLECTION=
FIREBASE_TICKETS_COLLECTION=
FIREBASE_USER_EMAIL_INDEX_COLLECTION=
FIREBASE_USER_IDENTITY_INDEX_COLLECTION=
FIREBASE_SCAN_LOGS_COLLECTION=
FIREBASE_ADMIN_ACTIVITY_LOGS_COLLECTION=
FIREBASE_FALLBACK_LOCAL=false

EVENT_CODE=SONGKRAN2026
TICKET_DOWNLOAD_FONT_PATH=/var/www/event-system/storage/app/fonts/TiltWarp-Regular-VariableFont_XROT,YROT.ttf

ADMIN_PANEL_PATH=admin
ADMIN_SEED_COUNT=2
ADMIN_BOOTSTRAP_PASSWORD=

STAFF_PANEL_PATH=staff
SCANNER_SEED_COUNT=1
SCANNER_BOOTSTRAP_PASSWORD=
SCANNER_POSTS=Gate A,Gate B

FRONTEND_HOMEPAGE_URL=https://register.example.com
ADMIN_APP_URL=https://portal.example.com
REGISTER_APP_URL=https://register.example.com
STAFF_APP_URL=https://app.example.com
```

Penjelasan keputusan config di atas:

- `DB_CONNECTION=sqlite`
  - cukup untuk internal tables project ini
- `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`
  - paling simpel dan tidak butuh Redis
- `APP_ROUTING_MODE=subdomain`
  - supaya production langsung aktif di mode subdomain
- `SESSION_DOMAIN=.example.com`
  - supaya login/session tetap terbaca di `portal.`, `register.`, dan `app.`
- `FIREBASE_TRANSPORT=rest`
  - paling aman untuk VPS biasa
- `FIREBASE_CREDENTIALS` pakai absolute path
  - supaya tidak bingung saat running via Nginx / CLI

### Step 10 - Upload file Firebase JSON ke server

Di laptop kalian, download file JSON dari email dulu.  
Setelah itu upload ke server memakai `scp`.

Dari laptop:

```bash
scp /path/ke/firebase-credentials.json youruser@YOUR_SERVER_IP:/tmp/firebase-credentials.production.json
```

Lalu di server:

```bash
mkdir -p /var/www/event-system/storage/app/secrets
mv /tmp/firebase-credentials.production.json /var/www/event-system/storage/app/secrets/firebase-credentials.production.json
chmod 640 /var/www/event-system/storage/app/secrets/firebase-credentials.production.json
```

Kalau file tidak ada di path itu, Firestore tidak akan jalan.

### Step 11 - Install dependency project

```bash
cd /var/www/event-system
composer install --no-dev --optimize-autoloader
npm ci
```

### Step 12 - Build frontend production

```bash
npm run build
```

Tanpa langkah ini, asset frontend production tidak akan lengkap.

### Step 13 - Buat database SQLite

```bash
touch /var/www/event-system/database/database.sqlite
```

### Step 14 - Set permission folder yang wajib writable

```bash
sudo chown -R $USER:www-data /var/www/event-system
sudo find /var/www/event-system/storage -type d -exec chmod 775 {} \;
sudo find /var/www/event-system/storage -type f -exec chmod 664 {} \;
sudo find /var/www/event-system/bootstrap/cache -type d -exec chmod 775 {} \;
sudo find /var/www/event-system/bootstrap/cache -type f -exec chmod 664 {} \;
sudo chmod 664 /var/www/event-system/database/database.sqlite
```

Kalau nanti muncul error seperti "Permission denied", hampir pasti masalahnya ada di langkah ini.

### Step 15 - Generate app key

```bash
php artisan key:generate
```

### Step 16 - Jalankan migration

```bash
php artisan migrate --force
```

Catatan penting:

- migration `scanner_gates` akan membaca `SCANNER_POSTS`
- jadi pastikan `SCANNER_POSTS` sudah benar sebelum migration pertama di production

### Step 17 - Seed akun admin dan scanner

Jangan simpan bootstrap password di `.env` kalau tidak perlu.  
Lebih aman jalankan manual dengan option `--password`.

Admin accounts:

```bash
php artisan admin:seed --password='GANTI_PASSWORD_ADMIN_YANG_KUAT'
```

Scanner accounts:

```bash
php artisan scanner:seed --password='GANTI_PASSWORD_SCANNER_YANG_KUAT'
```

Setelah itu catat hasilnya:

- admin login: `admin01@songkran.local`
- scanner login: `scanner01@songkran.local`

### Step 18 - Jalankan preflight scanner

```bash
php artisan scanner:preflight --production
```

Kalau command ini gagal, selesaikan error-nya sebelum go-live.

### Step 19 - Konfigurasi Nginx

Buat file config:

```bash
sudo nano /etc/nginx/sites-available/event-system
```

Isi dengan contoh ini:

```nginx
server {
    listen 80;
    server_name event.example.com;

    root /var/www/event-system/public;
    index index.php index.html;

    client_max_body_size 20M;

    access_log /var/log/nginx/event-system-access.log;
    error_log /var/log/nginx/event-system-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan config:

```bash
sudo ln -s /etc/nginx/sites-available/event-system /etc/nginx/sites-enabled/event-system
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm
```

Kalau ada default site yang bentrok:

```bash
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### Step 20 - Pasang SSL

Setelah domain sudah resolve ke VPS:

```bash
sudo certbot --nginx -d event.example.com
```

Kalau kalian memang sudah menyiapkan subdomain yang mengarah ke app yang sama:

```bash
sudo certbot --nginx -d event.example.com -d admin.example.com -d staff.example.com
```

Catatan:

- route tetap path-based
- jadi meskipun subdomain dipasang, URL aman tetap:
  - `/register`
  - `/login`
  - `/staff/login`

### Step 21 - Cache konfigurasi production

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 22 - Final smoke test

Cek semua ini:

1. `https://event.example.com/` terbuka
2. `https://event.example.com/register` terbuka
3. `https://event.example.com/login` terbuka
4. `https://event.example.com/staff/login` terbuka
5. admin bisa login
6. scanner bisa login dan pilih gate saat login
7. setelah login scanner, gate tidak bisa diganti lagi dari halaman scanner
8. test registrasi peserta berhasil
9. email terkirim
10. ticket page bisa dibuka

Kalau semua lolos, deploy pertama dianggap sukses.

---

## 13. Command Update Deploy Berikutnya

Kalau app sudah pernah terpasang dan kalian hanya ingin update code:

```bash
cd /var/www/event-system
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm
```

Kalau `.env` berubah:

```bash
nano .env
php artisan optimize:clear
php artisan config:cache
```

---

## 14. Hal yang Sering Bikin Deploy Gagal

### 1. `APP_URL` salah

Gejala:

- link email verification salah domain
- reset password buka ke domain lama

Solusi:

- cek `APP_URL`
- lalu jalankan:

```bash
php artisan optimize:clear
php artisan config:cache
```

### 2. Firebase JSON tidak ketemu

Gejala:

- register gagal
- scanner preflight gagal
- Firestore storage unavailable

Solusi:

- pastikan file benar-benar ada
- pastikan path di `.env` sama
- pastikan permission file benar

### 3. SQLite tidak bisa ditulis

Gejala:

- login admin gagal
- gate management gagal
- campaign links gagal
- error `SQLSTATE[HY000]` atau `Permission denied`

Solusi:

- cek file `database/database.sqlite`
- cek owner dan permission
- cek folder `database/`, `storage/`, `bootstrap/cache`

### 4. SMTP salah

Gejala:

- registrasi submit error
- forgot password tidak kirim email

Solusi:

- cek `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`
- cek sender email memang diizinkan provider SMTP
- cek log:

```bash
tail -f storage/logs/laravel.log
```

### 5. reCAPTCHA aktif tapi key kosong

Gejala:

- form register tidak bisa submit

Solusi:

- kalau belum siap, set `RECAPTCHA_ENABLED=false`
- kalau sudah siap, isi `RECAPTCHA_SITE_KEY` dan `RECAPTCHA_SECRET_KEY`

### 6. Vite asset belum dibuild

Gejala:

- halaman blank
- CSS/JS tidak tampil
- asset 404

Solusi:

```bash
npm ci
npm run build
```

---

## 15. Kapan Harus Pakai Redis?

Untuk project ini, Redis belum wajib di deploy pertama.

Mulai pertimbangkan Redis kalau:

- traffic production sudah tinggi
- session file mulai terasa berat
- kalian sengaja mengaktifkan queue async
- kalian ingin cache yang lebih cepat dan stabil

Kalau belum ada alasan kuat, tetap pakai:

- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`

---

## 16. Kapan Harus Pindah dari SQLite?

SQLite masih layak kalau:

- hanya satu VPS
- tidak ada banyak worker paralel
- tabel relational internal masih kecil

Pertimbangkan MySQL/PostgreSQL kalau:

- ingin multi-server
- write concurrency meningkat
- tim butuh operasional DB terpisah

Saat ini, untuk single VPS sederhana, SQLite adalah jalur termudah dan paling realistis.

---

## 17. Command yang Paling Sering Dipakai Tim

### Development

```bash
composer install
npm install
php artisan serve
npm run dev
php artisan test
npm run build
```

### Database / seed

```bash
php artisan migrate
php artisan migrate:fresh
php artisan admin:seed --password=YOUR_PASSWORD
php artisan scanner:seed --password=YOUR_PASSWORD
```

### Production sanity check

```bash
php artisan scanner:preflight --production
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 18. Ringkasan Paling Penting untuk Tim Baru




1. Laravel mengurus backend dan admin panel.
2. React mengurus public pages dan staff scanner.
3. Data peserta utama ada di Firestore.
4. Database Laravel hanya untuk data internal seperti admin, scanner gate, dan campaign links.
5. Local dev paling gampang pakai SQLite + Firebase REST + mail log.
6. Deploy pertama paling aman pakai SQLite + file session/cache + sync queue + Firebase REST.
7. Scanner staff memilih gate saat login, bukan setelah login.
8. Kalau production error, cek dulu:
   - `.env`
   - permission file
   - Firebase JSON
   - SMTP
   - hasil `npm run build`

---

## 19. Catatan Keamanan

- jangan commit `.env`
- jangan commit Firebase JSON
- jangan share password di chat group
- jangan simpan bootstrap password admin/scanner di README
- setelah deploy berhasil, simpan credential di password manager tim
- kalau password bootstrap sempat ditulis sementara di `.env`, hapus lagi setelah seed selesai

---

## 20. Jika Masih Bingung, Mulai dari Mana?

Urutan belajar paling aman:

1. pahami dulu URL dan flow aplikasi
2. jalankan lokal sampai berhasil
3. login admin dan scanner
4. baca `.env.example`
5. baru ikut ubah fitur kecil
6. baru setelah itu ikut deploy ke VPS

Kalau deploy pertama terasa menakutkan, itu normal.  
Yang penting jangan lompat langkah dan jangan improvisasi pada bagian `.env`, Firebase JSON, permission, atau Nginx.
