# How To Implement Near Realtime Admin Pages

Dokumen ini menjelaskan pola yang dipakai untuk membuat halaman `Admin > User Management` menjadi near realtime, lalu menjadikannya template untuk halaman admin lain.

Target pembaca:
- agent yang akan lanjut implementasi
- junior developer yang perlu mengikuti pola yang aman

Bahasa sederhananya:
- request admin harus tetap ringan
- update data harus terasa cepat
- halaman besar tidak boleh mengandalkan rebuild penuh di setiap request
- cache boleh ada, tapi jangan sampai membuat data terlihat "mati"

---

## 1. Tujuan yang ingin dicapai

Pada `User Management`, kita ingin:
- search cepat
- filter cepat
- card overview ikut bergerak
- edit user / attendance update tidak memicu rebuild penuh yang berat
- tetap aman dipakai saat data sudah besar

Near realtime di sini bukan berarti setiap request menembak semua koleksi Firestore dari nol. Near realtime berarti:
- write path mengirim sync kecil sesegera mungkin
- read path membaca projection yang sudah siap
- meta berat direfresh async
- ada reconcile berkala untuk menutup drift

---

## 2. Prinsip desain yang dipakai

Pola yang dipakai adalah:

1. Pisahkan write model dan read model.
2. Jadikan read model sebagai sumber utama untuk halaman besar.
3. Sync satu row saat ada perubahan kecil.
4. Rebuild penuh hanya untuk warmup, repair, atau reconcile.
5. Hitung card default secara live dari projection saat dataset sudah besar.
6. Jangan meletakkan pekerjaan berat di request admin.

Kalau diringkas:

`mutation kecil -> queue sync kecil -> projection update -> halaman baca projection`

Bukan:

`mutation kecil -> rebuild semua data -> request admin menunggu`

---

## 3. File inti yang dipakai di User Management

Read entry:
- `app/Http/Controllers/Admin/UserManagementController.php`
- `app/Services/Admin/AdminPanelService.php`
- `app/Services/Admin/AdminUserManagementReadModel.php`
- `app/Services/Admin/AdminAnalyticsService.php`

Dispatch / queue:
- `app/Services/Admin/AdminUserManagementReadModelDispatcher.php`
- `app/Jobs/SyncUserManagementReadModelJob.php`
- `app/Jobs/RefreshUserManagementReadModelMetaJob.php`
- `app/Jobs/RebuildUserManagementReadModelJob.php`

Trigger dari write path:
- `app/Services/Auth/RegistrationService.php`
- `app/Services/Staff/StaffScannerService.php`
- `app/Services/Admin/AdminPanelService.php`

Config / schedule:
- `config/admin.php`
- `bootstrap/app.php`

Testing:
- `tests/Unit/AdminUserManagementReadModelTest.php`
- `tests/Unit/AdminPanelServiceTest.php`

---

## 4. Bentuk arsitektur yang dipakai

### 4.1 Read model

`AdminUserManagementReadModel` menyimpan projection yang sudah siap dipakai halaman admin.

Isi projection per user kurang lebih:
- identitas user
- ticket info
- attendance status
- verification status
- account status
- email typo metadata
- field tambahan yang dibutuhkan UI

Storage key utama di read model:
- meta
- sync status
- rows
- order

Lihat konstanta di:
- `app/Services/Admin/AdminUserManagementReadModel.php`

### 4.2 Dua jenis data di read model

Ada dua lapisan:

1. `rows`
- sumber data utama untuk filter, search, dan page rows
- harus paling cepat update

2. `meta`
- overview cards
- filter options
- lebih mahal untuk dihitung kalau dataset besar

Keputusan penting yang dipakai:
- `rows` disinkronkan secepat mungkin
- `meta` direfresh async atau berkala
- untuk dataset besar, default overview tidak boleh bergantung penuh pada meta snapshot

---

## 5. Alur baca halaman User Management

Controller memanggil:

`UserManagementController@index -> AdminPanelService::userManagementPage()`

Di `AdminPanelService::userManagementPage()`:
- kalau read model aktif dan filter didukung, langsung coba baca dari read model
- kalau read model gagal, fallback ke legacy path

Artinya keputusan utama ada di:
- `AdminPanelService::userManagementPage()`
- `AdminUserManagementReadModel::supportsFilters()`
- `AdminUserManagementReadModel::page()`

### 5.1 Untuk search dan filter

Saat ada filter seperti:
- `q`
- `country`
- `identity_type`
- `verification_status`
- `attendance_status`
- `email_typo`

read model memakai:

`filteredPageUsingRedis()`

Pola penting di method ini:
- scan `ORDER_CACHE_KEY` per chunk
- ambil row dengan `hmget` hanya untuk chunk saat itu
- filter di memory kecil per chunk
- hitung overview sambil jalan
- simpan hanya row yang diperlukan untuk page aktif

Manfaat:
- tidak `hgetall`
- memory tetap rendah
- filter dan search tetap cepat walau data besar

### 5.2 Untuk default page tanpa filter

Ini titik yang dulu bikin card default terlihat stale.

Sebelumnya:
- default page banyak memakai `meta['overview']`
- kalau meta refresh belum jalan, cards tetap menampilkan angka lama

Perbaikannya:
- untuk projection Redis besar, default page diarahkan juga ke jalur live overview
- method `page()` memanggil `filteredPageUsingRedis([])` bila inline meta refresh dimatikan untuk dataset besar

Artinya:
- default overview sekarang dihitung live dari projection rows
- perilakunya jadi sejalan dengan filter dan search
- request tetap aman karena tetap chunked, bukan load semua row

Method penting:
- `AdminUserManagementReadModel::page()`
- `AdminUserManagementReadModel::filteredPageUsingRedis()`
- `AdminUserManagementReadModel::shouldServeLiveOverviewForIndexedPage()`

---

## 6. Alur write yang membuat data cepat bergerak

### 6.1 Registration

Di:
- `app/Services/Auth/RegistrationService.php`

Setelah user berhasil register:
- legacy cache ditandai stale
- dispatcher memanggil `syncUser(user_id, 'registration')`

### 6.2 Attendance success dari scanner

Di:
- `app/Services/Staff/StaffScannerService.php`

Saat scan sukses:
- dispatcher memanggil `syncUser(user_id, 'attendance_success')`

Catatan:
- hanya sync jika result `success`
- ini menjaga jalur scanner tetap ringan

### 6.3 Admin edit / delete / QR actions

Di:
- `app/Services/Admin/AdminPanelService.php`

Saat admin update:
- cache lama ditandai stale
- cached row disinkronkan seperlunya
- dispatcher memanggil `syncUser(...)`
- dispatcher juga memanggil `requestMetaRefresh(...)`

Saat delete:
- dispatcher memanggil `removeUser(...)`
- lalu `requestMetaRefresh(...)`

Ini penting:
- row sync cepat untuk efek near realtime
- meta refresh async supaya overview/filter options ikut mengejar

---

## 7. Peran dispatcher dan job

### 7.1 Dispatcher

File:
- `app/Services/Admin/AdminUserManagementReadModelDispatcher.php`

Tugas dispatcher:
- tidak mengerjakan bisnis berat
- hanya mengirim job ke queue yang benar

Pemisahan queue:
- `sync_queue` untuk update kecil yang mendesak
- `rebuild_queue` untuk meta refresh / rebuild yang lebih berat

Default saat ini:
- sync cepat ke `admin-sync-high`
- rebuild/meta ke `admin-sync-low`

### 7.2 Sync job

File:
- `app/Jobs/SyncUserManagementReadModelJob.php`

Tugas:
- sync satu user saja
- remove satu user jika perlu
- ada `WithoutOverlapping` per `userId`

Kenapa ini penting:
- dua event untuk user yang sama tidak saling tabrak
- queue tetap aman

### 7.3 Meta refresh job

File:
- `app/Jobs/RefreshUserManagementReadModelMetaJob.php`

Tugas:
- hitung ulang meta dari projection yang sudah ada
- tidak menarik ulang semua data sumber dari nol

Ini dipakai untuk:
- mengejar filter options
- mengejar overview snapshot
- membetulkan drift ringan

### 7.4 Rebuild job

File:
- `app/Jobs/RebuildUserManagementReadModelJob.php`

Tugas:
- rebuild total projection dari data sumber
- dipakai untuk warmup, page miss, atau reconcile periodik

Rebuild penuh jangan dijadikan jalur utama near realtime.

---

## 8. Scheduler yang dipakai

Laravel 11 di project ini mendaftarkan scheduler di:
- `bootstrap/app.php`

Bukan di:
- `app/Console/Kernel.php`

Schedule yang dipakai:
- `RefreshUserManagementReadModelMetaJob` setiap `3 menit`
- `RebuildUserManagementReadModelJob` setiap `15 menit`

Konfigurasinya ada di:
- `config/admin.php`

Env yang relevan:
- `ADMIN_USER_MANAGEMENT_READ_MODEL_ENABLED`
- `ADMIN_USER_MANAGEMENT_QUEUE_CONNECTION`
- `ADMIN_USER_MANAGEMENT_SYNC_QUEUE`
- `ADMIN_USER_MANAGEMENT_REBUILD_QUEUE`
- `ADMIN_USER_MANAGEMENT_META_REFRESH_MINUTES`
- `ADMIN_USER_MANAGEMENT_RECONCILE_MINUTES`
- `ADMIN_USER_MANAGEMENT_INLINE_META_SYNC_MAX_ROWS`

---

## 9. Kenapa ada `inline_meta_sync_max_rows`

Masalah nyata saat dataset besar:
- menghitung ulang meta penuh setiap mutasi itu mahal
- web request bisa makan memory terlalu tinggi
- PHP-FPM request bisa mati

Karena itu ada guardrail:

`admin.user_management.inline_meta_sync_max_rows`

Aturannya:
- kalau row count kecil, meta boleh ikut direfresh inline
- kalau row count besar, sync user hanya update row + sync status
- meta dibiarkan direfresh async lewat job atau scheduler

Namun setelah hotfix default overview:
- walau meta belum sempat update
- default cards tetap bisa live dari projection

Ini adalah kombinasi yang aman.

---

## 10. Kenapa default overview sempat tidak realtime

Root cause lama:
- filter/search membaca projection rows
- default cards membaca meta snapshot
- meta snapshot direfresh async, bukan setiap mutasi

Akibatnya:
- filter `Check-In Status` terlihat benar
- search terlihat benar
- tapi halaman default masih menampilkan angka lama

Solusi yang dipakai:
- jangan paksa full meta refresh inline pada dataset besar
- ubah default read path agar overview bisa dihitung live dari projection rows yang sudah ada
- tetap pertahankan meta refresh async untuk filter options dan repair

Pelajaran untuk halaman lain:
- kalau rows realtime tapi cards tidak realtime, cek apakah cards masih membaca snapshot lama

---

## 11. Checklist implementasi near realtime untuk halaman admin lain

Gunakan checklist ini.

### Langkah 1 - Tentukan SLA

Tulis dengan jelas:
- data apa yang harus terasa realtime
- data apa yang boleh tertinggal 1-5 menit
- data apa yang hanya perlu reconcile periodik

Contoh:
- row detail: near realtime
- counter utama: near realtime
- opsi filter berat: async 1-3 menit
- repair total: 15 menit

### Langkah 2 - Tentukan read model

Buat projection yang langsung cocok untuk UI.

Jangan simpan data mentah kalau UI masih harus join berat di request.

Projection ideal berisi:
- semua kolom yang dipakai tabel
- semua kolom yang dipakai filter
- semua kolom yang dipakai counter utama
- ordering field

### Langkah 3 - Pisahkan row data dan meta

Buat minimal:
- `rows`
- `order`
- `meta`
- `sync_status`

Kalau semuanya dijadikan satu blob besar, nanti akan sulit dibuat near realtime.

### Langkah 4 - Buat sync kecil per entity

Setiap mutasi penting harus bisa memanggil:
- `syncEntity(id)`
- atau `removeEntity(id)`

Jangan jadikan rebuild total sebagai satu-satunya cara update.

### Langkah 5 - Gunakan queue prioritas

Pisahkan:
- queue cepat untuk sync kecil
- queue lambat untuk rebuild/meta refresh

Ini penting saat traffic tinggi.

### Langkah 6 - Pastikan read path bisa hidup tanpa rebuild penuh

Read path ideal:
- page default bisa dari projection
- filter/search bisa dari projection
- counters bisa dari projection atau count query ringan

Kalau request masih harus rebuild dulu, desainnya belum siap.

### Langkah 7 - Pakai chunked scan untuk dataset besar

Kalau filter tidak bisa memakai index murni:
- scan projection per chunk
- jangan `load all`
- hitung counter sambil jalan

Ini pola yang dipakai di `filteredPageUsingRedis()`.

### Langkah 8 - Tambahkan reconcile berkala

Tetap sediakan:
- warmup awal
- refresh meta berkala
- rebuild total berkala

Near realtime tanpa reconcile biasanya akan drift.

### Langkah 9 - Sediakan sync status ke UI

User admin perlu tahu:
- data fresh
- data degraded
- data fallback
- sedang rebuild

Pola ini sudah ada di:
- `app/Services/Admin/AdminUserManagementSyncStatusFactory.php`

### Langkah 10 - Uji kondisi dataset besar

Jangan hanya test pada data kecil.

Minimal uji:
- default page
- search
- filter
- update setelah mutation
- memory limit rendah
- fallback saat read model belum siap

---

## 12. Anti-pattern yang harus dihindari

Jangan lakukan ini:

1. Rebuild semua projection di dalam request admin.
2. Ambil semua row dengan `hgetall` untuk search/filter pada dataset besar.
3. Mengandalkan snapshot meta lama untuk card yang harus terlihat hidup.
4. Menaruh query Firestore berat langsung di halaman admin populer.
5. Membuat queue sync dan rebuild bercampur di jalur prioritas yang sama.
6. Tidak punya reconcile periodik.
7. Tidak punya test untuk dataset besar.

---

## 13. Cara berpikir saat menerapkan ke halaman lain

Sebelum coding, jawab dulu:

1. Event apa yang mengubah halaman ini?
2. Apa unit sync terkecilnya?
3. Data apa yang wajib realtime?
4. Data apa yang boleh async?
5. Kalau projection telat, fallback apa yang aman?
6. Kalau data sudah 10x lebih besar, bagian mana yang akan meledak dulu?

Kalau enam pertanyaan ini belum terjawab, jangan langsung implementasi.

---

## 14. Template implementasi singkat

Pakai template mental ini:

1. Buat `XReadModel`
2. Buat `XReadModelDispatcher`
3. Buat job:
- `SyncXReadModelJob`
- `RefreshXReadModelMetaJob`
- `RebuildXReadModelJob`
4. Hubungkan semua mutation source ke dispatcher
5. Ubah read path agar membaca projection
6. Pisahkan row sync dan meta refresh
7. Tambahkan schedule refresh + reconcile
8. Tambahkan sync status ke UI
9. Tambahkan test small dataset + large dataset
10. Verifikasi di production dengan memory limit realistis

---

## 15. Verifikasi minimal sebelum deploy

Minimal jalankan:
- unit test service read model
- unit test service page/admin layer
- test kasus mutation lalu baca halaman
- test default page
- test filter/search

Di production, minimal cek:
- scheduler aktif
- worker queue cepat aktif
- worker queue lambat aktif
- default page tidak 500
- filter/search tidak 500
- memory usage request tetap rendah
- angka default bergerak setelah mutation

---

## 16. Ringkasan praktis

Pola near realtime yang dipakai di `User Management` adalah:

- projection/read model dijadikan pusat baca
- mutasi kecil mengirim sync kecil ke queue cepat
- meta berat direfresh async
- rebuild total hanya untuk warmup/reconcile
- filter/search memakai chunked scan projection
- default overview untuk dataset besar juga dihitung live dari projection agar tidak stale

Kalau halaman admin lain mau mengikuti pola ini, jangan mulai dari UI dulu. Mulailah dari:
- event yang mengubah data
- bentuk projection yang dibutuhkan UI
- pemisahan row sync vs meta refresh
- jalur baca yang tidak berat

Itu fondasi yang membuat near realtime terasa bagus dan tetap aman.
