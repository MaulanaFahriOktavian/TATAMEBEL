# TATAMEBEL — Sistem Manajemen Pesanan & Produksi Mebel

> *"Tetap jualan lewat WhatsApp. Kelola pesanan dan produksinya lewat TATAMEBEL."*

TATAMEBEL adalah sistem operasional untuk pengrajin dan workshop mebel yang mengubah pesanan berbasis WhatsApp menjadi workflow produksi yang terstruktur, terdokumentasi, dan dapat dibagikan kembali kepada pelanggan melalui satu link progress.

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
│   └── API_CONTRACT.md
│
└── README.md
```

---

## 2. Prasyarat Sistem
- **PHP:** >= 8.3 (Teruji pada PHP 8.4.24)
- **Composer:** >= 2.2 (Teruji pada Composer 2.9.4)
- **Node.js:** >= 20.x (Teruji pada Node.js 22.22.0)
- **MySQL:** >= 8.0 (Teruji pada MySQL 8.4.3)

---

## 3. Instalasi & Menjalankan Proyek

### Backend (Laravel 13)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```
Backend berjalan pada: `http://127.0.0.1:8000` (API Base: `http://127.0.0.1:8000/api/v1`)

### Frontend (React + Vite)
```bash
cd frontend
npm install
npm run dev
```
Frontend berjalan pada: `http://localhost:5173`

---

## 4. Pengujian
- **Backend Tests:** `cd backend && php artisan test`
- **Frontend Build:** `cd frontend && npm run build`
- **Health Check API:** `curl http://127.0.0.1:8000/api/v1/health`
