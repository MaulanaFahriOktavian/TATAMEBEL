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
  - Tenant query wajib diverifikasi di backend melalui authenticated user context (`auth()->user()->workshop_id`) atau global tenant scope.
  - User workshop A dilarang keras dapat membaca, mengubah, atau menghapus data workshop B.
  - Frontend visibility bukan mekanisme keamanan; otorisasi mutlak ditegakkan di backend Laravel.

## 4. User Roles & Authorization Matrix
1. **OWNER:** Akses penuh ke seluruh workshop setting, manajemen user, keuangan (pencatatan pembayaran), order, produksi, dan QC.
2. **ADMIN:** Manajemen pelanggan, pemrosesan order, approval spesifikasi, koordinasi shipping.
3. **PRODUCTION:** Pembaruan tahapan produksi (stage progress), upload foto bukti pengerjaan (photo evidence).
4. **QC:** Inspeksi mutu (QC checklist), pencatatan cacat (defect tracking), persetujuan rework.

## 5. Order State Machine
Order memiliki 12 state yang transisinya divalidasi ketat oleh `OrderService`:
```
DRAFT -> QUOTATION -> CONFIRMED -> WAITING_DP -> READY_FOR_PRODUCTION 
      -> IN_PRODUCTION -> QC -> PACKING -> READY_TO_SHIP -> SHIPPED -> COMPLETED
      (CANCELLED dapat dipicu dari status sebelum pengerjaan selesai)
```
- Transisi tidak valid akan ditolak dengan HTTP 422 Unprocessable Entity.

## 6. Specification Versioning & Change Requests
- Spesifikasi mebel diawali dengan status `DRAFT`.
- Begitu disetujui, spesifikasi di-`LOCKED`.
- Spesifikasi yang berstatus `LOCKED` tidak boleh di-edit secara destruktif.
- Perubahan wajib melalui alur `Change Request` (PENDING -> APPROVED/REJECTED/CANCELLED).
- Jika disetujui, versi baru spesifikasi dibuat (`version = version + 1`) untuk menjaga jejak audit.

## 7. Production Stage & Authoritative Progress Calculation
- Progress produksi dihitung secara otoritatif oleh backend:
  $$\text{Progress (\%)} = \frac{\text{Jumlah Tahapan Aktif Selesai}}{\text{Total Tahapan Aktif}} \times 100$$
- Jika tidak ada tahapan aktif: progress = 0%.
- Frontend dilarang keras menghitung sendiri persentase progres.

## 8. Quality Control & Defect Tracking
- Kategori checklist QC: dimension, material, construction, surface, finishing, color, quantity, accessories, packaging.
- Status QC: `PENDING`, `PASSED`, `FAILED`, `REWORK`.
- Defect severity: `LOW`, `MEDIUM`, `HIGH`, `CRITICAL`.
- Defect status: `OPEN`, `IN_REWORK`, `RESOLVED`, `ACCEPTED`.
- Tahap Packing mensyaratkan QC lolos (PASSED atau defek telah RESOLVED/ACCEPTED).

## 9. Media & Visibility
- File foto evidence disimpan melalui Laravel Storage abstraction.
- Visibility: `INTERNAL` (hanya staf workshop) dan `CUSTOMER` (dapat dilihat di portal publik).

## 10. Customer Portal Security
- Akses portal pelanggan menggunakan random high-entropy `public_token` (contoh: 64-char URL-safe string).
- Portal bersifat read-only tanpa login.
- Data sensitif seperti catatan internal, foto internal, margin laba/harga beli, user internal, dan activity log tidak pernah di-expose ke publik.

## 11. WhatsApp Integration Philosophy
- TATAMEBEL menyediakan templated progress update message dengan tautan customer portal.
- Staf cukup mengklik "Kirim via WhatsApp" untuk membuka WhatsApp Web/Desktop dengan pesan terisi otomatis.
