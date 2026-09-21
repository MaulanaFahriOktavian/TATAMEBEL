# TATAMEBEL — Customer & Order Management Architecture (Phase 3)

## 1. Overview & Business Workflow
TATAMEBEL mendigitalkan alur pesanan mebel berbasis WhatsApp:
```text
Pelanggan WhatsApp
       ↓
Workshop Staff (Owner / Admin)
       ↓
1. Customer Creation / Selection
       ↓
2. Order Creation dengan minimal 1 Order Item
       ↓
3. Authoritative Backend Calculation (Subtotal & Total Amount)
       ↓
4. Order Number Generation (ORD-YYYYMM-XXXX) & Public Token
       ↓
5. Order Status Transition (12-State Order Machine)
```

---

## 2. Customer Management

### 2.1. Attributes & Schema
Tabel `customers`:
- `id` (Bigint PK)
- `workshop_id` (FK -> `workshops(id)`)
- `name` (String, required)
- `company_name` (String, nullable)
- `phone` (String(50), required) — WhatsApp kontak utama pelanggan
- `email` (String, nullable)
- `address` (Text, nullable)
- `notes` (Text, nullable)
- `timestamps`

### 2.2. Endpoints
- `GET /api/v1/customers` (List paginated)
- `POST /api/v1/customers` (Create customer)
- `GET /api/v1/customers/{id}` (Get customer detail)
- `PATCH /api/v1/customers/{id}` (Update customer)
- `DELETE /api/v1/customers/{id}` (Delete customer)

### 2.3. Customer Deletion Restriction
Pelanggan yang sudah memiliki riwayat pesanan (`orders`) **TIDAK BOLEH DIHAPUS**.
Jika request `DELETE /api/v1/customers/{id}` dipanggil pada pelanggan dengan pesanan aktif, backend menolak dengan HTTP 422 standard envelope:
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "customer": [
      "Pelanggan tidak dapat dihapus karena memiliki pesanan yang terdaftar."
    ]
  }
}
```

---

## 3. Order Management & Item Calculations

### 3.1. Order Attributes & Schema
Tabel `orders`:
- `id` (Bigint PK)
- `workshop_id` (FK -> `workshops(id)`)
- `customer_id` (FK -> `customers(id)`)
- `order_number` (String(100), unique per workshop)
- `title` (String, required)
- `status` (Enum: 12-states, default `DRAFT`)
- `total_amount` (Decimal(15,2), dihitung otoritatif oleh backend)
- `notes` (Text, nullable)
- `public_token` (String(100), unique, high-entropy)
- `confirmed_at`, `completed_at`, `cancelled_at` (Timestamps)
- `timestamps`

### 3.2. Order Creation (`POST /api/v1/orders`)
Pembuatan pesanan wajib menyertakan minimal 1 order item dalam satu database transaction yang atomik:
```json
{
  "customer_id": 1,
  "title": "Set Meja Makan Minimalis Jati",
  "notes": "Pesanan via WhatsApp",
  "items": [
    {
      "product_name": "Meja Makan Solid Teak 200x90",
      "product_code": "MM-01",
      "quantity": 1,
      "unit_price": 4500000,
      "notes": "Finishing Natural PU"
    },
    {
      "product_name": "Kursi Makan Jati",
      "product_code": "KM-02",
      "quantity": 6,
      "unit_price": 750000,
      "notes": "Bantalan fabric cream"
    }
  ]
}
```

### 3.3. Authoritative Calculations
Frontend **DILARANG** dipercaya untuk kalkulasi uang:
- `item.subtotal = round(quantity * unit_price, 2)`
- `order.total_amount = round(sum(item.subtotal), 2)`

### 3.4. Order Number Generation Pattern
- **Format:** `ORD-YYYYMM-XXXX` (contoh: `ORD-202609-0001`).
- **Urutan:** Sequential per workshop dan per bulan (reset setiap pergantian bulan).
- **Concurrency Protection:** Proses pembuatan order mengunci record workshop secara eksklusif (`Workshop::where('id', $workshopId)->lockForUpdate()->first()`) di dalam database transaction, mencegah bentrok / race condition duplicate number.

### 3.5. Public Token
- Di-generate secara otomatis saat pembuatan order menggunakan string acak berentropi tinggi (`Str::random(40)`).
- Menjadi token akses pelanggan untuk Customer Portal di Phase 6.

---

## 4. Order State Machine (12 States)

### 4.1. Status Flow
```text
DRAFT
  ↓ (bisa langsung lompat ke CONFIRMED jika deal WhatsApp langsung)
QUOTATION
  ↓
CONFIRMED
  ↓
WAITING_DP
  ↓
READY_FOR_PRODUCTION
  ↓
IN_PRODUCTION
  ↓
QC
  ↓
PACKING
  ↓
READY_TO_SHIP
  ↓
SHIPPED
  ↓
COMPLETED
```

### 4.2. Cancellation Rules (`CANCELLED`)
- **Diperbolehkan Batal:** Hanya dari status sebelum pengerjaan selesai:
  - `DRAFT`
  - `QUOTATION`
  - `CONFIRMED`
  - `WAITING_DP`
  - `READY_FOR_PRODUCTION`
  - `IN_PRODUCTION`
- **Dilarang Batal (HTTP 422):**
  - `QC`
  - `PACKING`
  - `READY_TO_SHIP`
  - `SHIPPED`
  - `COMPLETED`
  - `CANCELLED`
- Transisi status ilegal lainnya (misal `DRAFT -> SHIPPED` atau `COMPLETED -> DRAFT`) ditolak mutlak dengan HTTP 422.

---

## 5. Tenant Isolation & IDOR Defense

### 5.1. Customer-Order Workshop Matching
Order **DILARANG** menggunakan customer dari workshop lain.
Jika user Workshop A membuat order menggunakan `customer_id` milik Workshop B:
Backend menolak request dengan HTTP 422:
`"Pelanggan tidak ditemukan atau tidak terdaftar pada workshop ini."`

### 5.2. Scoped Queries & Zero Data Leakage
Seluruh pembacaan dan pembaruan data bisnis di-scope melalui relasi workshop user terotentikasi:
```php
$customer = $request->user()->workshop->customers()->whereKey($id)->first();
$order = $request->user()->workshop->orders()->whereKey($id)->first();
```
Jika penyerang mengetahui integer ID customer atau order workshop lain, backend mengembalikan **HTTP 404 Not Found** tanpa membocorkan eksistensi data tersebut.

---

## 6. Role Authorization Matrix

| Role | Read Customers | Mutate Customers | Read Orders | Create Order | Change Order Status |
|---|:---:|:---:|:---:|:---:|:---:|
| **OWNER** | Ya | Ya | Ya | Ya | Ya |
| **ADMIN** | Ya | Ya | Ya | Ya | Ya |
| **PRODUCTION** | Ya | Tidak (403) | Ya | Tidak (403) | Tidak (403) |
| **QC** | Ya | Tidak (403) | Ya | Tidak (403) | Tidak (403) |

---

## 7. Activity Audit Logging
Tabel `activity_logs` mencatat event-event operasional utama:
- `CUSTOMER_CREATED`: Saat pelanggan baru dibuat.
- `CUSTOMER_UPDATED`: Saat data pelanggan diubah.
- `CUSTOMER_DELETED`: Saat data pelanggan dihapus.
- `ORDER_CREATED`: Saat pesanan dibuat beserta rincian jumlah item dan total nilai pesanan.
- `ORDER_STATUS_CHANGED`: Saat status pesanan berpindah (menyimpan `from_status` dan `to_status` pada metadata JSON).
