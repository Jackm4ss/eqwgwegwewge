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
   - pasang SSL Let's Encrypt
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

Project ini mendukung 2 mode routing:

1. `path` mode untuk local/dev
2. `subdomain` mode untuk production

### A. Default local/dev (`APP_ROUTING_MODE=path`)

| Area | URL |
| --- | --- |
| Landing page | `/` |
| Register form | `/register` |
| Admin login | `/login` |
| Admin panel | `/admin` |
| Staff scanner login | `/staff/login` |
| Staff scanner dashboard | `/staff` |

### B. Target production final (`APP_ROUTING_MODE=subdomain`)

| Area | URL production |
| --- | --- |
| Landing page | `https://songkranfestival.my/` |
| Register form | `https://register.songkranfestival.my/` |
| Helpdesk report | `https://help.songkranfestival.my/` |
| Admin login | `https://portal.songkranfestival.my/login` |
| Admin dashboard | `https://portal.songkranfestival.my/dashboard` |
| Staff scanner login | `https://app.songkranfestival.my/login` |
| Staff scanner app | `https://app.songkranfestival.my/` |

Mapping domain yang dipakai klien sekarang adalah:

| Host | Record | IP |
| --- | --- | --- |
| `songkranfestival.my` | `A` | `147.93.157.20` |
| `app.songkranfestival.my` | `A` | `147.93.157.20` |
| `help.songkranfestival.my` | `A` | `147.93.157.20` |
| `portal.songkranfestival.my` | `A` | `147.93.157.20` |
| `register.songkranfestival.my` | `A` | `147.93.157.20` |

Catatan penting:

- deploy production final untuk project ini harus memakai mode subdomain
- semua host di atas tetap mengarah ke root Laravel yang sama
- pembedaan landing, register, helpdesk, admin, dan scanner dilakukan oleh aplikasi berdasarkan `Host` request
- SSL production diasumsikan memakai Let's Encrypt untuk kelima host tersebut

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
SESSION_CONNECTION=default
CACHE_STORE=file
QUEUE_CONNECTION=sync
REGISTRATION_REDIS_MODE=disabled
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

BOOTSTRAP_EMAIL_DOMAIN=songkran.local
ADMIN_PANEL_PATH=admin
ADMIN_SEED_COUNT=8
STAFF_PANEL_PATH=staff
SCANNER_SEED_COUNT=1
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

Email hasil seeder mengikuti `BOOTSTRAP_EMAIL_DOMAIN`:

- admin: `admin01@{BOOTSTRAP_EMAIL_DOMAIN}`, `admin02@{BOOTSTRAP_EMAIL_DOMAIN}`, dst
- scanner: `scanner01@{BOOTSTRAP_EMAIL_DOMAIN}`, dst

Catatan penting:

- default local tetap aman di `songkran.local`
- kalau `BOOTSTRAP_EMAIL_DOMAIN=songkranfestival.my`, maka hasilnya jadi `admin01@songkranfestival.my`, `scanner01@songkranfestival.my`, dst
- jumlah akun mengikuti `ADMIN_SEED_COUNT` dan `SCANNER_SEED_COUNT`
- ini bukan email peserta

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

## 10. Profil Runtime yang Direkomendasikan

Project ini sekarang punya 2 profil runtime yang jelas:

- Local / dev profile
  - `SESSION_DRIVER=file`
  - `CACHE_STORE=file`
  - `QUEUE_CONNECTION=sync`
  - `REGISTRATION_REDIS_MODE=disabled`
- Production / VPS profile
  - `SESSION_DRIVER=redis`
  - `CACHE_STORE=redis`
  - `QUEUE_CONNECTION=redis`
  - `REGISTRATION_REDIS_MODE=required`

Kenapa dibagi seperti ini?

- intern dan junior tetap bisa jalan tanpa setup Redis di laptop
- production tetap fail keras kalau Redis belum siap
- popup sukses registrasi tetap penuh, tetapi email berat dipindah ke background queue

Catatan penting:

- `app/Console/Kernel.php` saat ini belum punya scheduled task yang wajib
- untuk production, queue worker Redis wajib aktif
- jalankan `php artisan registration:preflight --production` sebelum go-live

---

## 11. Data yang Harus Sudah Kalian Punya Sebelum Deploy

Sebelum login ke VPS, pastikan dari email kalian sudah punya:

1. akses SSH ke VPS
2. IP address VPS
3. domain utama dan subdomain final
4. kredensial Firebase / service account JSON
5. kredensial SMTP
6. key reCAPTCHA site + secret
7. akses DNS domain

Kalau salah satu dari ini belum ada, jangan mulai deploy dulu.

Untuk deployment production yang sedang disiapkan sekarang, DNS dari client sudah diarahkan ke VPS ini:

| Host | Record | Tujuan |
| --- | --- | --- |
| `songkranfestival.my` | `A` | `147.93.157.20` |
| `app.songkranfestival.my` | `A` | `147.93.157.20` |
| `help.songkranfestival.my` | `A` | `147.93.157.20` |
| `portal.songkranfestival.my` | `A` | `147.93.157.20` |
| `register.songkranfestival.my` | `A` | `147.93.157.20` |

Jadi di tahap deploy, fokusnya bukan lagi membuat struktur domain baru, tetapi memverifikasi propagasi DNS, menyalakan Nginx, lalu menerbitkan SSL Let's Encrypt.

---

## 12. Deploy ke VPS Tanpa Docker - Step by Step Sampai Sukses

Bagian ini diasumsikan untuk:

- Ubuntu 22.04 / 24.04
- akses SSH dengan user yang punya `sudo`
- web server Nginx
- PHP 8.2

### Step 0 - Konfirmasi Mapping Domain Final

Untuk project ini, target production finalnya sudah ditetapkan seperti ini:

- landing/public: `https://songkranfestival.my`
- register: `https://register.songkranfestival.my`
- helpdesk report: `https://help.songkranfestival.my`
- admin dashboard: `https://portal.songkranfestival.my`
- staff scanner: `https://app.songkranfestival.my`

Artinya:

- `.env` production harus memakai `APP_ROUTING_MODE=subdomain`
- Nginx harus menerima lima host tersebut dalam satu vhost Laravel
- SSL Let's Encrypt harus diterbitkan untuk lima domain itu sekaligus

### Step 1 - Verifikasi DNS yang Sudah Dipoint ke VPS

Client sudah mengarahkan DNS ini ke server production:

| Host | Record | IP |
| --- | --- | --- |
| `songkranfestival.my` | `A` | `147.93.157.20` |
| `app.songkranfestival.my` | `A` | `147.93.157.20` |
| `help.songkranfestival.my` | `A` | `147.93.157.20` |
| `portal.songkranfestival.my` | `A` | `147.93.157.20` |
| `register.songkranfestival.my` | `A` | `147.93.157.20` |

Yang perlu dilakukan di tahap ini adalah verifikasi resolve DNS dari sisi publik, misalnya dengan:

```bash
dig +short songkranfestival.my
dig +short portal.songkranfestival.my
dig +short app.songkranfestival.my
dig +short help.songkranfestival.my
dig +short register.songkranfestival.my
```

Semua hasilnya harus kembali ke `147.93.157.20` sebelum lanjut ke Certbot / Let's Encrypt.

### Step 2 - SSH ke VPS

```bash
ssh youruser@147.93.157.20
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
APP_URL=https://songkranfestival.my
APP_ROUTING_MODE=subdomain
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/event-system/database/database.sqlite

SESSION_DRIVER=redis
SESSION_CONNECTION=default
SESSION_LIFETIME=120
SESSION_COOKIE=event_system_session
SESSION_DOMAIN=.songkranfestival.my
SESSION_SECURE_COOKIE=true

CACHE_STORE=redis
QUEUE_CONNECTION=redis
FILESYSTEM_DISK=local
REGISTRATION_REDIS_MODE=required
REGISTRATION_EMAIL_QUEUE=registration-emails

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_USERNAME=
REDIS_PASSWORD=
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_CONNECTION=default
REDIS_CACHE_CONNECTION=cache
REDIS_QUEUE=default

MAIL_MAILER=smtp
MAIL_HOST=ISI_DARI_EMAIL
MAIL_PORT=587
MAIL_USERNAME=ISI_DARI_EMAIL
MAIL_PASSWORD=ISI_DARI_EMAIL
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@songkranfestival.my"
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

BOOTSTRAP_EMAIL_DOMAIN=songkranfestival.my
ADMIN_PANEL_PATH=admin
ADMIN_SEED_COUNT=2
ADMIN_BOOTSTRAP_PASSWORD=

STAFF_PANEL_PATH=staff
SCANNER_SEED_COUNT=1
SCANNER_BOOTSTRAP_PASSWORD=
SCANNER_POSTS=Gate A,Gate B

FRONTEND_HOMEPAGE_URL=https://songkranfestival.my
ADMIN_APP_URL=https://portal.songkranfestival.my
REGISTER_APP_URL=https://register.songkranfestival.my
HELP_APP_URL=https://help.songkranfestival.my
STAFF_APP_URL=https://app.songkranfestival.my
```

Penjelasan keputusan config di atas:

- `DB_CONNECTION=sqlite`
  - cukup untuk internal tables project ini
- `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`
  - production profile wajib pakai Redis supaya registrasi berat tidak membebani request utama
- `REGISTRATION_REDIS_MODE=required`
  - memaksa flow registrasi production tetap memakai Redis queue, bukan fallback diam-diam ke mode sync
- `APP_ROUTING_MODE=subdomain`
  - supaya production langsung aktif di mode subdomain
- `SESSION_DOMAIN=.songkranfestival.my`
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
scp /path/ke/firebase-credentials.json youruser@147.93.157.20:/tmp/firebase-credentials.production.json
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

- admin login: `admin01@{BOOTSTRAP_EMAIL_DOMAIN}`
- scanner login: `scanner01@{BOOTSTRAP_EMAIL_DOMAIN}`

Untuk target deploy sekarang, kalau `BOOTSTRAP_EMAIL_DOMAIN=songkranfestival.my`, maka hasil nyatanya menjadi:

- admin login: `admin01@songkranfestival.my`
- scanner login: `scanner01@songkranfestival.my`

### Step 18 - Jalankan preflight scanner dan registrasi

```bash
php artisan scanner:preflight --production
php artisan registration:preflight --production
```

Kalau salah satu command ini gagal, selesaikan error-nya sebelum go-live.

### Step 19 - Jalankan worker Redis untuk email registrasi

Minimal jalankan worker ini di production:

```bash
php artisan queue:work redis --queue=registration-emails,default --tries=3
```

Kalau pakai Supervisor atau systemd, command di atas yang harus dijaga tetap hidup.

### Step 20 - Konfigurasi Nginx

Buat file bootstrap config dulu:

```bash
sudo cp deploy/nginx/songkranfestival.my.bootstrap.conf.example /etc/nginx/sites-available/songkranfestival.my
sudo nano /etc/nginx/sites-available/songkranfestival.my
```

Di file itu, sesuaikan minimal 2 hal:

- `root /var/www/event-system/public;`
- `fastcgi_pass unix:/run/php/php8.2-fpm.sock;`

Arsitektur production yang dipakai sekarang adalah:

- landing/public: `songkranfestival.my`
- admin: `portal.songkranfestival.my`
- scanner: `app.songkranfestival.my`
- register: `register.songkranfestival.my`
- helpdesk: `help.songkranfestival.my`

Semua domain itu tetap mengarah ke root Laravel yang sama.  
Pembedaan behavior dilakukan oleh aplikasi berdasarkan host request, jadi Nginx paling aman justru memakai satu vhost untuk semua domain itu.

Aktifkan config:

```bash
sudo ln -s /etc/nginx/sites-available/songkranfestival.my /etc/nginx/sites-enabled/songkranfestival.my
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

### Step 21 - Pasang SSL

Karena deploy production ini memang akan memakai Let's Encrypt, pastikan lima host sudah resolve ke `147.93.157.20` sebelum menjalankan Certbot.

Setelah DNS resolve, jalankan:

```bash
sudo certbot --nginx -d songkranfestival.my -d portal.songkranfestival.my -d app.songkranfestival.my -d register.songkranfestival.my -d help.songkranfestival.my
```

Command di atas akan:

- memvalidasi domain lewat HTTP challenge
- membuat sertifikat Let's Encrypt untuk lima host sekaligus
- memasang redirect HTTPS jika konfigurasi Nginx sudah sesuai

Setelah SSL berhasil dibuat, ganti bootstrap config dengan file final:

```bash
sudo cp deploy/nginx/songkranfestival.my.conf.example /etc/nginx/sites-available/songkranfestival.my
sudo nano /etc/nginx/sites-available/songkranfestival.my
sudo nginx -t
sudo systemctl reload nginx
```

Di file final itu, cek lagi 3 hal:

- `root /var/www/event-system/public;`
- `fastcgi_pass unix:/run/php/php8.2-fpm.sock;`
- path SSL:
  - `/etc/letsencrypt/live/songkranfestival.my/fullchain.pem`
  - `/etc/letsencrypt/live/songkranfestival.my/privkey.pem`

File referensi yang bisa dipakai dari repo:

- `deploy/nginx/songkranfestival.my.bootstrap.conf.example`
- `deploy/nginx/songkranfestival.my.conf.example`

### Step 22 - Cache konfigurasi production

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 23 - Final smoke test

Cek semua ini:

1. `https://songkranfestival.my/` terbuka
2. `https://register.songkranfestival.my/` terbuka
3. `https://help.songkranfestival.my/` terbuka
4. `https://portal.songkranfestival.my/login` terbuka
5. `https://app.songkranfestival.my/login` terbuka
6. admin bisa login di `portal.songkranfestival.my`
7. scanner bisa login di `app.songkranfestival.my` dan pilih gate saat login
8. setelah login scanner, gate tidak bisa diganti lagi dari halaman scanner
9. test registrasi peserta dari `register.songkranfestival.my` berhasil
10. test halaman report helpdesk dari `help.songkranfestival.my` berhasil
11. short link campaign seperti `https://songkranfestival.my/fb` redirect ke tujuan yang benar
12. email verification / reset link membuka domain public yang benar
13. ticket page bisa dibuka

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

Untuk project ini:

- local development tidak wajib Redis
- production wajib Redis

Praktiknya sederhana:

- di laptop developer, tetap pakai `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, dan `REGISTRATION_REDIS_MODE=disabled`
- di VPS production, pakai `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, dan `REGISTRATION_REDIS_MODE=required`

Kalau production belum bisa memenuhi itu, jangan go-live dulu. Jalankan:

```bash
php artisan registration:preflight --production
```

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
6. Production profile registrasi sekarang wajib pakai Redis untuk session, cache, dan queue; local tetap boleh tanpa Redis.
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
