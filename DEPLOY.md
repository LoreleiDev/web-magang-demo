# Deploy MagangBridge SMK ke Hostinger (Web/Cloud Hosting, hPanel)

Alur singkat:

```
laptop: git push ke main
   └─> GitHub Actions: test → build tampilan → branch "production"
server: bash deploy.sh  (git pull branch production + migrasi + cache)
```

Branch `production` dibuat otomatis oleh GitHub Actions dan sudah berisi
`public/build`, jadi server **tidak perlu Node.js/npm**. Jangan deploy dari
branch `main` (tampilan tidak akan muncul).

---

## A. Persiapan sekali saja

### 1. GitHub

1. Push ke `main`, lalu buka tab **Actions** di GitHub. Tunggu workflow
   `tests` selesai hijau (job `ci` lalu `production`).
2. Pastikan branch `production` sudah muncul di repo.
3. Jika repo **private**, server butuh izin membaca repo: di hPanel buka
   **Advanced → Git**, salin _SSH key_-nya, lalu tambahkan di GitHub
   (repo → Settings → Deploy keys, akses _read-only_). Clone memakai alamat
   SSH (`git@github.com:LoreleiDev/web-magang-demo.git`).

### 2. hPanel

| Menu                         | Pengaturan                                                                                                                                                                                                                                                               |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Advanced → PHP Configuration | Versi **PHP 8.4 atau lebih baru** (wajib; Laravel 13 + Symfony 8). Extension: `curl`, `fileinfo`, `mbstring`, `pdo_mysql`, `zip`, `xmlreader`, `dom`. Opsi: `max_execution_time` **90**, `upload_max_filesize` **25M**, `post_max_size` **50M**, `memory_limit` **256M** |
| Databases → MySQL Databases  | Buat database + user, catat nama database, user, dan password                                                                                                                                                                                                            |
| Emails → Email Accounts      | Buat `noreply@domain-anda.com` untuk email masukan materi                                                                                                                                                                                                                |
| Security → SSL               | Pasang SSL (gratis) dan aktifkan **Force HTTPS**                                                                                                                                                                                                                         |
| Advanced → SSH Access        | Aktifkan SSH, catat perintah login                                                                                                                                                                                                                                       |

### 3. Pasang aplikasi (lewat SSH)

```bash
cd ~/domains/DOMAIN-ANDA

# Ambil kode branch production ke folder terpisah (BUKAN langsung di public_html).
git clone -b production https://github.com/LoreleiDev/web-magang-demo.git magangbridge
cd magangbridge

# Konfigurasi
cp .env.production.example .env
nano .env                      # isi semua bagian bertanda "ISI"
php artisan key:generate

# Library, database, akun admin pertama, cache
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan db:seed --force    # membuat superadmin dari ADMIN_EMAIL & ADMIN_PASSWORD
php artisan optimize
chmod -R 775 storage bootstrap/cache
```

### 4. Arahkan domain ke folder `public`

Hanya folder `public/` yang boleh bisa dibuka dari internet.

```bash
cd ~/domains/DOMAIN-ANDA
mv public_html public_html_lama            # cadangan isi lama
ln -s ~/domains/DOMAIN-ANDA/magangbridge/public public_html
```

Jika symlink tidak diizinkan, alternatifnya adalah meng-clone proyek langsung ke
dalam `public_html`. File `.htaccess` di root proyek akan meneruskan semua
permintaan ke `public/` dan memblokir `.env`. Cara ini hanya cadangan; cara
symlink di atas lebih aman.

### 5. Cron Job (wajib)

hPanel → **Advanced → Cron Jobs**, jalankan **setiap menit**:

```
php /home/USERNAME/domains/DOMAIN-ANDA/magangbridge/artisan schedule:run
```

Sesuaikan `USERNAME`/path (hPanel menampilkan contoh path PHP). Cron ini
memproses email masukan materi dan sinkron dokumen ke AI Mentor. Tanpa cron,
dokumen akan selamanya berstatus "Sedang diproses AI".

### 6. Cek setelah pasang

- [ ] `https://domain-anda.com/.env` → **403 atau 404** (tidak boleh menampilkan isi)
- [ ] `https://domain-anda.com/up` → 200
- [ ] Login superadmin di `/login/admin` berhasil
- [ ] Tes Gemini (harus menampilkan `200`):
      `php artisan tinker --execute="echo Http::withHeaders(['x-goog-api-key'=>config('services.gemini.key')])->get('https://generativelanguage.googleapis.com/v1beta/models')->status();"`
- [ ] Tes email ke alamat Anda sendiri:
      `php artisan tinker --execute="Mail::raw('Tes', fn(\$m) => \$m->to('email-anda@gmail.com')->subject('Tes SMTP'));"`
- [ ] Unggah satu dokumen sekolah; dalam 1–2 menit statusnya berubah menjadi "Siap dipakai AI Mentor"

---

## B. Pindah dari pemasangan manual sebelumnya

Jika sebelumnya file disalin manual ke `public_html`:

1. **Database tetap dipakai.** Isi `DB_*` di `.env` baru dengan database yang sama.
2. **File unggahan** (bukti logbook, dokumen knowledge base) ada di
   `storage/app/private` pada pemasangan lama. Salin ke
   `~/domains/DOMAIN-ANDA/magangbridge/storage/app/private/`.
3. Ikuti langkah A.3 sampai A.6, tetapi **lewati `db:seed`** jika akun admin
   sudah ada (atau jalankan untuk menyetel ulang password admin dari `.env`).
4. Setelah semuanya berjalan, hapus `public_html_lama`.

---

## C. Memperbarui website (setiap ada perubahan)

1. Di laptop: commit lalu `git push` ke `main`.
2. Tunggu workflow di tab **Actions** hijau. Jika test gagal, branch
   `production` tidak diperbarui, dan itu disengaja.
3. Di server:
    ```bash
    cd ~/domains/DOMAIN-ANDA/magangbridge
    bash deploy.sh
    ```
    `deploy.sh` menjalankan: mode perbaikan → `git pull` branch production →
    `composer install` → migrasi → `php artisan optimize` → restart antrean →
    website aktif kembali.

---

## D. Keamanan & perawatan

- **`.env` hanya ada di server**, tidak pernah di-commit. Isinya: API key Gemini,
  password database, password email, password admin.
- `APP_DEBUG=false` dan `APP_ENV=production` wajib di server. Di production,
  `migrate:fresh` / `db:wipe` ditolak, dan `DemoSeeder` (akun contoh
  berpassword `password`) tidak bisa dijalankan.
- **Backup**: aktifkan backup otomatis di hPanel, dan sesekali unduh
  ekspor database (phpMyAdmin) serta folder `storage/app/private`.
- **Gemini**: aktifkan billing dan pasang _budget alert_ di Google Cloud; batasi
  API key hanya untuk "Generative Language API".
- Log error ada di `storage/logs/laravel-YYYY-MM-DD.log` (disimpan 14 hari).

## E. Masalah umum

| Gejala                                                | Penyebab & solusi                                                                                                          |
| ----------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Halaman putih / tampilan tidak muncul                 | `public/build` tidak ada. Pastikan memakai branch `production`                                                             |
| Error 500                                             | Lihat `storage/logs/`. Biasanya `.env` belum lengkap atau izin `storage` (jalankan `chmod -R 775 storage bootstrap/cache`) |
| "Halaman sudah kedaluwarsa" terus-menerus             | `APP_URL` harus `https://...` dan sama persis dengan domain; jalankan `php artisan optimize` lagi                          |
| Perubahan `.env` tidak berpengaruh                    | Jalankan `php artisan optimize` (config ter-cache)                                                                         |
| Dokumen AI tidak pernah "Siap" / email tidak terkirim | Cron Job belum jalan atau path-nya salah                                                                                   |
| AI Mentor "sedang sibuk"                              | Kuota Gemini habis atau model lambat; cek `storage/logs`                                                                   |
