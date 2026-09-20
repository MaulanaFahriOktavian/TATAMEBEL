# TATAMEBEL — Database Schema Specification

## Overview
Semua tabel bisnis wajib memiliki kolom `workshop_id` sebagai kunci isolasi multi-tenancy.
Schema dikelola secara eksklusif melalui Laravel Migrations.

---

## 1. Core Tables

### 1. `workshops`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key, Auto Increment |
| name | VARCHAR(255) | No | Nama Workshop / Pengrajin |
| slug | VARCHAR(255) | No | Unique, URL-friendly slug |
| phone | VARCHAR(50) | No | Nomor kontak WhatsApp workshop |
| email | VARCHAR(255) | Yes | Email resmi workshop |
| address | TEXT | Yes | Alamat fisik workshop |
| timezone | VARCHAR(50) | No | Default: 'Asia/Jakarta' |
| created_at, updated_at | TIMESTAMP | Yes | Standard Laravel Timestamps |

### 2. `users`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| name | VARCHAR(255) | No | Nama Lengkap |
| email | VARCHAR(255) | No | Unique per sistem |
| password | VARCHAR(255) | No | Hashed bcrypt |
| role | ENUM | No | OWNER, ADMIN, PRODUCTION, QC |
| is_active | BOOLEAN | No | Default: true |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 3. `customers`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| name | VARCHAR(255) | No | Nama Pelanggan |
| company_name | VARCHAR(255) | Yes | Nama Usaha/Perusahaan Pelanggan |
| phone | VARCHAR(50) | No | WhatsApp Pelanggan (Index) |
| email | VARCHAR(255) | Yes | Email Pelanggan |
| address | TEXT | Yes | Alamat Kirim Default |
| notes | TEXT | Yes | Catatan Preferensi Pelanggan |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 4. `orders`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| customer_id | BIGINT UNSIGNED | No | Foreign Key -> customers(id) ON DELETE RESTRICT |
| order_number | VARCHAR(100) | No | Unique per workshop (Index) |
| title | VARCHAR(255) | No | Judul/Deskripsi singkat pesanan |
| status | ENUM | No | 12 state order machine (Default: DRAFT) |
| total_amount | DECIMAL(15, 2) | No | Nilai total pesanan |
| notes | TEXT | Yes | Catatan order |
| public_token | VARCHAR(100) | No | Unique, High-entropy token (Index) |
| confirmed_at | TIMESTAMP | Yes | Waktu order dikonfirmasi |
| completed_at | TIMESTAMP | Yes | Waktu order selesai |
| cancelled_at | TIMESTAMP | Yes | Waktu order dibatalkan |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 5. `order_items`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| order_id | BIGINT UNSIGNED | No | Foreign Key -> orders(id) ON DELETE CASCADE |
| product_name | VARCHAR(255) | No | Nama Barang (e.g. Meja Makan Solid Teak) |
| product_code | VARCHAR(100) | Yes | Kode SKU / Model |
| quantity | INT UNSIGNED | No | Jumlah unit |
| unit_price | DECIMAL(15, 2) | No | Harga satuan |
| subtotal | DECIMAL(15, 2) | No | quantity * unit_price |
| notes | TEXT | Yes | Catatan khusus item |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 6. `specifications`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| order_item_id | BIGINT UNSIGNED | No | Foreign Key -> order_items(id) ON DELETE CASCADE |
| version | INT UNSIGNED | No | Default: 1 |
| width | DECIMAL(10, 2) | Yes | Lebar |
| height | DECIMAL(10, 2) | Yes | Tinggi |
| depth | DECIMAL(10, 2) | Yes | Kedalaman / Panjang |
| dimension_unit | VARCHAR(20) | No | Default: 'cm' (mm, cm, m, inch) |
| material | VARCHAR(255) | Yes | Bahan baku utama (Kayu Jati, Mahoni, dll) |
| wood_grade | VARCHAR(100) | Yes | Grade Kayu (A, B, C, Rustic) |
| finishing | VARCHAR(255) | Yes | Tipe finishing (Melamine, PU, Natural Oil) |
| color | VARCHAR(100) | Yes | Warna / Sample code |
| fabric | VARCHAR(255) | Yes | Bahan kain / kulit jok |
| design_reference | TEXT | Yes | Referensi desain / link sketsa |
| special_request | TEXT | Yes | Permintaan khusus pelanggan |
| production_note | TEXT | Yes | Catatan teknis pengrajin |
| status | ENUM | No | DRAFT, LOCKED |
| locked_at | TIMESTAMP | Yes | Tanggal penguncian |
| locked_by | BIGINT UNSIGNED | Yes | Foreign Key -> users(id) |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 7. `change_requests`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| order_id | BIGINT UNSIGNED | No | Foreign Key -> orders(id) ON DELETE CASCADE |
| requested_by | VARCHAR(255) | No | Nama pemohon perubahan |
| description | TEXT | No | Deskripsi spesifikasi yang ingin diubah |
| reason | TEXT | Yes | Alasan perubahan |
| status | ENUM | No | PENDING, APPROVED, REJECTED, CANCELLED |
| approved_by | BIGINT UNSIGNED | Yes | Foreign Key -> users(id) |
| approved_at | TIMESTAMP | Yes | Waktu persetujuan |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 8. `production_stages`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| order_id | BIGINT UNSIGNED | No | Foreign Key -> orders(id) ON DELETE CASCADE |
| name | VARCHAR(255) | No | Nama tahapan (Cutting, Assembly, Sanding, dll) |
| sequence | INT UNSIGNED | No | Urutan pengerjaan |
| status | ENUM | No | PENDING, IN_PROGRESS, COMPLETED, SKIPPED |
| started_at | TIMESTAMP | Yes | Waktu mulai tahap |
| completed_at | TIMESTAMP | Yes | Waktu selesai tahap |
| is_active | BOOLEAN | No | Default: true (masuk rumus persentase) |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 9. `production_updates`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| order_id | BIGINT UNSIGNED | No | Foreign Key -> orders(id) ON DELETE CASCADE |
| production_stage_id | BIGINT UNSIGNED | No | Foreign Key -> production_stages(id) |
| user_id | BIGINT UNSIGNED | No | Foreign Key -> users(id) |
| description | TEXT | No | Catatan laporan pengerjaan |
| progress_snapshot | DECIMAL(5, 2) | No | Snapshot % progres saat update dibuat |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 10. `media`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| order_id | BIGINT UNSIGNED | No | Foreign Key -> orders(id) ON DELETE CASCADE |
| production_update_id | BIGINT UNSIGNED | Yes | Foreign Key -> production_updates(id) ON DELETE SET NULL |
| qc_inspection_id | BIGINT UNSIGNED | Yes | Foreign Key -> qc_inspections(id) ON DELETE SET NULL |
| uploaded_by | BIGINT UNSIGNED | No | Foreign Key -> users(id) |
| file_path | VARCHAR(500) | No | Lokasi berkas di storage |
| original_name | VARCHAR(255) | No | Nama file asli saat diunggah |
| mime_type | VARCHAR(100) | No | Tipe mime file |
| file_size | BIGINT UNSIGNED | No | Ukuran dalam bytes |
| visibility | ENUM | No | INTERNAL, CUSTOMER |
| caption | VARCHAR(255) | Yes | Keterangan foto |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 11. `qc_inspections`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| order_id | BIGINT UNSIGNED | No | Foreign Key -> orders(id) ON DELETE CASCADE |
| inspected_by | BIGINT UNSIGNED | No | Foreign Key -> users(id) |
| status | ENUM | No | PENDING, PASSED, FAILED, REWORK |
| notes | TEXT | Yes | Ringkasan hasil inspeksi |
| inspected_at | TIMESTAMP | Yes | Waktu inspeksi dilakukan |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 12. `qc_items`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| qc_inspection_id | BIGINT UNSIGNED | No | Foreign Key -> qc_inspections(id) ON DELETE CASCADE |
| category | VARCHAR(100) | No | dimension, material, construction, surface, etc. |
| item | VARCHAR(255) | No | Deskripsi poin pemeriksaan |
| status | ENUM | No | PENDING, PASSED, FAILED |
| notes | TEXT | Yes | Catatan hasil per poin |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 13. `qc_defects`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| qc_inspection_id | BIGINT UNSIGNED | No | Foreign Key -> qc_inspections(id) ON DELETE CASCADE |
| qc_item_id | BIGINT UNSIGNED | Yes | Foreign Key -> qc_items(id) ON DELETE SET NULL |
| description | TEXT | No | Deskripsi temuan cacat |
| severity | ENUM | No | LOW, MEDIUM, HIGH, CRITICAL |
| status | ENUM | No | OPEN, IN_REWORK, RESOLVED, ACCEPTED |
| resolution | TEXT | Yes | Tindakan perbaikan yang telah diambil |
| resolved_at | TIMESTAMP | Yes | Waktu defek diselesaikan |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 14. `payments`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| order_id | BIGINT UNSIGNED | No | Foreign Key -> orders(id) ON DELETE CASCADE |
| type | ENUM | No | DP, PARTIAL, FINAL, OTHER |
| amount | DECIMAL(15, 2) | No | Nilai pembayaran |
| payment_date | DATE | No | Tanggal bayar |
| method | VARCHAR(100) | No | Transfer Bank, Tunai, dll |
| reference | VARCHAR(255) | Yes | Bukti transfer / Nomor referensi |
| notes | TEXT | Yes | Catatan tambahan pembayaran |
| confirmed_by | BIGINT UNSIGNED | Yes | Foreign Key -> users(id) |
| confirmed_at | TIMESTAMP | Yes | Waktu verifikasi pembayaran |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 15. `shipping`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| order_id | BIGINT UNSIGNED | No | Foreign Key -> orders(id) ON DELETE CASCADE |
| courier | VARCHAR(100) | No | Nama ekspedisi / Driver workshop |
| tracking_number | VARCHAR(255) | Yes | Nomor resi pengiriman |
| shipping_address | TEXT | No | Alamat pengiriman barang |
| shipped_at | TIMESTAMP | Yes | Waktu barang diberangkatkan |
| estimated_arrival | DATE | Yes | Estimasi barang sampai |
| delivered_at | TIMESTAMP | Yes | Waktu barang diterima pelanggan |
| status | ENUM | No | PENDING, READY, SHIPPED, DELIVERED |
| notes | TEXT | Yes | Instruksi pengiriman / nomor driver |
| created_at, updated_at | TIMESTAMP | Yes | Timestamps |

### 16. `activity_logs`
| Field | Type | Nullable | Notes / Constraints |
|---|---|---|---|
| id | BIGINT UNSIGNED | No | Primary Key |
| workshop_id | BIGINT UNSIGNED | No | Foreign Key -> workshops(id) ON DELETE CASCADE |
| user_id | BIGINT UNSIGNED | Yes | Foreign Key -> users(id) ON DELETE SET NULL |
| order_id | BIGINT UNSIGNED | Yes | Foreign Key -> orders(id) ON DELETE SET NULL |
| action | VARCHAR(100) | No | e.g. ORDER_CREATED, STAGE_COMPLETED |
| entity_type | VARCHAR(100) | No | Model class / nama entitas |
| entity_id | BIGINT UNSIGNED | No | ID entitas |
| description | TEXT | No | Penjelasan log aktivitas |
| metadata | JSON | Yes | Snapshot konteks data |
| created_at | TIMESTAMP | No | Timestamp pencatatan (tanpa updated_at) |
