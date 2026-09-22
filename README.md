# TATAMEBEL — Sistem Manajemen Pesanan & Produksi Mebel

> *"Tetap jualan lewat WhatsApp. Kelola pesanan dan produksinya lewat TATAMEBEL."*

TATAMEBEL adalah sistem manajemen operasional untuk pengrajin dan workshop mebel yang merapikan proses pesanan berbasis WhatsApp menjadi alur produksi yang terstruktur, terpantau, dan dapat dibagikan progresnya kepada pelanggan melalui satu tautan publik.

---

## 1. Arsitektur Proyek

```
tatamebel/
├── backend/                  # REST API Laravel 13.x
│   ├── app/
│   │   ├── Enums/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   ├── Requests/
│   │   │   ├── Resources/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   ├── Policies/
│   │   ├── Services/
│   │   └── Support/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── postman/              # Postman Collections & Environments
│   │   ├── Tatamebel.postman_collection.json
│   │   └── Tatamebel.postman_environment.json
│   ├── routes/
│   └── tests/
│
├── frontend/                 # Single Page Application (React + Vite)
│   ├── src/
│   │   ├── components/
│   │   ├── features/
│   │   ├── hooks/
│   │   ├── pages/
│   │   ├── routes/
│   │   ├── services/
│   │   └── utils/
│   └── package.json
│
├── docs/                     # Spesifikasi & Dokumentasi Arsitektur
│   ├── SDD.md
│   ├── DEVELOPMENT_PLAN.md
│   ├── DATABASE_SCHEMA.md
│   ├── API_CONTRACT.md
│   └── QUALITY_CONTROL.md
│
└── README.md
```

---

## 2. Prasyarat Sistem

- **PHP:** >= 8.3 (Direkomendasikan PHP 8.4)
- **Composer:** >= 2.2
- **Node.js:** >= 20.x
- **MySQL:** >= 8.0
- **Ekstensi PHP:** `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` / `imagick`

---

## 3. Instalasi & Pengaturan Lingkungan

### A. Backend Setup (Laravel REST API)

1. Masuk ke direktori backend:
   ```bash
   cd backend
   ```

2. Pasang dependensi PHP:
   ```bash
   composer install
   ```

3. Konfigurasi file environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Pastikan pengaturan database pada `.env` telah sesuai:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tatamebel
   DB_USERNAME=root
   DB_PASSWORD=
   
   FRONTEND_URL=http://localhost:5173
   SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,127.0.0.1,127.0.0.1:8000,::1
   ```

4. Jalankan migrasi dan seeder awal:
   ```bash
   php artisan migrate:fresh --seed
   ```

5. Hubungkan storage publik untuk media bukti yang dapat dilihat pelanggan:
   ```bash
   php artisan storage:link
   ```

6. Jalankan backend development server:
   ```bash
   php artisan serve
   ```
   Backend aktif pada: `http://127.0.0.1:8000` (API Base: `http://127.0.0.1:8000/api/v1`)

---

### B. Frontend Setup (React + Vite)

1. Masuk ke direktori frontend:
   ```bash
   cd frontend
   ```

2. Pasang dependensi Node.js:
   ```bash
   npm install
   ```

3. Konfigurasi file environment (opsional jika menggunakan default):
   ```bash
   cp .env.example .env
   ```
   Konfigurasi default mengarah ke:
   ```env
   VITE_API_BASE_URL=http://127.0.0.1:8000/api/v1
   ```

4. Jalankan development server:
   ```bash
   npm run dev
   ```
   Frontend aktif pada: `http://localhost:5173`

---

## 4. Akun Pengembang Lokal (Local Seeded Accounts)

Setelah menjalankan `php artisan db:seed` (atau `migrate:fresh --seed`), akun-akun pondasi workshop berikut tersedia untuk pengujian lokal:

- **Workshop:** `Workshop Kayu Lestari` (Slug: `workshop-kayu-lestari`)
- **Password default seluruh akun:** `password`

| Role | Nama Pengguna | Email | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **OWNER** | Pak Bambang (Owner) | `owner@kayulestari.com` | Akses penuh, manajemen keuangan, WhatsApp share, kontrol produksi |
| **ADMIN** | Siti Aminah (Admin) | `admin@kayulestari.com` | Input pesanan, pelanggan, WhatsApp share, koordinasi jadwal |
| **PRODUCTION** | Joko Santoso (Kepala Produksi) | `produksi@kayulestari.com` | Update tahapan produksi, upload bukti pengerjaan teknis |
| **QC** | Budi Setiawan (Inspektur QC) | `qc@kayulestari.com` | Inspeksi QC, evaluasi checklist, catat defect & verifikasi rework |

---

## 5. Pengujian & Kualitas Kode

- **Backend Test Suite (PHPUnit):**
  ```bash
  cd backend
  php artisan test
  ```
- **Frontend Code Linter:**
  ```bash
  cd frontend
  npm run lint
  ```
- **Frontend Production Build:**
  ```bash
  cd frontend
  npm run build
  ```
- **API Health Check:**
  ```bash
  curl http://127.0.0.1:8000/api/v1/health
  ```

---

## 6. Integrasi API & Pengujian Postman

Koleksi dan environment Postman telah disediakan di folder `backend/postman/`:

- **Koleksi:** `backend/postman/Tatamebel.postman_collection.json`
- **Environment:** `backend/postman/Tatamebel.postman_environment.json`

### Variabel Environment Postman:
- `base_url`: `http://127.0.0.1:8000/api/v1`
- `token`: Bearer token (terisi otomatis saat request *Login* berhasil dieksekusi)
- `order_id`: Terisi otomatis saat request *Create Order* berhasil dieksekusi
- `public_token`: Terisi otomatis saat request *Create Order* berhasil dieksekusi
- `qc_inspection_id`: Terisi otomatis saat request *Create QC Inspection* berhasil dieksekusi
- `defect_id`: Terisi otomatis saat request *Log QC Defect* berhasil dieksekusi

---

## 7. Batasan Fungsional & Status Pilot (Pilot Preparation Boundaries)

Sistem saat ini berada pada tahap **Pilot Preparation (Fase 8 Selesai)**. Fitur dan batasan operasional yang berlaku adalah sebagai berikut:

1. **WhatsApp Workflow (Manual Share):**
   - TATAMEBEL menyediakan generator pesan terformat beserta tautan `https://wa.me/...`.
   - Admin/Owner membuka tautan dan mengirimkan pesan secara manual melalui aplikasi WhatsApp web/desktop.
   - **Belum menggunakan WhatsApp Business Cloud API otomatis.**

2. **Akses Pelanggan (Customer Portal):**
   - Pelanggan **tidak memiliki akun pengguna** dan tidak login via Sanctum.
   - Pelanggan memantau pesanan melalui URL publik berbasis token unik: `/track/{public_token}`.
   - Portal publik hanya menampilkan nama pelanggan, spesifikasi akhir yang disetujui (LOCKED), tahapan produksi aktif, foto bertanda CUSTOMER, status QC publik, dan status ekspedisi. Informasi finansial dan catatan internal disembunyikan secara ketat.

3. **Keuangan & Pembayaran:**
   - Pencatatan pembayaran bersifat administratif/manual di workshop.
   - **Belum terintegrasi dengan Payment Gateway otomatis.**

4. **Persediaan & Rantai Pasok:**
   - Modul *Inventory / Stok Bahan Baku*, *Supplier*, dan *Bill of Materials (BOM)* **belum termasuk** dalam cakupan pilot saat ini.
