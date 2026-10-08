import workshopProfile from '../../config/workshopProfile';

/**
 * Build a manual WhatsApp click-to-chat link (wa.me).
 * Tidak menggunakan WhatsApp Business API.
 *
 * Jika nomor workshop belum dikonfigurasi, link tetap valid
 * (https://wa.me/?text=...) — WhatsApp akan membuka pilihan kontak
 * dengan pesan yang sudah terisi.
 */
export function buildWhatsAppUrl(message) {
  const text = encodeURIComponent(message);
  const number = workshopProfile.whatsappNumber;
  return number ? `https://wa.me/${number}?text=${text}` : `https://wa.me/?text=${text}`;
}

export const WA_MESSAGES = {
  general:
    'Halo, saya ingin berkonsultasi mengenai pembuatan mebel kustom. Mohon informasi mengenai ukuran, material, dan finishing yang tersedia.',
  custom:
    'Halo, saya memiliki desain/ide mebel sendiri dan ingin mengonsultasikan kemungkinan pembuatannya.',
  product: (productName) =>
    `Halo, saya tertarik dengan model ${productName} ini. Saya ingin menanyakan ukuran, harga, dan opsi customnya.`,
};

/**
 * Extract a tracking code from user input.
 * Menerima kode mentah ATAU tautan lengkap yang dikirim workshop
 * (mis. https://.../track/AbC123...).
 *
 * Kode pelacakan dibuat backend dengan Str::random(40) — alfanumerik.
 * Validasi di sini hanya format; keberadaan pesanan tetap dicek oleh
 * Customer Portal yang sudah ada.
 *
 * @returns {{ code: string|null, error: string|null }}
 */
export function parseTrackingInput(rawInput) {
  const input = (rawInput || '').trim();

  if (!input) {
    return { code: null, error: 'Masukkan kode pelacakan terlebih dahulu.' };
  }

  let candidate = input;
  const marker = '/track/';
  const markerIndex = input.indexOf(marker);
  if (markerIndex !== -1) {
    candidate = input.substring(markerIndex + marker.length);
  }
  candidate = candidate.split(/[?#/\s]/)[0];

  if (!/^[A-Za-z0-9]{20,64}$/.test(candidate)) {
    return {
      code: null,
      error:
        'Kode pelacakan tidak valid. Periksa kembali kode atau tautan yang dikirim workshop melalui WhatsApp.',
    };
  }

  return { code: candidate, error: null };
}
