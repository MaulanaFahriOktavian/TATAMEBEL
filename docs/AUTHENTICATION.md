# TATAMEBEL — Authentication & Tenancy Architecture (Phase 2)

## 1. Overview & Security Philosophy
TATAMEBEL mengadopsi sistem autentikasi **Laravel Sanctum (Personal Access Token)** berbasis **Bearer Token** yang dipadukan secara ketat dengan **Workshop Tenancy Boundary**.

Prinsip keamanan utama:
1. **Zero Trust & Backend as Single Source of Truth:**
   Frontend tidak pernah dijadikan pembatas otorisasi. Otorisasi mutlak ditegakkan di backend Laravel.
2. **Tenant Isolation Over Roles:**
   Role tertinggi (`OWNER` atau `ADMIN`) sekalipun **TIDAK PERNAH** dapat membaca, mengubah, atau menghapus data milik Workshop lain. Mengetahui ID resource workshop lain tidak akan pernah membuka akses (zero data leakage).
3. **Anti-Enumeration Credentials:**
   Kegagalan login karena email salah, password keliru, ataupun akun tidak aktif (`is_active = false`) mengembalikan respons error yang seragam (`401 Invalid credentials.`) sehingga penyerang tidak dapat melakukan enumerasi user.
4. **Zero Sensitive Leakage:**
   Atribut sensitif (`password`, password hash, `remember_token`, token secret) tidak pernah keluar dari API Resource.

---

## 2. Standard API Envelope

### Success Response
```json
{
  "success": true,
  "message": "Human readable message.",
  "data": {}
}
```

### Error Response
```json
{
  "success": false,
  "message": "Human readable error summary.",
  "errors": {}
}
```

HTTP Status Codes:
- `200 OK`: Request berhasil diproses.
- `401 Unauthorized`: Kredensial tidak valid, token tidak disertakan, token telah dicabut, atau user tidak aktif.
- `403 Forbidden`: User tidak terikat ke workshop valid atau mencoba mengakses tenant lain.
- `422 Unprocessable Entity`: Validasi input request gagal.

---

## 3. Endpoints Contract

### 3.1. Login
Mengautentikasi kredensial pengguna, memastikan akun aktif dan terdaftar pada workshop, lalu menerbitkan personal access token Sanctum.

- **URL:** `POST /api/v1/auth/login`
- **Auth:** Public
- **Headers:**
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Request Body:**
  ```json
  {
    "email": "owner@kayulestari.com",
    "password": "password"
  }
  ```
- **Validation Rules:**
  - `email`: `required|string|email`
  - `password`: `required|string`
- **Success Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Login successful.",
    "data": {
      "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
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
- **Error Responses:**
  - `401 Unauthorized`: Kredensial salah atau user dinonaktifkan (`{"success":false,"message":"Invalid credentials.","errors":{}}`).
  - `403 Forbidden`: User tidak terhubung dengan workshop yang sah (`{"success":false,"message":"User does not belong to a valid workshop.","errors":{}}`).
  - `422 Unprocessable Entity`: Input tidak valid (`{"success":false,"message":"The given data was invalid.","errors":{...}}`).

---

### 3.2. Current User (Me)
Mengambil identitas profil, role, dan data tenant workshop dari pengguna yang sedang login.

- **URL:** `GET /api/v1/auth/me`
- **Auth:** Protected (`auth:sanctum`, `workshop.context`)
- **Headers:**
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
- **Success Response (200 OK):**
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
- **Error Responses:**
  - `401 Unauthorized`: Token tidak valid/telah dicabut atau akun dinonaktifkan.
  - `403 Forbidden`: Tenant workshop tidak valid.

---

### 3.3. Logout
Mencabut token sesi yang sedang aktif. Tidak mencabut token perangkat lain milik user yang sama secara sembarangan.

- **URL:** `POST /api/v1/auth/logout`
- **Auth:** Protected (`auth:sanctum`, `workshop.context`)
- **Headers:**
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
- **Success Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Logout successful.",
    "data": null
  }
  ```

---

## 4. Multi-Tenancy & Tenant Context Architecture

### 4.1. Request-Scoped Tenant Context
TATAMEBEL tidak menggunakan state global mutable (seperti `Tenant::$current`). Seluruh konteks tenant diikat secara request-scoped:
```text
HTTP Request (Header: Authorization: Bearer {token})
       ↓
Laravel Sanctum Guard (auth:sanctum)
       ↓
EnsureWorkshopContext Middleware
  ├── Memastikan authenticated user valid
  ├── Memastikan user->is_active === true (401 jika non-aktif)
  ├── Memastikan user->workshop_id valid & workshop exists (403 jika invalid)
  └── Menginjeksi workshop context ke $request->attributes
       ↓
Controller / Policy / Business Layer
```

### 4.2. Tenant Boundary Enforcement
Semua entitas bisnis terisolasi menggunakan `workshop_id`. Query wajib selalu ter-scope per workshop:
```php
// CONTOH QUERY RESMI (AMAN)
$order = $request->user()->workshop
    ->orders()
    ->whereKey($id)
    ->firstOrFail();
```
Mengetahui ID integer dari resource Workshop B tidak akan pernah menghasilkan record bagi User Workshop A karena query di-filter pada boundary `where workshop_id = ?`.

### 4.3. Policy Concern: `EnforcesWorkshopTenancy`
Tersedia trait `App\Policies\Concerns\EnforcesWorkshopTenancy` untuk otorisasi policy:
```php
public function belongsToSameWorkshop(User $user, Model $resource): bool
{
    if (! $user->workshop_id || ! isset($resource->workshop_id) || ! $resource->workshop_id) {
        return false;
    }

    return (int) $user->workshop_id === (int) $resource->workshop_id;
}
```

---

## 5. Role Capabilities Foundation

Enum `UserRole` mencakup 4 peran:
1. `OWNER`: Pengelola utama workshop.
2. `ADMIN`: Staff operasional, pesanan, dan customer.
3. `PRODUCTION`: Pengrajin / mandor produksi.
4. `QC`: Tim inspeksi mutu barang.

Helper methods pada `App\Models\User`:
```php
$user->hasRole(UserRole::OWNER);
$user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN]);
```

---

## 6. Seeded Local Development Credentials
*(HANYA UNTUK LOCAL DEVELOPMENT / TESTING — JANGAN DIGUNAKAN DI PRODUCTION)*

| Role | Name | Email | Password |
|---|---|---|---|
| **OWNER** | Pak Bambang (Owner) | `owner@kayulestari.com` | `password` |
| **ADMIN** | Siti Aminah (Admin) | `admin@kayulestari.com` | `password` |
| **PRODUCTION** | Joko Santoso (Kepala Produksi) | `produksi@kayulestari.com` | `password` |
| **QC** | Budi Setiawan (Inspektur QC) | `qc@kayulestari.com` | `password` |
