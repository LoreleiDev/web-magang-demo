#!/usr/bin/env bash
# Deploy/perbarui MagangBridge SMK di server (mis. Hostinger via SSH).
# Jalankan dari folder proyek:  bash deploy.sh
# Branch "production" dibuat otomatis oleh GitHub Actions (sudah berisi public/build).
set -euo pipefail

cd "$(dirname "$0")"

if [ ! -f .env ]; then
    echo "File .env belum ada. Salin dari .env.production.example lalu isi dulu (lihat DEPLOY.md)." >&2
    exit 1
fi

echo "==> Mode perbaikan (pengunjung melihat halaman 503 sementara)"
php artisan down --retry=30 || true

# Pastikan website kembali menyala walaupun salah satu langkah gagal.
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

echo "==> Ambil kode terbaru"
git pull --ff-only origin production

echo "==> Library PHP (tanpa paket pengembangan)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [ ! -f public/build/manifest.json ]; then
    echo "public/build tidak ada. Pastikan Anda memakai branch production, bukan main." >&2
    exit 1
fi

echo "==> Migrasi database"
php artisan migrate --force

echo "==> Cache konfigurasi, rute, view, event"
php artisan optimize

echo "==> Muat ulang antrean (dipakai cron schedule:run)"
php artisan queue:restart

php artisan up
echo "Selesai."
