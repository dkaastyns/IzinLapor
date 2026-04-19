# Dokumentasi Sistem Pengaduan Fasilitas Rusak - SMAN 11 Semarang

## 1. Pendahuluan
Sistem Pengaduan Fasilitas Rusak SMAN 11 Semarang adalah aplikasi pelaporan dan manajemen keluhan sarana dan prasarana sekolah berbasis web. Aplikasi ini dirancang agar siswa-siswi dan staf pengajar dapat melaporkan kerusakan infrastruktur sekolah secara digital, efisien, dan transparan.

Proyek ini dibangun oleh **Kelompok 38 RPL (Rekayasa Perangkat Lunak)**.

---

## 2. Arsitektur Proyek (Tech Stack)
Aplikasi ini diimplementasikan menggunakan arsitektur **Single Page Application (SPA)** yang modern dengan tumpukan teknologi (tech stack) sebagai berikut:

### Backend
- **Framework:** Laravel 11.x (PHP 8)
- **Database:** PostgreSQL
- **Real-Time WebSockets:** Laravel Reverb / Pusher

### Frontend
- **Framework:** Vue.js 3 (Composition API)
- **Bridge Backend-Frontend:** Inertia.js (Menghadirkan pengalaman SPA tanpa menghilangkan routing ala server-side Laravel)
- **Styling:** Tailwind CSS

### Infrastruktur / Pihak Ketiga
- **Penyimpanan Storage Gambar:** Cloudinary API
- **Deployment Server:** Render.com

---

## 3. Fitur Utama
1. **Autentikasi Terpusat:** Akses aplikasi dikunci berdasarkan domain resmi (contoh: `@student.sman11.sch.id` untuk siswa, `@sman11.sch.id` untuk staf sekolah).
2. **Dashboard Pengguna (Siswa/Staf):** Untuk mengirim tiket pelaporan dengan menyertakan bukti foto, keterangan, lokasi, dan kategori kerusakan.
3. **Dashboard Admin / Sarpras:** Untuk menindaklanjuti status pelaporan (Menerima, Mengubah Status ke 'Diproses' atau 'Selesai', dan Menolak Pelaporan Invalid).
4. **Tracking Real-time:** Sistem memunculkan progress keluhan secara real time beserta estimasi penyelesaian.
5. **Notifikasi Real-time:** Pengguna akan dibertahukan ketika status pelaporannya berubah berkat implementasi WebSocket.
6. **Desain Premium (Liquid Glass UI):** Antarmuka estetik dan responsif, modern look-and-feel untuk kenyamanan interaksi pengguna.

---

## 4. Konfigurasi Lingkungan (`.env`)
Untuk menjalankan server ini, pastikan Anda telah menyiapkan variabel lingkungan (`.env`) berikut dengan benar:

```ini
APP_NAME="Pengaduan SMAN 11"
APP_ENV=local
APP_KEY= # Generate via `php artisan key:generate`
APP_DEBUG=true
APP_URL=http://localhost:8000

# Konfigurasi Database PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pengaduan-sman11
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Konfigurasi Cloudinary (Bukti Foto)
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME

# Konfigurasi Pusher / Reverb (Notifikasi Real-time)
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your_reverb_id
REVERB_APP_KEY=your_reverb_key
REVERB_APP_SECRET=your_reverb_secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

---

## 5. Panduan Pengembangan (Development Workflow)

### Menjalankan Server Lokal (Laravel Laragon/Valet/Artisan)
1. Buka dua jendela terminal di folder proyek.
2. Pada terminal 1, jalankan server PHP:
   ```bash
   php artisan serve
   ```
3. Pada terminal 2, kompilasi aset Frontend dengan Vite:
   ```bash
   npm run dev
   ```

### Alur Migrasi Database
Jika ada perubahan struktur database aplikasi, tim harus melakukan operasi migrasi:
```bash
php artisan migrate
```
Untuk menginisialisasi database dari awal dengan data dummy:
```bash
php artisan migrate:fresh --seed
```

---

## 6. Penjelasan Struktur Folder Penting
- `app/Http/Controllers/`: Menyimpan semua logika bisnis Backend (Cth: `ComplaintController.php`).
- `app/Models/`: Menyimpan skema Relasi Database (Eloquent ORM).
- `resources/js/Pages/`: Menyimpan Halaman Utama aplikasi (Komponen Vue.js berbasis Inertia).
- `resources/js/Components/`: Menyimpan komponen UI independen dan dapat didaur ulang (Cth: Tombol, Form, `FluidBackground.vue`, Alert).
- `database/migrations/`: Menyimpan versi skema database (Tabel Complaints, Users, Categories, dsb).
- `routes/web.php` dan `routes/channels.php`: Menangani konfigurasi URL masuk dan hak rute Broadcast (Real-time).

---

## 7. Deployment (Produksi)
Proyek ini didesain otomatis (CI/CD) jika diselaraskan ke GitHub dan di-deploy ke server platform serbaguna seperti **Render.com**.
Pastikan setting lingkungan di lingkungan produksi memuat:
* `APP_ENV=production`
* `APP_DEBUG=false`
* `SESSION_DRIVER=cookie` (untuk performa ringan) atau `database`
* Menjalankan build Frontend untuk produksi: `npm run build`

---
*Dibuat untuk melengkapi tugas Sistem Terintegrasi & Rekayasa Perangkat Lunak 2026.*
