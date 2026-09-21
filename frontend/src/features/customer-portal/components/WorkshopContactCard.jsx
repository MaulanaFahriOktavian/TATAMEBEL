import React from 'react';

/**
 * WorkshopContactCard component offering direct WhatsApp / phone contact with workshop.
 */
export default function WorkshopContactCard({ workshop, orderNumber }) {
  if (!workshop) return null;

  const phone = workshop.phone ? workshop.phone.replace(/[^0-9]/g, '') : '';
  const waNumber = phone.startsWith('0') ? '62' + phone.substring(1) : phone;

  const waText = encodeURIComponent(
    `Halo ${workshop.name}, saya ingin menanyakan mengenai progres pesanan saya dengan nomor ${orderNumber || ''}.`
  );

  const waUrl = phone ? `https://wa.me/${waNumber}?text=${waText}` : null;

  return (
    <div className="portal-card" style={{ backgroundColor: '#faf8f5', borderColor: '#e7ded4' }}>
      <div style={{ textAlign: 'center', marginBottom: '0.85rem' }}>
        <h3 style={{ fontSize: '0.9375rem', fontWeight: 700, color: '#1e293b' }}>
          Ada Pertanyaan Mengenai Pesanan Anda?
        </h3>
        <p style={{ fontSize: '0.8125rem', color: '#64748b', marginTop: '0.2rem' }}>
          Hubungi tim {workshop.name} langsung untuk koordinasi dan konsultasi pengerjaan.
        </p>
      </div>

      {waUrl && (
        <a
          href={waUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="contact-btn"
        >
          <span>💬</span>
          <span>Tanya Workshop via WhatsApp</span>
        </a>
      )}
    </div>
  );
}
