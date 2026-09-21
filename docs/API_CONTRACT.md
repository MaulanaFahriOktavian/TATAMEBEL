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

### Specifications & Changes
- `POST /api/v1/orders/{order}/items/{item}/specification`
- `PATCH /api/v1/specifications/{id}`
- `POST /api/v1/specifications/{id}/lock`
- `POST /api/v1/orders/{id}/change-requests`
- `POST /api/v1/change-requests/{id}/approve`
- `POST /api/v1/change-requests/{id}/reject`

### Production & Media
- `GET /api/v1/orders/{id}/production`
- `POST /api/v1/orders/{id}/production/stages`
- `PATCH /api/v1/production/stages/{id}`
- `POST /api/v1/production/stages/{id}/updates`
- `POST /api/v1/orders/{id}/media`

### Quality Control
- `POST /api/v1/orders/{id}/qc`
- `POST /api/v1/qc/{id}/defects`

### Payments & Shipping
- `GET /api/v1/orders/{id}/payments`
- `POST /api/v1/orders/{id}/payments`
- `GET /api/v1/orders/{id}/shipping`
- `PUT /api/v1/orders/{id}/shipping`

### Dashboard & Public Portal
- `GET /api/v1/dashboard`
- `GET /api/v1/public/orders/{public_token}`
