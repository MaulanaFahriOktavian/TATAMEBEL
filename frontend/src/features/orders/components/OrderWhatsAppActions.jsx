import React, { useState } from 'react';
import { getWhatsAppShareData } from '../services/orderService';

/**
 * OrderWhatsAppActions Component
 *
 * Provides WhatsApp sharing and public tracking link copy actions for authorized staff (OWNER/ADMIN).
 *
 * @param {{ order: object, role: string|null }} props
 */
export default function OrderWhatsAppActions({ order, role }) {
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);
  const [copied, setCopied] = useState(false);

  // 1. Strictly restrict visibility: only OWNER and ADMIN can see/use WhatsApp actions
  const isAuthorized = role === 'OWNER' || role === 'ADMIN';
  if (!isAuthorized || !order) {
    return null;
  }

  // Handle WhatsApp action: request API -> receive URL -> open in new window
  const handleShareWhatsApp = async () => {
    setLoading(true);
    setErrorMessage(null);

    try {
      const result = await getWhatsAppShareData(order.id);
      if (result?.data?.url) {
        window.open(result.data.url, '_blank', 'noopener,noreferrer');
      } else {
        setErrorMessage('Tautan WhatsApp tidak ditemukan dalam respons.');
      }
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.response?.data?.errors?.phone?.[0] ||
        err.response?.data?.errors?.order?.[0] ||
        'Gagal menyiapkan data WhatsApp. Pastikan nomor telepon pelanggan valid.';
      setErrorMessage(message);
    } finally {
      setLoading(false);
    }
  };

  // Handle Copy Tracking Link action: copies authenticated tracking_url or constructs tracking URL
  const handleCopyTrackingLink = async () => {
    const trackingUrl =
      order.tracking_url ||
      (order.public_token ? `${window.location.origin}/track/${order.public_token}` : null);

    if (!trackingUrl) {
      setErrorMessage('Tautan pelacakan belum tersedia untuk pesanan ini.');
      return;
    }

    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(trackingUrl);
      } else {
        // Fallback for non-secure contexts
        const textarea = document.createElement('textarea');
        textarea.value = trackingUrl;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
      }

      setCopied(true);
      setErrorMessage(null);
      setTimeout(() => {
        setCopied(false);
      }, 2500);
    } catch {
      setErrorMessage('Gagal menyalin tautan ke papan klip.');
    }
  };

  return (
    <div className="order-whatsapp-actions" style={{ marginTop: '1.25rem' }}>
      <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.75rem', alignItems: 'center' }}>
        <button
          type="button"
          onClick={handleShareWhatsApp}
          disabled={loading}
          className="btn btn-whatsapp"
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '0.5rem',
            padding: '0.5rem 1rem',
            backgroundColor: '#166534',
            color: '#ffffff',
            border: 'none',
            borderRadius: 'var(--radius-md, 6px)',
            fontSize: '0.875rem',
            fontWeight: 500,
            cursor: loading ? 'not-allowed' : 'pointer',
            opacity: loading ? 0.7 : 1,
            transition: 'background-color 0.15s ease',
          }}
        >
          <span>{loading ? 'Menyiapkan WhatsApp...' : 'Bagikan via WhatsApp'}</span>
        </button>

        <button
          type="button"
          onClick={handleCopyTrackingLink}
          className="btn btn-copy"
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '0.5rem',
            padding: '0.5rem 1rem',
            backgroundColor: '#ffffff',
            color: 'var(--color-text-primary, #0f172a)',
            border: '1px solid var(--color-border, #e2e8f0)',
            borderRadius: 'var(--radius-md, 6px)',
            fontSize: '0.875rem',
            fontWeight: 500,
            cursor: 'pointer',
            transition: 'background-color 0.15s ease',
          }}
        >
          <span>{copied ? 'Link tracking disalin.' : 'Salin Link Tracking'}</span>
        </button>

        {copied && (
          <span
            style={{
              fontSize: '0.8125rem',
              color: '#15803d',
              fontWeight: 500,
            }}
          >
            ✓ Link tracking disalin.
          </span>
        )}
      </div>

      {errorMessage && (
        <div
          style={{
            marginTop: '0.75rem',
            padding: '0.5rem 0.75rem',
            backgroundColor: 'var(--color-danger-bg, #fee2e2)',
            border: '1px solid #fca5a5',
            borderRadius: 'var(--radius-sm, 4px)',
            color: 'var(--color-danger, #b91c1c)',
            fontSize: '0.8125rem',
          }}
        >
          {errorMessage}
        </div>
      )}
    </div>
  );
}
