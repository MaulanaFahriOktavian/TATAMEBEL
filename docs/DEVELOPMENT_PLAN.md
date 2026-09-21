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

### PHASE 3: Customer & Order Management *(VERIFIED)*
- **Tujuan:** Pengelolaan data pelanggan, pembuatan pesanan (Order), Order Items, kalkulasi otomatis subtotal/total, penomoran pesanan unik bulanan (ORD-YYYYMM-XXXX), token publik acak, 12-state order state machine, pencegahan kebocoran lintas tenant, dan activity audit log.
- **Implementasi:**
  - Customer CRUD (`GET /customers`, `POST /customers`, `GET /customers/{id}`, `PATCH /customers/{id}`, `DELETE /customers/{id}`).
  - Order Management (`GET /orders`, `POST /orders` dengan minimal 1 item, `GET /orders/{id}`, `PATCH /orders/{id}/status`).
  - State machine transisi pesanan terverifikasi ketat (termasuk direct DRAFT -> CONFIRMED dan pembatasan CANCELLED).
  - Service layer (`CustomerService`, `OrderService`, `ActivityLogService`).
  - Form Requests & API Resources (`CustomerResource`, `OrderResource`, `OrderItemResource`).
  - Policy & Role authorization (`OWNER`, `ADMIN`, `PRODUCTION`, `QC`).
  - Automated tests lulus 100% (61 tests, 464 assertions).
- **Status:** Selesai dan terverifikasi.

---

### PHASE 4: Specification & Production Tracking *(VERIFIED)*
- **Tujuan:** Manajemen spesifikasi teknis produk, versioning spesifikasi immutable, tata kelola Change Request terstruktur, tracking tahapan produksi (8 default stages), authoritative backend progress calculation, dan pengelolaan media/bukti foto pengerjaan (internal & customer visibility).
- **Implementasi:**
  - `POST /api/v1/orders/{orderId}/items/{itemId}/specifications` (Create DRAFT v1 spec).
  - `GET /api/v1/orders/{orderId}/items/{itemId}/specifications` (List version history).
  - `GET /api/v1/orders/{orderId}/items/{itemId}/specifications/current` (Resolve highest LOCKED version).
  - `PATCH /api/v1/specifications/{id}` (Update draft spec, rejected if locked).
  - `POST /api/v1/specifications/{id}/lock` (Lock specification).
  - `POST /api/v1/orders/{orderId}/change-requests` (Submit change request with structured `requested_changes` JSON).
  - `POST /api/v1/change-requests/{id}/approve` (Spawn new specification version `v+1` in `DRAFT` status).
  - `POST /api/v1/change-requests/{id}/reject` (Reject with required `review_note`).
  - `GET /api/v1/orders/{orderId}/production` (Authoritative progress calculation and overview).
  - `POST /api/v1/orders/{orderId}/production/init-stages` (Instantiate 8 default stages per order).
  - `PATCH /api/v1/production-stages/{id}` (Update stage status, QC stage protected).
  - `POST /api/v1/production-stages/{id}/updates` (Create update with progress snapshot and media).
  - `POST /api/v1/orders/{orderId}/media` & `DELETE /api/v1/media/{id}` (Media photo evidence).
  - Order state machine integration: `READY_FOR_PRODUCTION` requires all items to have `LOCKED` specifications.
  - Automated tests lulus 100% (91 tests, 685 assertions).
- **Status:** Selesai dan terverifikasi.

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
