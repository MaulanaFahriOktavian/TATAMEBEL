# TATAMEBEL — Specification & Production Tracking Architecture (Phase 4)

## 1. Executive Summary
Phase 4 menghubungkan kesepakatan pesanan mebel ke lantai produksi workshop:
```text
Pesanan (Order)
      ↓
Spesifikasi Teknis (DRAFT v1) ── (Lock) ──► Spesifikasi Terkunci (LOCKED)
                                                  │
               ┌──────────────────────────────────┤
               ▼                                  ▼
      READY_FOR_PRODUCTION               Change Request (PENDING)
               ↓                                  ↓
      Inisialisasi 8 Stages               Review & Approval (Admin/Owner)
               ↓                                  ↓
      IN_PRODUCTION                      Spesifikasi Versi Baru (DRAFT v2)
               ↓                                  ↓
      Update & Bukti Foto                 Review & Lock (LOCKED v2)
               ↓
   Kalkulasi Otoritatif Backend (%)
```

---

## 2. Order Specification & Lifecycle

### 2.1. Atribut Spesifikasi
Spesifikasi teknis mebel mendefinisikan parameter manufaktur:
- `width`, `height`, `depth` (Decimal, nullable)
- `dimension_unit` (String: `cm`, `mm`, `m`, `inch`)
- `material` (String: Kayu Jati, Mahoni, Sungkai, dll)
- `wood_grade` (String: Grade A, Grade B, Rustic, dll)
- `finishing` (String: Natural PU, Melamine, Duco, dll)
- `color` (String: Warm Teak, Walnut Brown, Bleached, dll)
- `fabric` (String, nullable: Kain jok bludru, oscar, linen, dll)
- `design_reference` (Text, nullable: URL gambar sketsa/referensi)
- `special_request` (Text, nullable: Catatan permintaan khusus pelanggan)
- `production_note` (Text, nullable: Catatan instruksi konstruksi pengrajin)

> **Catatan Normalisasi Database (Decision 2):**  
> `product_name`, `product_code`, dan `quantity` **tetap menjadi sumber kebenaran tunggal pada tabel `order_items`** dan tidak diduplikasi ke dalam tabel `specifications`. Data diserialisasi secara transparan melalui `SpecificationResource`.

### 2.2. Lifecycle Spesifikasi
```text
[ DRAFT ] ────────── (POST /specifications/{id}/lock) ──────────► [ LOCKED ]
    │                                                                  │
Bisa di-update langsung                                     Tidak boleh di-update (422).
via PATCH /specifications/{id}                              Wajib lewat Change Request.
```

---

## 3. Specification Versioning & Current Specification

### 3.1. Aturan Versioning
1. Satu item pesanan (`order_item`) dapat memiliki beberapa versi spesifikasi (`v1`, `v2`, `v3`, ...).
2. Versi pertama dimulai dari `version = 1`.
3. Keunikan versi per item dijamin oleh database constraint: `unique(['order_item_id', 'version'])`.
4. Versi lama bersifat **IMMUTABLE (tidak dapat diubah dan tidak boleh dihapus)** untuk menjaga audit trail historis.

### 3.2. Definisi Current Operational Specification (Clarification 2)
> **Definisi Otoritatif:**  
> Spesifikasi operasional aktif (`Current Specification`) didefinisikan secara ketat sebagai:  
> **VERSI TERTINGGI YANG BERSTATUS `LOCKED`**.

- Versi berstatus `DRAFT` tidak dianggap sebagai acuan produksi.
- Query resolver backend:
  ```php
  $item->specifications()
      ->where('status', SpecificationStatus::LOCKED)
      ->orderByDesc('version')
      ->first();
  ```
- Contoh skenario:
  - `v1 LOCKED`, `v2 DRAFT` ➔ Current = `v1`.
  - `v1 LOCKED`, `v2 LOCKED` ➔ Current = `v2`.
  - `v1 DRAFT` (belum ada yang locked) ➔ Current = `null`.

---

## 4. Change Request Governance

### 4.1. Lifecycle Change Request
`PENDING` ➔ `APPROVED` | `REJECTED` | `CANCELLED`.

### 4.2. Struktur Data Perubahan Terstruktur (`requested_changes` JSON)
Backend **dilarang menebak** perubahan dari free-text description. Change Request wajib mengirim data terstruktur berupa JSON:
```json
{
  "order_item_id": 1,
  "requested_by": "Pelanggan WhatsApp (Pak Hendra)",
  "description": "Minta ubah lebar dipan dari 180cm menjadi 200cm dan ganti finishing.",
  "reason": "Kasur yang dibeli ukuran super king.",
  "requested_changes": {
    "width": 200.0,
    "finishing": "Walnut Glossy"
  }
}
```

### 4.3. Approval & Deterministic Version Spawning
1. Ketika Change Request disetujui (`POST /change-requests/{id}/approve`), backend secara deterministik:
   - Mengambil spesifikasi baseline (`current_version`).
   - Menyalin seluruh atribut teknis sebelumnya.
   - Menerapkan perubahan dari `requested_changes`.
   - Menyimpan sebagai **versi baru (`version = version + 1`)** dalam status **`DRAFT`** (Decision 3).
2. Mengapa status baru `DRAFT`?  
   Memungkinkan staf workshop untuk memeriksa kelayakan konstruksi dan menambahkan catatan instruksi pengerjaan (`production_note`) sebelum menguncinya secara sadar (`LOCKED`).

---

## 5. Production Stages & Template

### 5.1. 8 Tahapan Standar SDD
Dikonfigurasi di `config/production.php` dan diinstansiasi ke pesanan:
1. **Material Preparation** (Sequence 1)
2. **Cutting** (Sequence 2)
3. **Assembly** (Sequence 3)
4. **Sanding** (Sequence 4)
5. **Finishing** (Sequence 5)
6. **Final Assembly** (Sequence 6)
7. **QC (Quality Control)** (Sequence 7)
8. **Packing** (Sequence 8)

Tahapan **tidak di-seed secara global** di database. Setiap pesanan memiliki baris tahapan mandiri (`production_stages.order_id`).

### 5.2. Handoff Tahapan QC (Clarification 3)
- Tahapan `QC` tidak dapat diselesaikan (`COMPLETED`) melalui endpoint tracking produksi biasa oleh staf `PRODUCTION`.
- Percobaan mengubah QC stage menjadi `COMPLETED` menghasilkan HTTP 422:
  `"Tahapan QC tidak dapat diselesaikan melalui production tracking biasa. Penyelesaian QC memerlukan modul QC Inspection (Phase 5)."`

---

## 6. Authoritative Progress Calculation Formula

Persentase kemajuan pesanan dihitung **100% oleh backend** menggunakan rumus SDD:

$$\text{Progress (\%)} = \frac{\text{Jumlah Tahapan Aktif Selesai}}{\text{Total Tahapan Aktif}} \times 100$$

- Filter: `is_active = true`.
- Jika `Total Tahapan Aktif == 0` ➔ `Progress = 0.00%`.
- Hasil pembulatan: 2 angka di belakang koma (contoh: `57.14%`).
- Frontend dilarang keras menghitung persentase progres sendiri. Nilai dihitung ulang dan disimpan ke kolom `progress_snapshot` pada tabel `production_updates`.

---

## 7. Media & Photo Evidence

- **Penyimpanan Multi-Tenant:**
  `Storage::disk('public')->put('workshops/{workshop_id}/orders/{order_id}/media/...')`.
- **Visibilitas:**
  - `INTERNAL`: Hanya untuk konsumsi staf internal workshop.
  - `CUSTOMER`: Memenuhi syarat untuk ditampilkan di Customer Progress Portal (Phase 6).
- **Validasi Berkas:**
  - MIME Types: `image/jpeg`, `image/png`, `image/webp`.
  - Ukuran maksimal: `10 MB` (10,240 KB).
  - Akses publik tanpa otentikasi dilarang di Phase 4.

---

## 8. Role Authorization Matrix (Phase 4)

| Kemampuan | OWNER | ADMIN | PRODUCTION | QC |
|---|:---:|:---:|:---:|:---:|
| **Lihat Spesifikasi** | Ya | Ya | Ya (Read-only) | Ya (Read-only) |
| **Buat / Edit DRAFT Spesifikasi** | Ya | Ya | Tidak (403) | Tidak (403) |
| **Kunci (LOCKED) Spesifikasi** | Ya | Ya | Tidak (403) | Tidak (403) |
| **Ajukan Change Request** | Ya | Ya | Ya (Floor Worker) | Tidak (403) |
| **Approve / Reject Change Request** | Ya | Ya | Tidak (403) | Tidak (403) |
| **Lihat Tahapan / Update Produksi** | Ya | Ya | Ya | Ya (Read-only) |
| **Ubah Status Tahapan (IN_PROGRESS/COMPLETED)** | Ya | Ya | Ya (Kecuali QC stage) | Tidak (403) |
| **Tambah Tahapan Custom / Hapus / Deaktivasi** | Ya | Ya | Tidak (403) | Tidak (403) |
| **Catat Update Produksi & Upload Bukti Foto** | Ya | Ya | Ya | Tidak (403) |

---

## 9. Order State Machine Integration

- **Gerbang `READY_FOR_PRODUCTION`:**
  Pesanan yang memiliki item wajib memastikan seluruh itemnya telah memiliki minimal 1 spesifikasi berstatus `LOCKED`. Jika ada item yang spesifikasinya masih `DRAFT` atau belum dibuat, transisi status pesanan ditolak dengan HTTP 422:
  `"Semua item pesanan harus memiliki spesifikasi yang sudah dikunci (LOCKED) sebelum masuk status READY_FOR_PRODUCTION."`
- **Inisialisasi Otomatis:** Saat pesanan berhasil masuk ke `READY_FOR_PRODUCTION`, jika pesanan belum memiliki tahapan produksi, backend secara otomatis menginstansiasi 8 tahapan standar.
- **DP Payment Deferred Rule:** Pengecekan bukti transfer Down Payment (DP) ditangguhkan ke Phase 5 (Modul Pembayaran).
