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

### Customers
- `GET /api/v1/customers`
- `POST /api/v1/customers`
- `GET /api/v1/customers/{id}`
- `PATCH /api/v1/customers/{id}`
- `DELETE /api/v1/customers/{id}`

### Orders
- `GET /api/v1/orders`
- `POST /api/v1/orders`
- `GET /api/v1/orders/{id}`
- `PATCH /api/v1/orders/{id}/status`

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
