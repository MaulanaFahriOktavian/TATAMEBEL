import imgTable from '../../assets/landing/hero-dining.jpg';
import imgChair from '../../assets/landing/chair.jpg';
import imgWardrobe from '../../assets/landing/wardrobe.jpg';
import imgCredenza from '../../assets/landing/credenza.jpg';
import imgShelf from '../../assets/landing/shelf.jpg';
import imgDesk from '../../assets/landing/desk.jpg';

/**
 * Showcase / Etalase Data
 * ------------------------------------------------------------------
 * CONTOH TAMPILAN — BUKAN KATALOG RESMI.
 *
 * Backend belum memiliki tabel/endpoint katalog produk publik, jadi
 * etalase ini masih berupa data frontend. Foto adalah ilustrasi.
 *
 * Untuk mengganti dengan karya workshop sebenarnya:
 *   1. Ganti foto di src/assets/landing/
 *   2. Ubah daftar SHOWCASE_ITEMS di bawah
 *   3. Set SHOWCASE_IS_SAMPLE = false agar catatan "contoh" disembunyikan
 *
 * Struktur item sengaja dibuat sederhana agar kelak mudah dipetakan
 * dari response API (id, name, category, description, image, customizable).
 *
 * Catatan: field `material` sengaja tidak diisi. Jangan menambahkan
 * klaim material/jenis kayu kecuali memang data dari workshop.
 */

export const SHOWCASE_IS_SAMPLE = true;

export const SHOWCASE_CATEGORIES = [
  { id: 'meja', label: 'Meja' },
  { id: 'kursi', label: 'Kursi' },
  { id: 'lemari', label: 'Lemari' },
  { id: 'credenza', label: 'Credenza' },
  { id: 'rak', label: 'Rak' },
  { id: 'meja-kerja', label: 'Meja Kerja' },
];

export const SHOWCASE_ITEMS = [
  {
    id: 'sample-meja-makan',
    refCode: 'ART.01',
    name: 'Meja Makan Solid Minimalis',
    category: 'meja',
    subtitle: 'Meja santap keluarga dengan proporsi kaki ramping dan sambungan presisi.',
    description: 'Dibuat dengan kalkulasi bentang ruang yang matang. Lebar, panjang, dan ketinggian disesuaikan dengan postur pengguna dan kapasitas kursi Anda.',
    image: imgTable,
    customizable: ['Panjang & Lebar', 'Tinggi Meja', 'Tipe Sudut (Radius/Siku)', 'Pilihan Finishing'],
    highlight: 'Proporsi Fleksibel 4–10 Kursi',
    specsGuideline: {
      kapasitas: '4 hingga 10 dudukan kursi santap',
      konstruksi: 'Rangka tumpu kokoh dengan sambungan purus presisi',
      opsiDetail: 'Sudut membulat (soft radius) atau siku minimalis',
    },
  },
  {
    id: 'sample-kursi-santai',
    refCode: 'ART.02',
    name: 'Kursi Lounge Sandaran Anyam',
    category: 'kursi',
    subtitle: 'Kursi santai berstruktur solid dengan kemiringan ergonomis untuk sudut baca.',
    description: 'Rangka kayu kokoh dengan sudut kemiringan santai. Opsi dudukan dan sandaran dapat dikonfigurasikan sesuai selera interior ruang keluarga.',
    image: imgChair,
    customizable: ['Ketinggian Dudukan', 'Anyaman / Jok Busa', 'Finishing Rangka'],
    highlight: 'Ergonomi Ruang Santai',
    specsGuideline: {
      kapasitas: '1 dudukan santai personal',
      konstruksi: 'Sudut sandaran 105° ergonomis untuk relaksasi',
      opsiDetail: 'Anyaman serat alami atau bantalan busa kain linen',
    },
  },
  {
    id: 'sample-lemari-pakaian',
    refCode: 'ART.03',
    name: 'Lemari Pakaian Dua Pintu',
    category: 'lemari',
    subtitle: 'Lemari pakaian berprofil bersih dengan pembagian kompartemen modular.',
    description: 'Kompartemen dalam dirancang khusus mengikuti inventaris sandang Anda: rasio area gantung, laci tersembunyi, hingga ambalan lipat.',
    image: imgWardrobe,
    customizable: ['Dimensi Eksterior', 'Pembagian Rak & Laci', 'Tipe Handle & Engsel'],
    highlight: 'Internal Compartment Custom',
    specsGuideline: {
      kapasitas: 'Modul gantung panjang & rak simpan lipat',
      konstruksi: 'Sistem pintu berengsel soft-close atau geser',
      opsiDetail: 'Konfigurasi ambalan dapat dipindah (adjustable shelf)',
    },
  },
  {
    id: 'sample-credenza',
    refCode: 'ART.04',
    name: 'Credenza Bufet Geser Rendah',
    category: 'credenza',
    subtitle: 'Bufet kabinet multi-fungsi untuk ruang keluarga atau ruang santap.',
    description: 'Solusi penyimpanan rendah dengan rel pintu geser halus. Cocok untuk alas peranti audio-visual, pajangan seni, atau peranti makan keramik.',
    image: imgCredenza,
    customizable: ['Panjang Total', 'Jumlah Pintu Geser', 'Lubang Manajemen Kabel'],
    highlight: 'Smooth Sliding Panel',
    specsGuideline: {
      kapasitas: 'Penyimpanan tersembunyi & rak display terbuka',
      konstruksi: 'Rel geser kayu/aluminium halus tahan debu',
      opsiDetail: 'Kanal kabel tersembunyi untuk peranti media/TV',
    },
  },
  {
    id: 'sample-rak-terbuka',
    refCode: 'ART.05',
    name: 'Rak Buku & Display Terbuka',
    category: 'rak',
    subtitle: 'Struktur rak terbuka berpenampang simetris untuk partisi atau dinding.',
    description: 'Struktur kokoh tanpa beban visual berlebih. Jarak antar ambalan disesuaikan dengan dimensi koleksi buku, tembikar, atau tanaman hias Anda.',
    image: imgShelf,
    customizable: ['Jumlah Susun', 'Jarak Antar Ambalan', 'Lebar Penampang Kayu'],
    highlight: 'Modular Shelf Spacing',
    specsGuideline: {
      kapasitas: '4–6 susun display buku & dekorasi ruang',
      konstruksi: 'Penopang vertikal ganda berdaya tahan beban merata',
      opsiDetail: 'Bisa difungsikan sebagai partisi sekat ruangan',
    },
  },
  {
    id: 'sample-meja-kerja',
    refCode: 'ART.06',
    name: 'Meja Kerja Studio & Laci Ringkas',
    category: 'meja-kerja',
    subtitle: 'Meja kerja personal dengan integrasi laci simpanan esensial.',
    description: 'Dirancang untuk kenyamanan fokus harian di rumah maupun ruang kerja. Permukaan lapang dengan laci ceper untuk peranti kerja tetap rapi.',
    image: imgDesk,
    customizable: ['Ukuran Daun Meja', 'Posisi & Jumlah Laci', 'Kanal Jalur Kabel'],
    highlight: 'Fokus Kerja & Ergonomi',
    specsGuideline: {
      kapasitas: 'Area kerja laptop / desktop monitor & peranti tulis',
      konstruksi: 'Laci ceper dengan tarikan minimalis tersembunyi',
      opsiDetail: 'Grommet kayu estetik untuk manajemen kabel charger',
    },
  },
];
