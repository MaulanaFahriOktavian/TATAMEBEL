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

### Authentication
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`

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
