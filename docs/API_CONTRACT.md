# TATAMEBEL — REST API Contract

## Base URL
Semua API backend TATAMEBEL disajikan di bawah prefix resmi:
```
/api/v1
```

## Standard Response Envelopes

### 1. Single Item / Mutation Success (200 OK / 201 Created)
```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {}
}
```

### 2. Collection Success (200 OK)
```json
{
  "success": true,
  "message": "Data retrieved successfully.",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 45,
    "last_page": 3
  }
}
```

### 3. Validation Error (422 Unprocessable Entity)
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "field_name": [
      "The field_name field is required."
    ]
  }
}
```

### 4. Client / Server Error (400, 401, 403, 404, 500)
```json
{
  "success": false,
  "message": "Detailed error message without exposing stack traces or raw database exceptions."
}
```

---

## Phase 0 Foundation Endpoints

### 1. Health Check
- **Endpoint:** `GET /api/v1/health`
- **Auth:** Public
- **Description:** Memverifikasi status operasional server, konektivitas database, environment, dan versi aplikasi.
- **Success Response (200 OK):**
```json
{
  "success": true,
  "message": "TATAMEBEL API is healthy.",
  "data": {
    "status": "healthy",
    "environment": "local",
    "timestamp": "2026-09-20T09:47:00+07:00",
    "database": "connected",
    "version": "1.0.0"
  }
}
```
- **Database Degraded Response (200 OK with degraded status):**
```json
{
  "success": true,
  "message": "TATAMEBEL API is running with degraded dependencies.",
  "data": {
    "status": "degraded",
    "environment": "local",
    "timestamp": "2026-09-20T09:47:00+07:00",
    "database": "disconnected",
    "version": "1.0.0"
  }
}
```
*Catatan Keamanan:* Kegagalan koneksi database tidak pernah membocorkan kredensial, host IP, nama port, SQL query, atau stack trace.

---

## Target Endpoint Map (Phases 2 - 7)

### Authentication (Phase 2 — VERIFIED)
Lihat dokumentasi lengkap di [docs/AUTHENTICATION.md](AUTHENTICATION.md).

#### 1. Login
- **Method / Path:** `POST /api/v1/auth/login`
- **Auth:** Public
- **Request:**
  ```json
  {
    "email": "owner@kayulestari.com",
    "password": "password"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Login successful.",
    "data": {
      "token": "...",
      "token_type": "Bearer",
      "user": {
        "id": 1,
        "name": "Pak Bambang (Owner)",
        "email": "owner@kayulestari.com",
        "role": "OWNER",
        "is_active": true,
        "workshop": {
          "id": 1,
          "name": "Workshop Kayu Lestari",
          "slug": "workshop-kayu-lestari",
          "phone": "081234567890",
          "email": "info@kayulestari.com",
          "address": "Jl. Pengrajin Mebel No. 12, Jepara, Jawa Tengah",
          "timezone": "Asia/Jakarta"
        }
      }
    }
  }
  ```
- **Error Codes:** 401 (Invalid credentials / Inactive user), 403 (No valid workshop), 422 (Validation error).

#### 2. Get Authenticated User
- **Method / Path:** `GET /api/v1/auth/me`
- **Auth:** Bearer Token (`auth:sanctum`, `workshop.context`)
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Authenticated user.",
    "data": {
      "user": {
        "id": 1,
        "name": "Pak Bambang (Owner)",
        "email": "owner@kayulestari.com",
        "role": "OWNER",
        "is_active": true,
        "workshop": {
          "id": 1,
          "name": "Workshop Kayu Lestari",
          "slug": "workshop-kayu-lestari",
          "phone": "081234567890",
          "email": "info@kayulestari.com",
          "address": "Jl. Pengrajin Mebel No. 12, Jepara, Jawa Tengah",
          "timezone": "Asia/Jakarta"
        }
      }
    }
  }
  ```
- **Error Codes:** 401 (Unauthenticated / Token revoked / Inactive user), 403 (Invalid workshop).

#### 3. Logout
- **Method / Path:** `POST /api/v1/auth/logout`
- **Auth:** Bearer Token (`auth:sanctum`, `workshop.context`)
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Logout successful.",
    "data": null
  }
  ```
- **Error Codes:** 401 (Unauthenticated).

### Customers (Phase 3 — VERIFIED)
Lihat detail lengkap di [docs/CUSTOMER_ORDER.md](CUSTOMER_ORDER.md).

#### 1. List Customers
- **Method / Path:** `GET /api/v1/customers`
- **Auth:** Bearer Token (`auth:sanctum`, `workshop.context`)
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Customers retrieved successfully.",
    "data": [
      {
        "id": 1,
        "name": "Pak Hendra Jati",
        "company_name": "PT Mebel Nusantara",
        "phone": "081234567888",
        "email": "hendra@mebelnusantara.com",
        "address": "Jl. Raya Tahunan No. 10, Jepara",
        "notes": "VIP buyer WhatsApp",
        "created_at": "2026-09-21T06:11:50.000000Z",
        "updated_at": "2026-09-21T06:11:50.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 15,
      "total": 1,
      "last_page": 1
    }
  }
  ```

#### 2. Create Customer
- **Method / Path:** `POST /api/v1/customers`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Request Body:**
  ```json
  {
    "name": "Pak Hendra Jati",
    "company_name": "PT Mebel Nusantara",
    "phone": "081234567888",
    "email": "hendra@mebelnusantara.com",
    "address": "Jl. Raya Tahunan No. 10, Jepara",
    "notes": "VIP buyer WhatsApp"
  }
  ```
- **Response (201 Created):** Customer data envelope.

#### 3. Get Customer Detail
- **Method / Path:** `GET /api/v1/customers/{id}`
- **Response (200 OK):** Customer data envelope. 404 jika tidak ditemukan di workshop.

#### 4. Update Customer
- **Method / Path:** `PATCH /api/v1/customers/{id}`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Response (200 OK):** Updated customer data envelope.

#### 5. Delete Customer
- **Method / Path:** `DELETE /api/v1/customers/{id}`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Response (200 OK):** `{ "success": true, "message": "Customer deleted successfully.", "data": null }`.
- **Constraint:** Ditolak 422 jika pelanggan memiliki pesanan yang terdaftar.

---

### Orders (Phase 3 — VERIFIED)
Lihat detail lengkap di [docs/CUSTOMER_ORDER.md](CUSTOMER_ORDER.md).

#### 1. List Orders
- **Method / Path:** `GET /api/v1/orders`
- **Auth:** Bearer Token (`auth:sanctum`, `workshop.context`)
- **Response (200 OK):** Paginated orders envelope dengan relasi `customer`.

#### 2. Create Order
- **Method / Path:** `POST /api/v1/orders`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Request Body:**
  ```json
  {
    "customer_id": 1,
    "title": "Set Meja Tamu Ukir Mewah",
    "notes": "Finishing Walnut Glossy",
    "items": [
      {
        "product_name": "Meja Tamu Ukir Jati 150x80",
        "product_code": "MT-UKIR-01",
        "quantity": 1,
        "unit_price": 3500000,
        "notes": "Ukir motif Jepara klasik"
      },
      {
        "product_name": "Kursi Tamu Ukir Single",
        "product_code": "KT-UKIR-02",
        "quantity": 4,
        "unit_price": 1250000,
        "notes": "Busa royal foam kain bludru gold"
      }
    ]
  }
  ```
- **Response (201 Created):**
  ```json
  {
    "success": true,
    "message": "Order created successfully.",
    "data": {
      "id": 1,
      "order_number": "ORD-202609-0001",
      "title": "Set Meja Tamu Ukir Mewah",
      "status": "DRAFT",
      "total_amount": 8500000,
      "notes": "Finishing Walnut Glossy",
      "public_token": "cIuuwGQOMTP7ajwAtmWCHHff60Vk3grnsgSEvA3t",
      "customer": { ... },
      "items": [ ... ]
    }
  }
  ```

#### 3. Get Order Detail
- **Method / Path:** `GET /api/v1/orders/{id}`
- **Auth:** Bearer Token (`auth:sanctum`, `workshop.context`)
- **Response (200 OK):** Detail order lengkap dengan customer dan order items. 404 jika beda workshop.

#### 4. Change Order Status
- **Method / Path:** `PATCH /api/v1/orders/{id}/status`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Request Body:** `{ "status": "CONFIRMED" }`
- **Response (200 OK):** Updated order data envelope. 422 jika transisi tidak diizinkan oleh state machine.

### Specifications (Phase 4 — VERIFIED)

#### 1. Create Specification (DRAFT v1)
- **Method / Path:** `POST /api/v1/orders/{orderId}/items/{itemId}/specifications`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Request Body:**
  ```json
  {
    "width": 200.0,
    "height": 75.0,
    "depth": 90.0,
    "dimension_unit": "cm",
    "material": "Kayu Jati Solid TPK Perhutani",
    "wood_grade": "Grade A",
    "finishing": "Natural PU Satin",
    "color": "Warm Teak",
    "fabric": null,
    "design_reference": "https://example.com/sketches/meja.jpg",
    "special_request": "Sudut meja dibuat beveled 45 derajat",
    "production_note": "Gunakan konstruksi mortise and tenon ganda"
  }
  ```
- **Response (201 Created):** Single item specification envelope (`status`: `DRAFT`, `version`: 1, product attributes from `OrderItem`).

#### 2. List Specification Versions
- **Method / Path:** `GET /api/v1/orders/{orderId}/items/{itemId}/specifications`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `PRODUCTION`, `QC`)
- **Response (200 OK):** Collection of all historical versions in descending order.

#### 3. Get Current Operational Specification
- **Method / Path:** `GET /api/v1/orders/{orderId}/items/{itemId}/specifications/current`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `PRODUCTION`, `QC`)
- **Response (200 OK):** Returns the highest version with `status = LOCKED`. If no locked version exists, returns `{ "data": null }`.

#### 4. Update DRAFT Specification
- **Method / Path:** `PATCH /api/v1/specifications/{id}`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Response (200 OK):** Updated specification envelope. Ditolak HTTP 422 jika status sudah `LOCKED`.

#### 5. Lock Specification
- **Method / Path:** `POST /api/v1/specifications/{id}/lock`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Response (200 OK):** Locked specification envelope (`status`: `LOCKED`, `locked_at`, `locked_by`).

---

### Change Requests (Phase 4 — VERIFIED)

#### 1. Submit Change Request
- **Method / Path:** `POST /api/v1/orders/{orderId}/change-requests`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `PRODUCTION`)
- **Request Body:**
  ```json
  {
    "order_item_id": 1,
    "requested_by": "Pelanggan WhatsApp (Pak Hendra)",
    "description": "Minta ubah lebar dipan dari 180cm menjadi 200cm dan finishing ganti ke Walnut Glossy.",
    "reason": "Kasur yang dibeli ukuran super king.",
    "requested_changes": {
      "width": 200.0,
      "finishing": "Walnut Glossy"
    }
  }
  ```
- **Response (201 Created):** Change request envelope (`status`: `PENDING`). Ditolak 422 jika item belum memiliki spesifikasi LOCKED atau requested_changes tidak valid.

#### 2. List Change Requests
- **Method / Path:** `GET /api/v1/orders/{orderId}/change-requests`
- **Response (200 OK):** Array of change requests for the order.

#### 3. Approve Change Request
- **Method / Path:** `POST /api/v1/change-requests/{id}/approve`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Request Body:** `{ "review_note": "Disetujui setelah konfirmasi ketersediaan bahan baku." }`
- **Response (200 OK):** Contains updated change request (`APPROVED`) and newly created specification (`version = v+1`, `status = DRAFT`).

#### 4. Reject Change Request
- **Method / Path:** `POST /api/v1/change-requests/{id}/reject`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Request Body:** `{ "review_note": "Alasan penolakan wajib diisi." }`
- **Response (200 OK):** Updated change request (`REJECTED`).

---

### Production Tracking & Media (Phase 4 — VERIFIED)

#### 1. Production Overview & Authoritative Progress
- **Method / Path:** `GET /api/v1/orders/{orderId}/production`
- **Auth:** Bearer Token (`auth:sanctum`, `workshop.context`)
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Production overview retrieved successfully.",
    "data": {
      "order_id": 1,
      "order_number": "ORD-202609-0001",
      "order_title": "Set Meja Tamu Ukir",
      "order_status": "IN_PRODUCTION",
      "progress_percentage": 50.0,
      "total_active_stages": 8,
      "completed_active_stages": 4,
      "stages": [ ... ],
      "recent_updates": [ ... ]
    }
  }
  ```

#### 2. Initialize Default 8 Stages
- **Method / Path:** `POST /api/v1/orders/{orderId}/production/init-stages`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Response (200 OK):** Array of the 8 default stages.

#### 3. Update Stage Status
- **Method / Path:** `PATCH /api/v1/production-stages/{id}`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `PRODUCTION`)
- **Request Body:** `{ "status": "IN_PROGRESS" }` (or `COMPLETED`)
- **Constraint:** Tahapan QC tidak dapat diselesaikan via endpoint ini (HTTP 422, handoff untuk Phase 5).

#### 4. Post Production Progress Update
- **Method / Path:** `POST /api/v1/production-stages/{id}/updates`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `PRODUCTION`)
- **Content-Type:** `multipart/form-data`
- **Fields:** `description` (required), `media[]` (optional images), `media_visibility` (`INTERNAL`/`CUSTOMER`), `media_caption` (optional).
- **Response (201 Created):** Update envelope with calculated `progress_snapshot`.

#### 5. Upload Standalone Photo Evidence
- **Method / Path:** `POST /api/v1/orders/{orderId}/media`
- **Content-Type:** `multipart/form-data`
- **Fields:** `file` (image max 10MB), `visibility` (`INTERNAL`/`CUSTOMER`), `caption`.

#### 6. Delete Media
- **Method / Path:** `DELETE /api/v1/media/{id}`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, or uploader)

### Quality Control & Defect Tracking (Phase 5)

#### 1. List QC Inspections for an Order
- **Method / Path:** `GET /api/v1/orders/{orderId}/qc-inspections`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `PRODUCTION`, `QC`)
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "QC inspections retrieved successfully.",
  "data": [
    {
      "id": 1,
      "order_id": 1,
      "status": "PASSED",
      "notes": "Pemeriksaan akhir mutu mebel",
      "inspected_at": "2026-09-21T14:00:00Z",
      "inspector": {
        "id": 4,
        "name": "Budi Setiawan (Inspektur QC)",
        "role": "QC"
      },
      "items_count": 9,
      "defects_count": 0,
      "created_at": "2026-09-21T13:30:00Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
}
```

#### 2. Create QC Inspection
- **Method / Path:** `POST /api/v1/orders/{orderId}/qc-inspections`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `QC`)
- **Request Body:**
```json
{
  "notes": "Sesi inspeksi pra-packing meja makan",
  "order_item_id": 1,
  "custom_items": [
    {
      "category": "special",
      "item": "Kekokohan tarikan laci rahasia",
      "notes": "Sesuai request customer di WhatsApp"
    }
  ]
}
```
- **Response (201 Created):** Inspection created with 9 default template items (`status: null`) and any custom items.

#### 3. Get QC Inspection Detail
- **Method / Path:** `GET /api/v1/qc-inspections/{id}`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `PRODUCTION`, `QC`)
- **Response (200 OK):** Detailed inspection with `items`, `defects`, `media`, and inspector details.

#### 4. Evaluate Checklist Items (Batch)
- **Method / Path:** `POST /api/v1/qc-inspections/{id}/items`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `QC`)
- **Constraint:** Hanya diizinkan saat inspeksi berstatus `PENDING`.
- **Request Body:**
```json
{
  "items": [
    {
      "id": 1,
      "status": "PASS",
      "notes": "Ukuran sesuai gambar kerja LOCKED"
    },
    {
      "id": 2,
      "status": "FAIL",
      "notes": "Terdapat goresan pada permukaan daun meja"
    },
    {
      "id": 3,
      "status": "NA",
      "notes": "Tidak menggunakan kain jok"
    }
  ]
}
```

#### 5. Finalize QC Inspection
- **Method / Path:** `POST /api/v1/qc-inspections/{id}/finalize`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `QC`)
- **Constraint:** Inspeksi yang difinalisasi bersifat **IMMUTABLE**. Finalisasi `PASSED` mewajibkan seluruh butir dinilai, tidak ada item `FAIL`, tidak ada defek `OPEN`/`IN_REWORK`, dan otomatis menyelesaikan production stage Sequence 7 (`QC`).
- **Request Body:**
```json
{
  "status": "PASSED",
  "notes": "Seluruh poin checklist telah sesuai standar mutu bengkel."
}
```

#### 6. Upload Inspection Photo Evidence
- **Method / Path:** `POST /api/v1/qc-inspections/{id}/media`
- **Content-Type:** `multipart/form-data`
- **Fields:** `file` (image max 10MB), `visibility` (`INTERNAL`/`CUSTOMER`, default `INTERNAL`), `caption`.

#### 7. Log QC Defect
- **Method / Path:** `POST /api/v1/qc-inspections/{id}/defects`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `QC`)
- **Constraint:** Hanya dapat dicatat saat inspeksi masih berstatus `PENDING`.
- **Request Body:**
```json
{
  "qc_item_id": 2,
  "description": "Lapisan pernis tidak rata dan ada lelehan pada kaki meja kanan depan.",
  "severity": "MEDIUM"
}
```

#### 8. Update Defect Lifecycle Status
- **Method / Path:** `PATCH /api/v1/qc-defects/{id}/status`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `PRODUCTION`, `QC`)
- **RBAC Rule:** Status `ACCEPTED` (waiver) **hanya boleh diubah oleh `OWNER` atau `ADMIN`** dan wajib mencantumkan justifikasi. Staf `PRODUCTION` dan `QC` dapat mengubah ke `IN_REWORK` dan `RESOLVED` (wajib catatan tindakan korektif).
- **Request Body (RESOLVED):**
```json
{
  "status": "RESOLVED",
  "resolution": "Permukaan diamplas ulang grit 400 dan disemprot top coat satin ulang."
}
```
- **Request Body (ACCEPTED - Owner/Admin):**
```json
{
  "status": "ACCEPTED",
  "resolution": "Variasi serat alami kayu jati disetujui owner dan telah dikonfirmasi ke customer."
}
```

#### 9. Upload Defect Photo Evidence
- **Method / Path:** `POST /api/v1/qc-defects/{id}/media`
- **Content-Type:** `multipart/form-data`
- **Fields:** `file` (image max 10MB), `visibility` (`INTERNAL`/`CUSTOMER`, default `INTERNAL`), `caption`.

#### 10. Delete QC Defect
- **Method / Path:** `DELETE /api/v1/qc-defects/{id}`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`, `QC`)
- **Constraint:** Hanya dapat dihapus saat inspeksi induk masih berstatus `PENDING`.

### Payments & Shipping
- `GET /api/v1/orders/{id}/payments`
- `POST /api/v1/orders/{id}/payments`
- `GET /api/v1/orders/{id}/shipping`
- `PUT /api/v1/orders/{id}/shipping`

### Customer Progress Portal (Public - Phase 6)

#### 1. Get Public Order Tracking
- **Method / Path:** `GET /api/v1/public/orders/{public_token}`
- **Auth:** Public (Tanpa Bearer Token).
- **Middleware:** `throttle:60,1` (Maksimal 60 request per menit per alamat IP).
- **Lookup Constraint:** Dicocokkan secara ketat pada `orders.public_token` (indeks B-tree unik).
- **Keamanan & Proyeksi:** Menggunakan `CustomerPortalOrderResource`. Menolak eksposur seluruh ID internal database, data finansial (`total_amount`, `unit_price`), `public_token` dalam respons, catatan internal, activity/audit logs, defect QC internal, dan foto ber-visibilitas `INTERNAL`.
- **Response 200 OK:**
```json
{
  "success": true,
  "message": "Order tracking details retrieved successfully.",
  "data": {
    "order": {
      "order_number": "ORD-202609-0001",
      "title": "Meja Makan Jati Minimalis",
      "status": "IN_PRODUCTION",
      "status_label": "Sedang Diproduksi",
      "created_at": "2026-09-20T09:30:00Z",
      "confirmed_at": "2026-09-20T10:00:00Z",
      "customer_name": "Bpk. Hendra",
      "workshop": {
        "name": "Jati Indah Furniture",
        "phone": "08123456789",
        "address": "Jepara, Jawa Tengah"
      }
    },
    "items": [
      {
        "product_name": "Meja Makan Utama 6 Kursi",
        "product_code": "TBL-01",
        "quantity": 1,
        "notes": "Finishing natural doff",
        "specification": {
          "version": 1,
          "dimensions": {
            "width": "200.00",
            "height": "75.00",
            "depth": "100.00",
            "unit": "cm"
          },
          "material": "Kayu Jati Solid",
          "wood_grade": "Grade A TPK",
          "finishing": "Natural Teak Oil Polyurethane",
          "color": "Warm Honey Teak",
          "fabric": null,
          "design_reference": "Minimalis Scandinavian",
          "special_request": "Ujung meja dibuat bevel rounded 10mm"
        }
      }
    ],
    "production": {
      "progress_percentage": 50.0,
      "current_stage": "Assembly",
      "stages": [
        {
          "sequence": 1,
          "name": "Material Preparation",
          "status": "COMPLETED",
          "status_label": "Selesai",
          "started_at": "2026-09-20T11:00:00Z",
          "completed_at": "2026-09-20T14:00:00Z"
        },
        {
          "sequence": 2,
          "name": "Cutting",
          "status": "COMPLETED",
          "status_label": "Selesai",
          "started_at": "2026-09-20T14:00:00Z",
          "completed_at": "2026-09-21T09:00:00Z"
        },
        {
          "sequence": 3,
          "name": "Assembly",
          "status": "IN_PROGRESS",
          "status_label": "Sedang Dikerjakan",
          "started_at": "2026-09-21T09:30:00Z",
          "completed_at": null
        }
      ]
    },
    "photos": [
      {
        "url": "http://localhost:8000/storage/workshops/1/orders/1/media/xyz.jpg",
        "caption": "Rangka utama meja makan telah dirakit dan presisi.",
        "uploaded_at": "2026-09-21T09:45:00Z"
      }
    ],
    "quality_control": {
      "status": "IN_PROGRESS",
      "status_label": "Sedang dalam Pengecekan Kualitas",
      "passed_at": null,
      "note": "Pesanan sedang dalam tahap evaluasi kualitas komprehensif."
    },
    "shipping": {
      "courier": "Jepara Cargo Express",
      "tracking_number": "JCE-88992211",
      "status": "SHIPPED",
      "status_label": "Dalam Pengiriman",
      "shipped_at": "2026-09-22T08:00:00Z",
      "estimated_arrival": "2026-09-25",
      "delivered_at": null
    }
  }
}
```
- **Response 404 Not Found (Invalid / Unknown Token):**
```json
{
  "success": false,
  "message": "Pesanan tidak ditemukan atau tautan pelacakan tidak valid.",
  "errors": {}
}
```
- **Response 429 Too Many Requests:**
```json
{
  "message": "Too Many Attempts."
}
```

### WhatsApp Workflow (Phase 7)

#### 1. Generate WhatsApp Share Data
- **Method / Path:** `GET /api/v1/orders/{id}/whatsapp`
- **Auth:** Bearer Token (Roles: `OWNER`, `ADMIN`)
- **Otorisasi & Keamanan:**
  - Hanya staf `OWNER` dan `ADMIN` yang diizinkan (staf `PRODUCTION` dan `QC` ditolak HTTP 403 Forbidden).
  - Terisolasi per-workshop (akses ke order workshop lain menghasilkan HTTP 404 Not Found).
  - Memerlukan nomor telepon pelanggan yang valid (format Indonesia dinormalisasi ke `628...`). Jika nomor telepon kosong atau tidak valid, mengembalikan HTTP 422 Unprocessable Entity.
  - Pesanan dengan status `CANCELLED` tidak dapat dibagikan (mengembalikan HTTP 422 Unprocessable Entity).
  - URL teks pesan di-encode menggunakan RFC 3986 (`rawurlencode`).
  - Respons data JSON **tidak membocorkan** `public_token` sebagai field mandiri, tidak memuat ID internal, data finansial, atau data cacat QC.
  - Menghasilkan pencatatan audit log `WHATSAPP_SHARE_GENERATED` dengan metadata minimal `{"order_id": <id>, "channel": "whatsapp"}`.
- **Response 200 OK:**
```json
{
  "success": true,
  "message": "WhatsApp share data generated.",
  "data": {
    "phone": "6281234567890",
    "message": "Halo Budi Santoso,\n\nPesanan Anda telah dicatat oleh Karya Jati Jepara.\n\nNomor Pesanan: ORD-202609-0001\nProduk: Meja Makan Jati 6 Kursi\n\nPantau perkembangan pesanan:\nhttp://localhost:5173/track/abcdef1234567890abcdef1234567890abcdef12\n\nTerima kasih.",
    "url": "https://wa.me/6281234567890?text=Halo%20Budi%20Santoso%2C%0A%0APesanan%20Anda%20telah%20dicatat%20oleh%20Karya%20Jati%20Jepara.%0A%0ANomor%20Pesanan%3A%20ORD-202609-0001%0AProduk%3A%20Meja%20Makan%20Jati%206%20Kursi%0A%0APantau%20perkembangan%20pesanan%3A%0Ahttp%3A%2F%2Flocalhost%3A5173%2Ftrack%2Fabcdef1234567890abcdef1234567890abcdef12%0A%0ATerima%20kasih."
  }
}
```
- **Response 403 Forbidden (Peran Tidak Diizinkan):**
```json
{
  "message": "This action is unauthorized."
}
```
- **Response 404 Not Found (Pesanan Tidak Ditemukan / Lintas Tenant):**
```json
{
  "success": false,
  "message": "Order not found.",
  "errors": {}
}
```
- **Response 422 Unprocessable Entity (Nomor Tidak Valid / Pesanan Dibatalkan):**
```json
{
  "message": "Nomor WhatsApp pelanggan tidak valid atau belum diisi.",
  "errors": {
    "phone": [
      "Nomor WhatsApp pelanggan tidak valid atau belum diisi."
    ]
  }
}
```

### Dashboard (Planned)
- `GET /api/v1/dashboard`

