# TATAMEBEL — Multi-Phase Development Plan

## Phase Overview & Gated Progression
Pengembangan TATAMEBEL dibagi ke dalam 9 fase berurutan (Phase 0 hingga Phase 8). Setiap fase wajib diverifikasi melalui testing riil, lolos review arsitektur, dan mendapatkan approval eksplisit sebelum melangkah ke fase berikutnya.

---

### PHASE 0: Project Foundation *(Current Phase)*
- **Tujuan:** Menyiapkan fondasi backend (Laravel 13), frontend (React + Vite), database (MySQL), endpoint kesehatan (`/api/v1/health`), CORS, Postman setup, dokumentasi arsitektur, dan automated test fondasi.
- **Kriteria Lolos:**
  - Laravel 13 terverifikasi (`php artisan --version`).
  - PHP 8.4+ kompatibel.
  - MySQL database terkonfigurasi.
  - Endpoint `GET /api/v1/health` mengembalikan format standard response envelope.
  - Postman collection & environment tersimpan di `backend/postman/`.
  - Frontend React + Vite sukses build (`npm run build`).
  - Automated test lulus (`php artisan test`).
  - Tidak ada business feature atau fake mock data.

---

### PHASE 1: Database Core
- **Tujuan:** Mengimplementasikan 16 tabel inti, migrasi berurutan, model Eloquent, Enums, relasi foreign key, indexes, cascading rules, model factories, dan database seeders.
- **Urutan Migrasi:**
  1. `workshops`
  2. `users`
  3. `customers`
  4. `orders`
  5. `order_items`
  6. `specifications`
  7. `change_requests`
  8. `production_stages`
  9. `production_updates`
  10. `media`
  11. `qc_inspections`
  12. `qc_items`
  13. `qc_defects`
  14. `payments`
  15. `shipping`
  16. `activity_logs`
- **Kriteria Lolos:** Seluruh migrasi sukses dijalankan, rollback bersih, dan test model relationship lulus.

---

### PHASE 2: Authentication & Tenant Isolation
- **Tujuan:** Otentikasi Laravel Sanctum, role-based authorization (OWNER, ADMIN, PRODUCTION, QC), dan penegakan isolasi tenant multi-tenant (`workshop_id`) di query level dan policy level.

---

### PHASE 3: Customer & Order Management
- **Tujuan:** Pengelolaan data pelanggan, pembuatan pesanan (Order), Order Items, Spesifikasi Mebel ber-versi, Change Request approval flow, dan Order State Machine validation.

---

### PHASE 4: Production Tracking
- **Tujuan:** Pembuatan template tahapan produksi per pesanan, update progres pengerjaan, upload foto bukti pengerjaan (photo evidence internal/customer), dan authoritative progress calculation service.

---

### PHASE 5: Quality Control
- **Tujuan:** Checklist inspeksi QC per kategori, pencatatan cacat (defect tracking: severity, rework, resolution), dan validasi syarat sebelum proses packing dan shipping.

---

### PHASE 6: Customer Progress Portal
- **Tujuan:** Portal publik tanpa login menggunakan secure high-entropy `public_token`. Menampilkan identitas order, status pengerjaan, progres kalkulasi, timeline foto ber-visibilitas customer, status QC, dan tracking pengiriman.

---

### PHASE 7: WhatsApp Workflow
- **Tujuan:** Integrasi pesan siap kirim (generated message) dengan link customer portal, tombol interaktif "Kirim WhatsApp", dan pencatatan activity log.

---

### PHASE 8: Testing, Hardening & Pilot Preparation
- **Tujuan:** End-to-end integration testing, validasi keamanan (sanitization, rate limiting, token rotation), optimalisasi query index, audit kesesuaian sistem operasional mebel nyata, dan persiapan pilot workshop.

---

## Anti-Slop & Quality Principles
1. **No Fake Functionality:** Dilarang membuat dummy API atau frontend mock yang seolah-olah berfungsi namun tidak didukung backend riil.
2. **Backend as Source of Truth:** Seluruh aturan bisnis, kalkulasi persen progres, dan transisi status wajib ditegakkan di backend Laravel.
3. **Verified Testing Only:** Status pengujian hanya boleh dicatat `VERIFIED` bila command tes benar-benar telah dieksekusi dan menghasilkan exit code 0.
