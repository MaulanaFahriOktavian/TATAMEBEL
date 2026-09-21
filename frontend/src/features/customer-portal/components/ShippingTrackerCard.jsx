import React, { useState } from 'react';

/**
 * ShippingTrackerCard component displaying courier and delivery tracking details.
 */
export default function ShippingTrackerCard({ shipping }) {
  const [copied, setCopied] = useState(false);

  if (!shipping) {
    return null;
  }

  const handleCopy = () => {
    if (shipping.tracking_number) {
      navigator.clipboard.writeText(shipping.tracking_number);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  const shippedDateStr = shipping.shipped_at
    ? new Date(shipping.shipped_at).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
      })
    : null;

  return (
    <div className="portal-card">
      <div className="portal-card-header">
        <span className="portal-card-title">Pengiriman</span>
        <span className="stage-badge in_progress" style={{ fontSize: '0.75rem' }}>
          {shipping.status_label || shipping.status}
        </span>
      </div>

      <div className="shipping-row">
        <span style={{ color: '#64748b' }}>Ekspedisi / Kurir</span>
        <span style={{ fontWeight: 600 }}>{shipping.courier || 'Kurir Workshop'}</span>
      </div>

      {shipping.tracking_number && (
        <div className="shipping-row">
          <span style={{ color: '#64748b' }}>Nomor Resi</span>
          <div style={{ display: 'flex', alignItems: 'center' }}>
            <span style={{ fontWeight: 700, fontFamily: 'monospace' }}>
              {shipping.tracking_number}
            </span>
            <button className="copy-btn" onClick={handleCopy} title="Salin nomor resi">
              {copied ? 'Tersalin' : 'Salin'}
            </button>
          </div>
        </div>
      )}

      {shippedDateStr && (
        <div className="shipping-row">
          <span style={{ color: '#64748b' }}>Tanggal Kirim</span>
          <span>{shippedDateStr}</span>
        </div>
      )}

      {shipping.estimated_arrival && (
        <div className="shipping-row">
          <span style={{ color: '#64748b' }}>Estimasi Tiba</span>
          <span style={{ fontWeight: 600, color: '#b45309' }}>
            {new Date(shipping.estimated_arrival).toLocaleDateString('id-ID', {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
            })}
          </span>
        </div>
      )}
    </div>
  );
}
