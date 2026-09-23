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

### PHASE 5: Quality Control & Defect Tracking
- **Tujuan:** Checklist inspeksi QC per kategori, pencatatan cacat (defect tracking: severity, rework, resolution), dan validasi syarat sebelum proses packing dan shipping.
- **Deliverables:**
  - Standard checklist template (9 kategori SDD di `config/qc.php`) diinisialisasi otomatis dengan status awal belum dinilai (`null`).
  - Siklus inspeksi immutable: sesi draft (`PENDING`) dapat diedit, sesi final (`PASSED`/`REWORK`/`FAILED`) terkunci permanen.
  - Re-inspeksi historis untuk verifikasi hasil rework tanpa menimpa rekaman lama.
  - Siklus hidup defek: `OPEN` -> `IN_REWORK` -> `RESOLVED` (wajib catatan resolusi) dan persetujuan konsesi `ACCEPTED` (otoritas eksklusif `OWNER`/`ADMIN` dengan justifikasi).
  - Media photo evidence tertaut ke `qc_inspection_id` dan `qc_defect_id` dengan default `INTERNAL`.
  - Gate otoritatif backend `QC -> PACKING` pada `OrderService::changeStatus`.
  - Sinkronisasi otomatis penyelesaian tahapan produksi Sequence 7 (`QC`) saat inspeksi `PASSED`.
  - 10 endpoint API di bawah `/api/v1` terlindungi Sanctum dan isolasi multi-tenant.
  - Automated tests lulus 100% (32 test QC baru, 123 tests total proyek).
- **Status:** Selesai dan terverifikasi.

---

### PHASE 6: Customer Progress Portal
- **Tujuan:** Portal publik tanpa login menggunakan secure high-entropy `public_token` (40-char CSPRNG string). Menampilkan identitas order, status pengerjaan, progres kalkulasi backend, spesifikasi teknis terkunci (LOCKED), timeline foto ber-visibilitas customer, status QC, dan tracking pengiriman.
- **Deliverables:**
  - Endpoint publik: `GET /api/v1/public/orders/{public_token}` dilindungi `throttle:60,1` tanpa autentikasi Sanctum.
  - Resource proyeksi publik terisolasi: `CustomerPortalOrderResource` dengan blacklist ketat (tanpa ID internal, tanpa data finansial, tanpa catatan internal, tanpa data user bengkel, tanpa public_token di body).
  - Proyeksi spesifikasi teknis murni versi terkunci (`LOCKED`), mengabaikan draft perubahan.
  - Proyeksi foto hanya ber-visibilitas `CUSTOMER`, mengisolasi foto cacat QC internal.
  - Status mutu QC ramah pelanggan (`PENDING`, `IN_PROGRESS`, `PASSED`) tanpa membocorkan daftar defek, keparahan, atau riwayat rework internal.
  - Proyeksi pengiriman bila data tersedia (`shipping = null` jika belum ada pengiriman).
  - Antarmuka web mobile-first React pada rute `/track/:publicToken` yang mendukung 7 status tampilan (Loading, Sukses, 404, Error Jaringan, Belum Produksi, Selesai, Pengiriman).
  - Automated tests lulus 100% (14 test feature publik baru mencakup seluruh aspek keamanan dan fungsionalitas).
- **Status:** Selesai dan terverifikasi.

---

### PHASE 7: WhatsApp Workflow
- **Tujuan:** Menghubungkan pesanan TATAMEBEL dengan kanal WhatsApp secara sederhana dan aman tanpa WhatsApp Business API resmi. Menggunakan alur manual dispatch: `Order → generate message → generate wa.me URL → admin manually sends`.
- **Deliverables:**
  - Utilitas normalisasi nomor telepon Indonesia `WhatsAppNumberNormalizer` (`08...` / `+62...` / `62...` ke format `628...`). Validasi strict format ponsel tanpa mutasi agresif.
  - Service terpisah `WhatsAppMessageService` untuk merakit pesan terstruktur, memetakan template berdasarkan `OrderStatus`, dan menyusun tautan `https://wa.me/{phone}?text={rawurlencode(message)}`.
  - Pemetaan template cerdas:
    - `ORDER_CREATED`: untuk status `DRAFT`, `QUOTATION`, `CONFIRMED`, `WAITING_DP`.
    - `IN_PRODUCTION`: untuk status `READY_FOR_PRODUCTION`, `IN_PRODUCTION`.
    - *Generic Progress Template*: untuk status `QC`, `PACKING`, `READY_TO_SHIP`, `SHIPPED` (mengabarkan progres tanpa klaim pesanan baru).
    - `COMPLETED`: untuk status `COMPLETED`.
    - `CANCELLED`: ditolak dengan validasi bisnis HTTP `422 Unprocessable Entity`.
  - Endpoint terproteksi: `GET /api/v1/orders/{id}/whatsapp` dengan otorisasi `OrderPolicy::shareWhatsApp` (`OWNER` dan `ADMIN` diizinkan; `PRODUCTION` dan `QC` ditolak HTTP 403; cross-tenant HTTP 404).
  - Keamanan data ketat: pesan dan respons JSON dilarang memuat catatan internal, detail cacat QC, ID internal, data finansial, atau `public_token` sebagai field JSON mandiri.
  - Pencatatan log audit: aktivitas `WHATSAPP_SHARE_GENERATED` dicatat ke `activity_logs` dengan metadata minimal `{"order_id": <id>, "channel": "whatsapp"}` tanpa menyimpan isi pesan atau nomor telepon.
  - Field `tracking_url` ditambahkan ke `OrderResource` authenticated admin.
  - Komponen antarmuka `OrderWhatsAppActions` dan halaman admin `OrderDetailPage` pada rute `/orders/:id` dengan aksi "Bagikan via WhatsApp" (`window.open`) dan "Salin Link Tracking" (clipboard copy).
  - Automated tests lulus 100% (7 unit test normalizer + 16 feature test WhatsApp share).
- **Status:** Selesai dan terverifikasi.

---

### PHASE 8: Testing, Hardening & Pilot Preparation
- **Tujuan:** End-to-end integration testing, validasi keamanan (sanitization, rate limiting, physical storage segregation), audit integritas multi-tenant, dan verifikasi kesiapan pilot workshop.
- **Deliverables:**
  - Database regression: verifikasi `migrate:fresh`, `migrate:rollback`, foreign key integrity, dan unique constraints tanpa error.
  - Auth & tenant isolation regression: uji coba lintas-tenant pada customer, order, spesifikasi, produksi, QC, dan media membuktikan isolasi data sempurna (HTTP 404 tanpa kebocoran data).
  - Production & progress calculation regression: formula backend terverifikasi matematis (`completed / total * 100`) tanpa ketergantungan kalkulasi pada client frontend.
  - Media physical security: berkas `INTERNAL` terbukti tersimpan fisik di disk private (`storage/app/private`) dan ditolak HTTP 403 saat diakses via URL publik tanpa signature.
  - Quality Control gate: pemenuhan seluruh 5 syarat gerbang transisi pesanan ke `PACKING` (inspeksi lulus, checklist lengkap, nol defek aktif, tahapan QC produksi selesai).
  - Customer progress portal: verifikasi proyeksi whitelist publik tanpa kebocoran public_token di response body, tanpa data finansial, dan tanpa catatan internal.
  - WhatsApp manual dispatch: verifikasi normalisasi nomor telepon Indonesia, pembentukan URL wa.me ber-encoding RFC 3986, dan pencatatan audit log minimal.
  - End-to-End Pilot Workflow Test: skenario uji komprehensif 22-langkah (`PilotOrderWorkflowTest`) mencakup seluruh alur dari autentikasi Owner hingga verifikasi portal publik dan pembersihan data.
  - Full automated regression test suite: 166 test cases (1.184 assertions) lulus 100% tanpa kegagalan.
  - Frontend code quality: oxlint linter 0 warning & 0 error, build produksi Vite sukses tanpa komplikasi.
- **Status:** Selesai dan terverifikasi.

---

### PHASE 9: Pilot Hardening *(VERIFIED)*
- **Tujuan:** Menutup temuan operasional dari Pilot Simulation Audit pada dua area kunci: Frontend Authentication UI & Reactive Session, serta Minimal Shipping Management & Order SHIPPED Gate.
- **Deliverables:**
  - **Frontend Authentication UI & Session:**
    - Service autentikasi `authService.js` (`login`, `logout`, `getMe`) berbasis Axios instance existing.
    - Session management reaktif global via `AuthContext.jsx` dan hook `useAuth.js` (`user`, `role`, `loading`, `isAuthenticated`, `canShareWhatsApp`).
    - Halaman login profesional bertema workshop mebel `LoginPage.jsx` pada rute publik `/login` dengan validasi aman tanpa kebocoran stack trace.
    - Mekanisme proteksi rute `ProtectedRoute.jsx` yang membatasi akses `/` dan `/orders/:id` untuk staf terautentikasi dan mengarahkan pengguna belum login ke `/login`.
    - Portal pelacakan pelanggan `/track/:publicToken` tetap strictly public tanpa intervensi autentikasi.
    - Penanganan HTTP 401 terpusat di `api.js` yang membersihkan sesi lokal dan mengarahkan ke `/login` tanpa loop redirect.
  - **Minimal Shipping Management:**
    - Service layer `ShippingService.php` berbasis tabel `shipping` existing (tanpa migrasi baru).
    - Status transitions terverifikasi: `PENDING -> READY/SHIPPED`, `READY -> SHIPPED`, `SHIPPED -> DELIVERED` (terminal).
    - Form Requests `StoreShippingRequest` dan `UpdateShippingRequest` dengan dukungan armada bengkel sendiri (`tracking_number` nullable).
    - Policy `ShippingPolicy` dengan isolasi multi-tenant (`belongsToSameWorkshop`) dan otorisasi peran (OWNER/ADMIN kelola, PRODUCTION/QC lihat).
    - RESTful Controller `ShippingController` (`GET`, `POST`, `PATCH /api/v1/orders/{orderId}/shipping`) dan `ShippingResource`.
    - Gerbang otoritatif `OrderService::changeStatus`: transisi `READY_TO_SHIP -> SHIPPED` mewajibkan rekaman shipping dengan `shipping_address` valid, serta otomatis menyinkronkan status pengiriman ke `SHIPPED` dan mencatat `shipped_at = now()`.
    - Integritas Customer Portal: proyeksi publik menampilkan kurir dan status pengiriman tanpa kebocoran ID internal atau catatan bengkel.
    - Automated tests: 13 feature test baru pada `ShippingManagementTest` (total suite: 179 passed, 1.263 assertions).
    - Postman collection diperbarui dengan folder `Shipping` (GET, POST, PATCH).
- **Status:** Selesai dan terverifikasi.

---

## Anti-Slop & Quality Principles
1. **No Fake Functionality:** Dilarang membuat dummy API atau frontend mock yang seolah-olah berfungsi namun tidak didukung backend riil.
2. **Backend as Source of Truth:** Seluruh aturan bisnis, kalkulasi persen progres, dan transisi status wajib ditegakkan di backend Laravel.
3. **Verified Testing Only:** Status pengujian hanya boleh dicatat `VERIFIED` bila command tes benar-benar telah dieksekusi dan menghasilkan exit code 0.
