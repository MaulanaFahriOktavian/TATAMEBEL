# TATAMEBEL — Furnitur Modern & Arsitektural Kustom

Sistem etalase furnitur modern & workshop kustom TATAMEBEL dengan backend Laravel REST API dan frontend modern Single Page Application (React + Vite + Tailwind CSS).

---

## 1. Arsitektur Proyek

```
TATAMEBEL/
├── backend/                       # REST API (Laravel 12/13)
│   ├── app/
│   │   ├── Http/Controllers/Api/  # Product, Category, Cart, Inquiry Controllers
│   │   └── Models/                # Product, Category, CartItem, CustomInquiry
│   ├── database/
│   │   ├── migrations/            # E-commerce & Workshop schema migrations
│   │   └── seeders/               # EcommerceSeeder & Workshop seeders
│   ├── routes/
│   │   └── api.php                # API v1 routes
│   └── tests/
│       └── Feature/Ecommerce/     # PHPUnit Automated API Tests
│
├── frontend/                      # React SPA (Vite + Tailwind CSS v3)
│   ├── src/
│   │   ├── components/ecommerce/  # CartDrawer, ProductDetailModal, SearchModal, OrderTrackingModal
│   │   ├── pages/landing/         # Modern Atelier & Architectural Landing Page
│   │   ├── services/              # Axios & Fetch API clients
│   │   └── index.css              # Custom design tokens, charcoal pattern & scrollbars
│   ├── tailwind.config.js         # Curated architectural palette & font typography
│   └── package.json
│
└── README.md
```

---

## 2. Fitur Utama

1. **Etalase Furnitur Modern & Portofolio Arsitektural:**
   - Katalog produk dinamis berbasis database (Kayu Jati Grade A, Ash Solid, Boucle, Cane Rotan).
   - Filter tab kategori instan (*Semua, Kursi, Meja, Penyimpanan, Sofa Santai*).
   - Modal detail produk interaktif dengan opsi finishing dan spesifikasi teknis.
   - Pencarian instan furnitur berdasarkan nama, jenis kayu, dan deskripsi.

2. **Keranjang Belanja (Cart) & Konsultasi WhatsApp:**
   - Keranjang belanja real-time berbasis sesi (`X-Cart-Session`).
   - Penambahan produk langsung ke keranjang dengan badge dinamis pada header.
   - Drawer keranjang interaktif dengan kalkulasi total otomatis.
   - Tombol checkout otomatis yang menyusun format pesan konsultasi pesanan ke WhatsApp.

3. **Formulir Pengajuan Proyek Kustom & Arsitektur:**
   - Konsultasi proyek untuk hunian residensial, hospitality, kantor arsitektur, atau furnitur kustom satuan.
   - Validasi data otomatis dan penyimpanan ke database `custom_inquiries`.
   - Notifikasi dan feedback langsung di antarmuka.

4. **Lacak Pesanan (Order Tracking):**
   - Modal pelacakan publik menggunakan nomor pesanan atau token publik untuk memantau status produksi secara langsung.

5. **Akses Internal Staf & Workshop:**
   - Rute staf `/login` dan manajemen pesanan internal tetap tersedia untuk operasional workshop.

---

## 3. Endpoints REST API (Laravel)

Semua endpoint publik tersedia di bawah prefix `/api/v1/`:

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/v1/products` | Mendapatkan daftar produk aktif (filter kategori & pencarian) |
| `GET` | `/api/v1/products/{idOrSlug}` | Mendapatkan detail produk beserta varian dan gambar |
| `GET` | `/api/v1/categories` | Mendapatkan daftar kategori furnitur |
| `GET` | `/api/v1/categories/{slug}` | Mendapatkan detail kategori beserta produk di dalamnya |
| `GET` | `/api/v1/cart` | Mengambil data item keranjang untuk sesi aktif |
| `POST` | `/api/v1/cart` | Menambahkan produk ke keranjang belanja |
| `PATCH` | `/api/v1/cart/{itemId}` | Mengubah kuantitas item keranjang |
| `DELETE` | `/api/v1/cart/{itemId}` | Menghapus item dari keranjang |
| `DELETE` | `/api/v1/cart` | Mengosongkan seluruh keranjang belanja |
| `POST` | `/api/v1/inquiries` | Mengirim pengajuan konsultasi proyek mebel kustom |
| `GET` | `/api/v1/tracking/{token}` | Memeriksa status pesanan secara publik |

---

## 4. Cara Menjalankan

### Backend (Laravel)
```bash
cd backend
composer install
php artisan migrate
php artisan db:seed --class=EcommerceSeeder
php artisan serve
```
Backend berjalan pada: `http://127.0.0.1:8000`

### Frontend (React + Vite)
```bash
cd frontend
npm install
npm run dev
```
Frontend berjalan pada: `http://localhost:5173`

### Menjalankan Pengujian Otomatis
```bash
# Pengujian Backend (PHPUnit)
cd backend
php vendor/phpunit/phpunit/phpunit tests/Feature/Ecommerce/EcommerceApiTest.php

# Pengujian Frontend Lint & Build
cd frontend
npm run lint
npm run build
```
