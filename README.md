# SIS Paguyuban (Backend)

Backend API berbasis **CodeIgniter 3** untuk aplikasi SIS Paguyuban.

## Stack
- PHP (CodeIgniter 3)
- MySQL/MariaDB

## Menjalankan Lokal

### 1) Copy env
Buat file `.env` dari contoh:

```bash
cp .env.example .env
```

### 2) Install dependencies (agar .env terbaca)
CodeIgniter 3 tidak otomatis membaca `.env`. Project ini memakai `vlucas/phpdotenv` yang di-load di `index.php`.

```bash
composer install
```

### 3) Konfigurasi database
Isi variabel berikut di `.env`:

- `SIS_PAGUYUBAN_DB_HOST`
- `SIS_PAGUYUBAN_DB_PORT`
- `SIS_PAGUYUBAN_DB_NAME`
- `SIS_PAGUYUBAN_DB_USER`
- `SIS_PAGUYUBAN_DB_PASS`

### 4) Jalankan server
Jika pakai PHP built-in:

```bash
php -S localhost:8080
```

Lalu akses base URL yang sesuai di `.env` (`SIS_PAGUYUBAN_BASE_URL`).

## Konfigurasi Penting

### Base URL
`application/config/config.php` membaca:

- `SIS_PAGUYUBAN_BASE_URL` (fallback ke `http://localhost/`)

### Database
`application/config/database.php` membaca:

- `SIS_PAGUYUBAN_DB_HOST`
- `SIS_PAGUYUBAN_DB_PORT`
- `SIS_PAGUYUBAN_DB_NAME`
- `SIS_PAGUYUBAN_DB_USER`
- `SIS_PAGUYUBAN_DB_PASS`

## Production Checklist (Singkat)
- Set environment:
  - `ENVIRONMENT=production`
  - `SIS_PAGUYUBAN_BASE_URL=https://domain-kamu/`
  - `SIS_PAGUYUBAN_JWT_SECRET` harus random & panjang
- Pastikan folder writable oleh web server:
  - `application/cache/`
  - `application/logs/`
  - `uploads/`
- Pastikan HTTPS aktif dan header security sesuai kebutuhan.

## Konvensi Kode (Ringkas)
Target: konsisten, mudah di-maintain.

- Indent PHP: **4 spasi**
- Trailing whitespace dibersihkan, file selalu diakhiri newline
- Hindari komentar “noise” (komentar hanya untuk alasan bisnis/aturan yang tidak obvious)
- Penamaan:
  - method: `camelCase()`
  - variabel: `camelCase`
  - class: `PascalCase`

## Struktur Folder yang Disentuh
Refactor & cleaning difokuskan pada:

- `application/controllers/`
- `application/models/`
- `application/core/`
- `application/hooks/`
- `application/libraries/`
- `application/helpers/`

File bawaan CodeIgniter (config default, system) dibiarkan mengikuti standar aslinya.

---
