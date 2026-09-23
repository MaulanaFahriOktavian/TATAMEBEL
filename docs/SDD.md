# TATAMEBEL — Software Design Document (SDD)

## 1. Executive Summary & Identity
- **Nama Resmi:** TATAMEBEL
- **Nama Lengkap:** TATAMEBEL — Sistem Manajemen Pesanan & Produksi Mebel
- **Positioning:** *"Tetap jualan lewat WhatsApp. Kelola pesanan dan produksinya lewat TATAMEBEL."*
- **Tujuan Sistem:** Mengubah pesanan berbasis WhatsApp dari pengrajin mebel custom dan workshop furniture menjadi workflow produksi yang terstruktur, akuntabel, dan transparan melalui satu link progress publik yang aman untuk pelanggan.
- **Batasan Produk:** TATAMEBEL bukan marketplace, e-commerce, ERP raksasa, inventory management penuh, accounting software, generic CRM, atau bot AI furniture generator.

## 2. System Architecture
```
+-----------------------------------------------------------+
|              Customer (via WhatsApp Link)                 |
+-----------------------------------------------------------+
                              | (Public Token)
                              v
+-----------------------------------------------------------+
|                     React Frontend                        |
|  - Workshop Admin / Staff Portal (Sanctum Auth)           |
|  - Customer Progress Portal (Public Token Only)           |
+-----------------------------------------------------------+
                              | (HTTP REST API /api/v1)
                              v
+-----------------------------------------------------------+
|                   Laravel 13 REST API                     |
|  - Service Layer Architecture                             |
|  - Tenant-isolation (`workshop_id`) via Policies/Scopes   |
|  - Order State Machine Validator                          |
|  - Production Calculation Service                         |
+-----------------------------------------------------------+
                              |
               +--------------+--------------+
               |                             |
               v                             v
      +-----------------+           +-----------------+
      |  MySQL Database |           | Storage (Local/ |
      | (Multi-Tenant)  |           | S3 Abstraction) |
      +-----------------+           +-----------------+
```

## 3. Multi-Tenancy & Tenant Isolation
- **Tenant Scope:** Setiap entitas operasional terisolasi per workshop melalui foreign key `workshop_id`.
- **Backend Enforcement:**
  - Tenant context ditegakkan secara request-scoped oleh middleware `EnsureWorkshopContext` (`workshop.context`).
  - Tidak ada static mutable global state (`Tenant::$current`).
  - Tenant boundary query wajib diverifikasi di backend melalui user workshop scope: `$user->workshop->orders()->whereKey($id)->firstOrFail()`.
  - Trait `EnforcesWorkshopTenancy` (`belongsToSameWorkshop()`) menjamin otorisasi policy selalu memvalidasi kesamaan `workshop_id`.
  - User workshop A dilarang keras membaca, mengubah, atau menghapus data workshop B. Mengetahui ID integer resource tenant lain tidak akan pernah membuka akses (zero data leakage).
  - Frontend visibility bukan mekanisme keamanan; otorisasi mutlak ditegakkan di backend Laravel.

## 4. User Roles & Authorization Matrix
Enum `UserRole` (`OWNER`, `ADMIN`, `PRODUCTION`, `QC`) terintegrasi pada model `User`:
- Helper methods backend: `hasRole(UserRole $role)` dan `hasAnyRole(array $roles)`.
- **OWNER:** Akses penuh ke seluruh workshop setting, manajemen user, keuangan (pencatatan pembayaran), customer CRUD, order CRUD, produksi, dan QC.
- **ADMIN:** Manajemen pelanggan (CRUD), pemrosesan order (CRUD), approval spesifikasi, koordinasi shipping.
- **PRODUCTION:** Read-only pada Customer dan Order. Pembaruan tahapan produksi (stage progress), upload foto bukti pengerjaan (photo evidence). Mutasi customer/order ditolak (403).
- **QC:** Read-only pada Customer dan Order. Inspeksi mutu (QC checklist), pencatatan cacat (defect tracking), persetujuan rework. Mutasi customer/order ditolak (403).
- **Penting:** Role capability tidak pernah mengabaikan atau melompati batas tenant isolation. Akses lintas workshop tetap ditolak walaupun berstatus OWNER/ADMIN.

## 5. Order State Machine
Order memiliki 12 state yang transisinya divalidasi ketat oleh `OrderService`:
```
DRAFT -> QUOTATION -> CONFIRMED -> WAITING_DP -> READY_FOR_PRODUCTION 
      -> IN_PRODUCTION -> QC -> PACKING -> READY_TO_SHIP -> SHIPPED -> COMPLETED
```
- **Jalan Pintas Cepat:** Transisi langsung `DRAFT -> CONFIRMED` diperbolehkan untuk memfasilitasi transaksi langsung via WhatsApp.
- **Pembatasan Pembatalan (`CANCELLED`):** Hanya diperbolehkan dari status sebelum pengerjaan selesai:
  `DRAFT`, `QUOTATION`, `CONFIRMED`, `WAITING_DP`, `READY_FOR_PRODUCTION`, dan `IN_PRODUCTION`.
- **Dilarang Batal:** Status `QC`, `PACKING`, `READY_TO_SHIP`, `SHIPPED`, `COMPLETED`, dan `CANCELLED` tidak dapat dibatalkan.
- **Transisi Ilegal:** Ditolak dengan HTTP 422 Unprocessable Entity.
- **Penomoran Pesanan:** `ORD-YYYYMM-XXXX` berurutan per workshop per bulan dengan penguncian eksklusif baris workshop (`lockForUpdate`).
- **Kalkulasi Nilai:** `subtotal = qty * unit_price`, `total_amount = sum(subtotals)`. Dihitung otoritatif oleh backend secara atomik dalam database transaction.
- **Gerbang Pengiriman (`READY_TO_SHIP -> SHIPPED`):** Transisi pesanan ke status `SHIPPED` mewajibkan ketersediaan data pengiriman (`shipping`) dengan alamat pengiriman (`shipping_address`) yang terisi. Saat transisi berhasil, backend secara otomatis menyinkronkan status pengiriman ke `SHIPPED` dan mencatat waktu `shipped_at = now()` jika belum terisi. Transisi `PACKING -> READY_TO_SHIP` tidak mewajibkan data pengiriman terlebih dahulu. Transisi `SHIPPED -> COMPLETED` tidak mewajibkan status pengiriman `DELIVERED`, mengakomodasi serah terima langsung atau pengambilan mandiri pelanggan tanpa ketergantungan konfirmasi kurir pihak ketiga.

## 6. Specification Versioning & Change Requests
- Spesifikasi mebel diawali dengan status `DRAFT` (versi 1).
- Begitu disetujui, spesifikasi di-`LOCKED`. Spesifikasi yang berstatus `LOCKED` tidak boleh di-edit secara destruktif (HTTP 422).
- **Spesifikasi Operasional Aktif (Current Specification):** Didefinisikan secara ketat sebagai **versi tertinggi yang berstatus LOCKED** (`status = LOCKED`). Versi dalam status `DRAFT` tidak dianggap sebagai blueprint operasional sampai dikunci.
- **Change Request:** Perubahan spesifikasi yang sudah `LOCKED` wajib melalui alur `Change Request` (`PENDING -> APPROVED / REJECTED / CANCELLED`).
  - Menggunakan payload terstruktur `requested_changes` JSON yang memuat field teknis spesifik yang diminta berubah.
  - Jika disetujui (`APPROVED`), backend secara deterministik membuat spesifikasi versi baru (`version = version + 1`) dalam status `DRAFT` untuk memungkinkan penyesuaian detail teknis sebelum dikunci kembali.
  - Versi lama tetap tersimpan sebagai bukti audit historis yang tidak boleh diubah atau dihapus.
- **Gerbang Status Pesanan:** Pesanan hanya dapat berpindah ke status `READY_FOR_PRODUCTION` jika seluruh item pesanan telah memiliki spesifikasi yang berstatus `LOCKED`.

## 7. Production Stage & Authoritative Progress Calculation
- **Default Production Stages (8 Tahapan):**
  1. Material Preparation
  2. Cutting
  3. Assembly
  4. Sanding
  5. Finishing
  6. Final Assembly
  7. QC (Quality Control)
  8. Packing
- **Template Configuration:** 8 tahapan standar dikonfigurasi melalui konfigurasi aplikasi (`config/production.php`) dan diinstansiasi ke pesanan (`production_stages.order_id`). Tahapan tidak di-seed sebagai global database records.
- **QC Stage Handoff:** Tahapan QC tidak dapat diselesaikan (`COMPLETED`) melalui production tracking biasa oleh tim produksi. Tahapan QC merupakan handoff gate untuk modul QC Inspection (Phase 5).
- **Authoritative Progress Calculation:**
  Progress produksi dihitung secara otoritatif oleh backend:
  $$\text{Progress (\%)} = \frac{\text{Jumlah Tahapan Aktif Selesai}}{\text{Total Tahapan Aktif}} \times 100$$
- Jika tidak ada tahapan aktif: progress = 0%.
- Frontend dilarang keras menghitung sendiri persentase progres. Progres dicatat sebagai telemetry snapshot pada setiap `production_updates`.

## 8. Quality Control & Defect Tracking
- **Checklist Template (9 Kategori SDD):** Dikonfigurasi di `config/qc.php`: `dimension`, `material`, `construction`, `surface`, `finishing`, `color`, `quantity`, `accessories`, `packaging`.
- **Status Awal Butir Checklist:** Butir checklist pada inspeksi baru berstatus `null` (belum dinilai). Finalisasi `PASSED` mewajibkan seluruh butir telah dievaluasi (`PASS` atau `NA`).
- **Status Sesi Inspeksi:** `PENDING` (draft/dapat diedit), `PASSED` (lulus sempurna), `REWORK` (memerlukan perbaikan tukang), `FAILED` (gagal fatal non-rework).
- **Immutability & Re-inspeksi:** Sesi inspeksi yang telah difinalisasi (`PASSED`, `REWORK`, `FAILED`) bersifat **permanen dan immutable (terkunci)** termasuk berkas media yang tertaut (penambahan media baru ditolak HTTP 422). Perbaikan fisik diverifikasi melalui sesi inspeksi baru (*re-inspection*), mempertahankan rekam jejak audit kualitas lengkap.
- **Alur Penanganan Inspeksi Gagal (FAILED Workflow):** Jika inspeksi berstatus `FAILED`, pesanan tetap berada pada status `QC` dan tidak dapat masuk ke `PACKING`. Workshop melakukan evaluasi teknis/remake dan memverifikasi kelulusan melalui re-inspeksi baru tanpa menambahkan status order baru atau memundurkan ke `IN_PRODUCTION`.
- **Defect Severity:** `LOW`, `MEDIUM`, `HIGH`, `CRITICAL`.
- **Defect Lifecycle:**
  - `OPEN`: Cacat ditemukan dan dicatat pada sesi inspeksi draft.
  - `IN_REWORK`: Tukang mengambil alih pengerjaan perbaikan fisik.
  - `RESOLVED`: Perbaikan fisik selesai dengan kewajiban mengisi catatan tindakan korektif (`resolution`).
  - `ACCEPTED`: Konsesi/waiver toleransi kualitas (misal corak serat alami kayu) yang **hanya dapat disetujui oleh OWNER atau ADMIN** dengan catatan justifikasi wajib. Bersifat final dan tidak dapat diubah kembali ke `OPEN`, `IN_REWORK`, atau `RESOLVED`.
- **QC → PACKING Gate (Otoritatif Backend):** Transisi pesanan ke `PACKING` mensyaratkan:
  1. Terdapat minimal 1 catatan inspeksi QC.
  2. Inspeksi terbaru berstatus `PASSED`.
  3. Seluruh butir checklist pada inspeksi lulus bernilai `PASS` atau `NA`.
  4. Nol defek aktif (seluruh defek pada pesanan wajib berstatus `RESOLVED` atau `ACCEPTED`).
  5. Tahapan produksi Sequence 7 (`QC`) berstatus `COMPLETED`.
- **Sinkronisasi Otomatis Tahapan Produksi:** Saat sesi inspeksi QC dibuat, tahapan Sequence 7 (`QC`) yang masih `PENDING` otomatis berubah ke `IN_PROGRESS`. Finalisasi inspeksi sebagai `PASSED` secara otomatis menyelesaikan tahapan produksi `QC` (`completed_at = now()`) melalui boundary service `ProductionService::completeQcStageFromInspection()`. Status pesanan tetap berada pada `QC` selama proses rework tanpa pemunduran ke `IN_PRODUCTION`.
- **Status Pembayaran (Payment Scope):** Payment tracking belum diimplementasikan dan akan ditangani pada fase berikutnya sesuai Development Plan.

## 9. Media & Visibility
- File foto evidence disimpan melalui Laravel Storage abstraction.
- Visibility: `INTERNAL` (hanya staf workshop) dan `CUSTOMER` (dapat dilihat di portal publik).

## 10. Customer Portal Architecture & Security
- **Akses Tanpa Login:** Pelanggan mengakses portal publik melalui secure, non-incremental, high-entropy `public_token` (40-karakter alfanumerik acak berbasis CSPRNG) pada URL `/track/{public_token}`. Tidak memerlukan akun Sanctum.
- **Tenant Isolation:** Lookup order murni berbasis `orders.public_token` (indeks B-tree unik). Seluruh relasi child di-query secara ketat melalui instance order tersebut tanpa inferensi tenant dari client.
- **Public Projection Resource (`CustomerPortalOrderResource`):**
  - **Whitelist Data Publik:** Identitas pesanan (`order_number`, `title`, `status`, `status_label`, `created_at`, `confirmed_at`), nama pemesan (`customer_name`), identitas workshop (`name`, `phone`, `address`), item mebel beserta spesifikasi teknis terkunci (`LOCKED` specs: dimensi, kayu, finishing, warna, jok, permintaan khusus), progres tahapan aktif kalkulasi backend (`progress_percentage`), galeri foto ber-visibilitas `CUSTOMER`, status mutu QC umum, dan status pengiriman (bila ada).
  - **Strict Blacklist (Dilarang Diekspos):** Seluruh ID internal database (`order.id`, `workshop_id`, `customer_id`, dll.), data finansial (`total_amount`, `unit_price`, `subtotal`, pembayaran), `public_token` dalam response body, catatan internal bengkel (`notes`, `production_note`, `customer.notes`), user/pekerja internal, activity/audit logs, detail cacat QC (`qc_defects`, severity, rework history), dan foto ber-visibilitas `INTERNAL`.
- **Media Visibility:** Hanya berkas media dengan `visibility = CUSTOMER` yang dimasukkan dalam proyeksi `photos`. Media bukti cacat QC internal ber-visibilitas `INTERNAL` terisolasi secara mutlak dari respons publik.
- **QC Visibility:** Menampilkan label status umum dan menenangkan: `PENDING` ("Menunggu Pengecekan Kualitas"), `IN_PROGRESS` ("Sedang dalam Pengecekan Kualitas"), dan `PASSED` ("Lolos Pengecekan Kualitas" beserta tanggal verifikasi). Detail cacat dan proses perbaikan bengkel tidak pernah dibocorkan.
- **Proteksi Rate Limiting & Anti-Enumeration:** Endpoint publik dilindungi oleh middleware `throttle:60,1`. Token yang tidak valid menghasilkan HTTP `404 Not Found` generik yang seragam.
- **Frontend Mobile-First:** Rute React `/track/:publicToken` dirancang mobile-first (optimal 360–430px) untuk pelanggan WhatsApp, dengan estetika bersih bertema kayu mebel hangat dan penanganan 7 state antarmuka yang andal.

## 11. WhatsApp Workflow & Manual Dispatch Architecture (Phase 7)
- **Filosofi Integrasi:** Menghubungkan Order TATAMEBEL dengan kanal WhatsApp secara sederhana dan aman tanpa ketergantungan API pihak ketiga: `Order → generate message → generate wa.me URL → admin manually sends`.
- **Scope Boundary (Tegas):**
  - **Diimplementasikan:** Pembuatan pesan terstruktur berbasis template, normalisasi nomor telepon pelanggan, pembentukan tautan `https://wa.me/{phone}?text={encoded}`, pencatatan audit log, dan tombol aksi pada antarmuka Order Detail admin.
  - **Tidak Diimplementasikan (Di Luar Scope Phase 7):** WhatsApp Business API resmi, pengiriman otomatis (*automatic sending*), webhook penerimaan pesan, chatbot AI/otomatisasi balasan, pesan siaran (*broadcast*), OTP, dan sinkronisasi obrolan pelanggan.
- **Normalisasi Nomor Telepon (`WhatsAppNumberNormalizer`):**
  - Nomor ponsel Indonesia dinormalisasi ke standar internasional tanpa tanda plus: `0812...` → `62812...`, `+62812...` → `62812...`, `62812...` → `62812...`.
  - Karakter pemformatan (spasi, tanda hubung, titik, tanda kurung) dibersihkan secara aman.
  - Nomor yang kosong, tidak lengkap, atau tidak valid menghasilkan penolakan validasi bisnis HTTP `422 Unprocessable Entity` tanpa transformasi agresif yang merusak data.
- **Pemetaan Template Berdasarkan `OrderStatus`:**
  - `DRAFT`, `QUOTATION`, `CONFIRMED`, `WAITING_DP` → Template `ORDER_CREATED` (Konfirmasi pencatatan pesanan).
  - `READY_FOR_PRODUCTION`, `IN_PRODUCTION` → Template `IN_PRODUCTION` (Pemberitahuan masuk jalur produksi).
  - `QC`, `PACKING`, `READY_TO_SHIP`, `SHIPPED` → Template progres umum (*generic progress template* yang aman dan tidak mengklaim pesanan baru dibuat).
  - `COMPLETED` → Template `COMPLETED` (Pemberitahuan pesanan selesai).
  - `CANCELLED` → Ditolak dengan HTTP `422 Unprocessable Entity` ("WhatsApp sharing is unavailable for cancelled orders.").
- **Batasan Keamanan & Kerahasiaan Data:**
  - Pesan WhatsApp dan respons data payload **dilarang keras** memuat: catatan internal bengkel, detail cacat QC / hasil rework, ID internal database, informasi finansial (harga satuan, subtotal, margin, rekening), token Sanctum, atau `public_token` sebagai field JSON mandiri.
  - Tautan pelacakan publik merujuk langsung ke customer portal: `{FRONTEND_URL}/track/{public_token}`.
- **Otorisasi & Hak Akses Peran:**
  - Hanya peran `OWNER` dan `ADMIN` yang diizinkan menghasilkan tautan WhatsApp (`GET /api/v1/orders/{id}/whatsapp`).
  - Peran `PRODUCTION` dan `QC` ditolak tegas dengan HTTP `403 Forbidden`.
  - Permintaan lintas-tenant (*cross-tenant*) menghasilkan HTTP `404 Not Found`.
- **Rekam Jejak Audit (`ActivityLog`):**
  - Setiap pembentukan tautan WhatsApp dicatat sebagai aktivitas `WHATSAPP_SHARE_GENERATED` dengan metadata minimal `{"order_id": <id>, "channel": "whatsapp"}`.
  - Isi pesan lengkap, nomor telepon, dan URL WhatsApp tidak disimpan ke tabel log audit demi privasi dan efisiensi penyimpanan.

