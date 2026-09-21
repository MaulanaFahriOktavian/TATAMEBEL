# TATAMEBEL — Multi-Phase Development Plan

## Phase Overview & Gated Progression
Pengembangan TATAMEBEL dibagi ke dalam 9 fase berurutan (Phase 0 hingga Phase 8). Setiap fase wajib diverifikasi melalui testing riil, lolos review arsitektur, dan mendapatkan approval eksplisit sebelum melangkah ke fase berikutnya.

---

### PHASE 0: Project Foundation *(VERIFIED & COMMITTED)*
- **Tujuan:** Menyiapkan fondasi backend (Laravel 13), frontend (React + Vite), database (MySQL), endpoint kesehatan (`/api/v1/health`), CORS, Postman setup, dokumentasi arsitektur, dan automated test fondasi.
- **Status:** Selesai dan terverifikasi.

---

### PHASE 1: Database Core *(VERIFIED & COMMITTED)*
- **Tujuan:** Mengimplementasikan 16 tabel inti, migrasi berurutan, model Eloquent, Enums, relasi foreign key, indexes, cascading rules, model factories, dan database seeders.
- **Status:** Selesai dan terverifikasi (22 tests, 132 assertions passed).

---

### PHASE 2: Authentication & Tenant Isolation *(VERIFIED)*
- **Tujuan:** Otentikasi Laravel Sanctum, role-based authorization (OWNER, ADMIN, PRODUCTION, QC), dan penegakan isolasi tenant multi-tenant (`workshop_id`) di query level dan policy level.
- **Implementasi:**
  - `POST /api/v1/auth/login` (Sanctum bearer token, anti-enumeration, active user check).
  - `POST /api/v1/auth/logout` (revokasi current token).
  - `GET /api/v1/auth/me` (profil, role, dan workshop context).
  - Middleware `EnsureWorkshopContext` (request-scoped tenant enforcement).
  - Trait `EnforcesWorkshopTenancy` (policy foundation pencegah cross-tenant leak).
  - Role capabilities `hasRole()` & `hasAnyRole()`.
  - Feature & security automated tests lulus 100% (39 tests, 219 assertions).
- **Status:** Selesai dan terverifikasi.

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
