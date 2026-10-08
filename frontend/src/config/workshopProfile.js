/**
 * Public Workshop Profile
 * ------------------------------------------------------------------
 * TATAMEBEL adalah platform. Workshop adalah pemilik etalase, produk,
 * dan branding. Identitas workshop TIDAK di-hardcode di dalam komponen;
 * semuanya dibaca dari sini.
 *
 * Saat ini backend belum menyediakan endpoint profil workshop publik,
 * sehingga nilai dibaca dari environment variable Vite (frontend/.env):
 *
 *   VITE_WORKSHOP_NAME="Nama Workshop"
 *   VITE_WORKSHOP_TAGLINE="Deskripsi singkat workshop"
 *   VITE_WORKSHOP_WHATSAPP="6281xxxxxxxxx"
 *
 * Jika belum diisi, dipakai placeholder netral — BUKAN data bisnis karangan.
 * Nantinya modul ini dapat diganti dengan data dari API profil workshop
 * tanpa mengubah komponen landing page.
 */

const env = import.meta.env;

function normalizeWhatsAppNumber(raw) {
  if (!raw) return '';
  const digits = String(raw).replace(/[^0-9]/g, '');
  if (!digits) return '';
  // Sama dengan pola WorkshopContactCard: 08xx -> 628xx
  return digits.startsWith('0') ? `62${digits.substring(1)}` : digits;
}

const workshopProfile = {
  name: (env.VITE_WORKSHOP_NAME || '').trim() || 'Workshop Mebel',
  tagline:
    (env.VITE_WORKSHOP_TAGLINE || '').trim() ||
    'Mebel kustom yang dikerjakan sesuai ukuran, kebutuhan, dan karakter ruang Anda.',
  whatsappNumber: normalizeWhatsAppNumber(env.VITE_WORKSHOP_WHATSAPP),
};

export const isWhatsAppConfigured = Boolean(workshopProfile.whatsappNumber);

export default workshopProfile;
