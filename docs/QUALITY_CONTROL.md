# TATAMEBEL — QUALITY CONTROL & DEFECT TRACKING
## Arsitektur & Aturan Bisnis Modul QC (Phase 5)

Dokumen ini merupakan panduan arsitektural dan acuan teknis resmi mengenai implementasi **Quality Control (QC) & Defect Tracking** pada TATAMEBEL.

---

## 1. Filosofi & Peran Bisnis Modul QC

Dalam bengkel mebel pesanan khusus (*custom furniture*), kualitas produk adalah penentu reputasi dan kelangsungan bisnis bengkel. Pengiriman produk cacat (misalnya retak konstruksi, salah ukuran, pernis belang, atau laci seret) ke tangan pembeli berakibat pada biaya retur yang sangat mahal, komplain WhatsApp, dan rusaknya kepercayaan pelanggan.

Modul QC TATAMEBEL bertindak sebagai **gerbang pemeriksaan mutu otoritatif** (*authoritative quality gate*) yang memisahkan fase produksi perkayuan (*in-production*) dari fase pengemasan dan pengiriman (*packing & shipping*).

---

## 2. Checklist Pemeriksaan Mutu (9 Kategori SDD)

Sesi inspeksi QC diinisialisasi dengan 9 butir checklist standar industri mebel yang dikonfigurasi pada `config/qc.php`:

1. **`dimension`**: Kesesuaian dimensi panjang, lebar, dan tinggi fisik dengan spesifikasi yang telah dikunci (`LOCKED`).
2. **`material`**: Kesesuaian jenis kayu (jati, mahoni, sungkai, dll.), grade mutu kayu, dan tingkat kadar air (*moisture content*).
3. **`construction`**: Kekokohan rangka, kekuatan sambungan purus/dowel/tenon, ketiadaan goyangan, dan kekencangan baut/sekrup.
4. **`surface`**: Kehalusan amplas permukaan, ketiadaan goresan kasar, kerapian dempul, dan ketiadaan mata kayu mati yang lepas.
5. **`finishing`**: Kerapian lapisan cat/politur/melamine, ketiadaan lelehan (*runs*), gelembung (*blistering*), atau efek kulit jeruk.
6. **`color`**: Kesesuaian warna akhir cat/politur dan kain pelapis jok (*upholstery*) dengan sampel yang disepakati.
7. **`quantity`**: Kelengkapan kuantitas unit produk dan seluruh komponen lepasan/rakitan.
8. **`accessories`**: Pemasangan dan fungsi hardware (rel laci *soft-close*, engsel sendok, handle pintu, kunci, bantalan kaki/glides).
9. **`packaging`**: Kesiapan dan standar proteksi kemasan (karton pelindung sudut, single face, bubble wrap).

Setiap butir checklist saat inspeksi baru dibuat berstatus `null` (**belum dinilai**).
Inspektur mengevaluasi setiap butir menjadi:
- `PASS`: Memenuhi standar mutu.
- `FAIL`: Terdapat ketidaksesuaian/cacat (wajib dicatat pada daftar defek).
- `NA`: Tidak berlaku untuk item mebel ini (misal poin kain jok untuk meja kayu tanpa busa).

---

## 3. Immutability & Riwayat Re-inspeksi

Untuk menjamin integritas audit dan mencegah manipulasi data mutu:

- **Fase DRAFT (`status = PENDING`):**
  Inspeksi dapat diedit, butir checklist dapat dinilai (`PASS`/`FAIL`/`NA`), catatan teknis dapat ditambahkan, temuan cacat dapat dicatat, dan foto bukti dapat diunggah.
- **Fase FINALISASI (`PASSED`, `REWORK`, `FAILED`):**
  Saat endpoint `/finalize` dipanggil, timestamp `inspected_at` dicatat dan inspeksi **terkunci permanen (IMMUTABLE)**.
  Percobaan memodifikasi, mengevaluasi ulang butir checklist, atau menghapus defek pada inspeksi yang telah difinalisasi akan ditolak oleh backend dengan **HTTP 422**.
- **Alur Re-inspeksi:**
  Jika hasil inspeksi adalah `REWORK`, perbaikan fisik dilakukan oleh tim produksi. Setelah perbaikan selesai, verifikasi kelulusan dicatat melalui **sesi inspeksi baru (Re-inspection)**. Sistem tidak menimpa (*overwrite*) inspeksi sebelumnya, sehingga bengkel memiliki rekaman historis lengkap mengenai riwayat pengerjaan ulang (*rework rate*).

---

## 4. Alur Penanganan Inspeksi Gagal (FAILED Workflow)

Jika inspeksi menghasilkan status `FAILED`:
1. **Status Pesanan Tetap di `QC`:** Pesanan tidak berpindah ke status baru dan dilarang dimundurkan ke `IN_PRODUCTION` (`QC -> IN_PRODUCTION` tidak ada pada state machine).
2. **Gerbang PACKING Tertutup Rapat:** Pesanan tidak dapat dimajukan ke `PACKING` (ditolak HTTP 422) karena inspeksi terbaru belum berstatus `PASSED`.
3. **Tindakan Lanjutan Workshop:** Manajemen workshop (Owner/Admin) dan tim produksi melakukan evaluasi teknis menyeluruh (misalnya pembuatan ulang komponen yang gagal fatal, penyesuaian material, atau mediasi teknis).
4. **Verifikasi via Re-Inspeksi Baru:** Setelah tindakan perbaikan/pembuatan ulang selesai secara fisik, tim QC membuat sesi **Re-inspeksi Baru** (`POST /api/v1/orders/{orderId}/qc-inspections`).
5. **Tidak Dead-End:** Begitu re-inspeksi baru tersebut dievaluasi dan difinalisasi sebagai `PASSED` (serta seluruh defek telah terselesaikan), gerbang menuju `PACKING` terbuka kembali secara otomatis.

---

## 5. Siklus Hidup Temuan Cacat (Defect Lifecycle)

Temuan cacat dicatat pada tabel `qc_defects` dengan tingkat keparahan (*severity*):
- `LOW`: Cacat kosmetik sangat minor yang mudah di-touch-up.
- `MEDIUM`: Cacat permukaan atau hardware yang memerlukan perbaikan terfokus.
- `HIGH`: Cacat fungsional atau konstruksi yang memerlukan pembongkaran sebagian komponen.
- `CRITICAL`: Deviasi spesifikasi fatal atau kegagalan struktural utama.

### Alur Status Defek:

```text
       [PENEMUAN CACAT]
              │
              ▼
           [OPEN]
          ╱      ╲
         ╱        ╲ (Persetujuan Owner / Admin)
        ▼          ▼
  [IN_REWORK]  [ACCEPTED] ◄── Concession / Toleransi (Hanya OWNER / ADMIN)
        │
        ▼
   [RESOLVED] ◄── Diselesaikan tukang dengan kewajiban mengisi catatan resolusi
        │
        ▼
Verifikasi via Re-Inspection ──► [PASSED]
```

1. `OPEN`: Cacat ditemukan dan dicatat oleh inspektur QC/Admin/Owner saat inspeksi draft.
2. `IN_REWORK`: Tukang atau kepala produksi mengambil alih barang untuk memulai perbaikan fisik.
3. `RESOLVED`: Perbaikan fisik selesai. Staf produksi **wajib** mengisi teks `resolution` (penjelasan tindakan korektif nyata yang telah dilakukan).
4. `ACCEPTED`: Cacat diterima apa adanya sebagai toleransi artistik/diskon/kesepakatan pembeli (misal: corak alami serat kayu jati).
   - **Aturan Otoritas:** Hanya peran `OWNER` dan `ADMIN` yang berhak menyetujui status `ACCEPTED`. Staf produksi dan QC dilarang (HTTP 403).
   - **Aturan Finalitas:** Defek yang sudah berstatus `ACCEPTED` bersifat final dan dilarang dikembalikan ke status `OPEN`, `IN_REWORK`, atau `RESOLVED` (HTTP 422). Wajib memiliki catatan justifikasi pada kolom `resolution`.

---

## 6. Gerbang Validasi Status Pesanan (QC → PACKING Gate)

Pada `OrderService::changeStatus`, transisi status pesanan dari `QC` ke `PACKING` dijaga oleh 7 aturan validasi ketat di sisi backend (*backend authoritative*):

1. **Wajib Memiliki Riwayat Inspeksi:** Pesanan harus memiliki minimal 1 sesi inspeksi QC.
2. **Inspeksi Terakhir Wajib Lulus (`PASSED`):** Status inspeksi QC terbaru harus `PASSED`.
3. **Seluruh Checklist Dinilai:** Seluruh butir checklist pada inspeksi lulus tersebut wajib telah dievaluasi (`PASS` atau `NA`), tidak boleh ada yang tersisa `null` (belum dinilai) atau `FAIL`.
4. **Nol Defek Terbuka:** Tidak boleh ada temuan cacat pada pesanan yang masih berstatus `OPEN` atau `IN_REWORK`. Seluruh defek wajib telah berstatus `RESOLVED` atau `ACCEPTED`.
5. **Tahapan Produksi QC Selesai:** Tahapan produksi Sequence 7 (`QC`) dipastikan berstatus `COMPLETED`.
6. **Order State Machine Invariant:** Status pesanan tidak dapat dimundurkan dari `QC` ke `IN_PRODUCTION`. Pengerjaan ulang berjalan di bawah status pesanan `QC`.

---

## 7. Sinkronisasi dengan Tahapan Produksi (Sequence 7: QC)

- **Transisi Otomatis ke IN_PROGRESS:** Saat sesi inspeksi QC pertama kali dibuat (`POST /qc-inspections`), tahapan produksi Sequence 7 (`QC`) yang masih `PENDING` secara otomatis berpindah menjadi `IN_PROGRESS` (`started_at = now()`).
- **Guard Phase 4 Tetap Aktif:** Staf `PRODUCTION` dilarang menyelesaikan tahapan QC secara manual melalui endpoint tracking produksi biasa (HTTP 422).
- **Penyelesaian Otomatis saat Lulus:** Saat inspeksi QC difinalisasi sebagai `PASSED`, `QcService` secara otomatis memanggil metode boundary:
  `ProductionService::completeQcStageFromInspection($order, $actor)`.
- Selesainya tahapan QC secara otomatis memicu pembaruan telemetry progres produksi (misal 7/8 tahapan selesai = 87.5%).

---

## 8. Foto Bukti QC & Media Immutability

- Foto bukti dapat dikaitkan langsung dengan sesi inspeksi (`qc_inspection_id`) maupun temuan cacat spesifik (`qc_defect_id`).
- Menggunakan arsitektur `MediaService` terpadu dengan isolasi storage per workshop:
  `workshops/{workshop_id}/orders/{order_id}/media/`
- **Media Immutability:** Unggahan foto bukti pada sesi inspeksi atau defek yang telah difinalisasi (`PASSED`, `REWORK`, `FAILED`) **ditolak dengan HTTP 422**. Foto tambahan setelah perbaikan fisik wajib dimasukkan ke dalam sesi re-inspeksi baru.
- **Visibilitas:** Default adalah `INTERNAL` (hanya dapat dilihat staf workshop). Foto hasil akhir yang telah lulus QC dapat diatur sebagai `CUSTOMER` agar dapat ditampilkan pada Customer Progress Portal (Phase 6).

---

## 9. Matriks Hak Akses (Role-Based Access Control)

| Aksi | OWNER | ADMIN | PRODUCTION | QC |
|---|:---:|:---:|:---:|:---:|
| **Lihat Inspeksi & Defek** | Ya | Ya | Ya | Ya |
| **Buat Sesi Inspeksi Baru** | Ya | Ya | Tidak (403) | Ya |
| **Evaluasi Checklist (Draft)** | Ya | Ya | Tidak (403) | Ya |
| **Finalisasi Inspeksi (`PASSED`/`REWORK`/`FAILED`)** | Ya | Ya | Tidak (403) | Ya |
| **Catat Temuan Cacat (Defect)** | Ya | Ya | Tidak (403) | Ya |
| **Ambil Alih Rework (`IN_REWORK`)** | Ya | Ya | Ya | Ya |
| **Selesaikan Rework (`RESOLVED` + Catatan)** | Ya | Ya | Ya | Ya |
| **Persetujuan Toleransi (`ACCEPTED` + Justifikasi)** | **Ya** | **Ya** | **Tidak (403)** | **Tidak (403)** |
| **Hapus Defek (Hanya saat Draft/Pending)** | Ya | Ya | Tidak (403) | Ya |
| **Unggah Foto Bukti QC (Hanya Draft/Pending)** | Ya | Ya | Tidak (403) | Ya |
| **Unggah Foto Bukti Defek (Hanya Draft/Pending)** | Ya | Ya | Ya | Ya |
| **Ubah Status Pesanan ke PACKING** | Ya | Ya | Tidak (403) | Tidak (403) |

---

## 10. Catatan Terkait Modul Pembayaran (Payment Scope)

> [!NOTE]
> Payment tracking belum diimplementasikan dan akan ditangani pada fase berikutnya sesuai Development Plan. Fase 5 strictly berfokus pada inspeksi mutu, penelusuran defek, dan gerbang validasi operasional bengkel.
