import React, { useEffect, useState } from 'react';
import {
  getShipping,
  createShipping,
  updateShipping,
} from '../../../services/shippingService';

export default function ShippingSection({ order, role, onShippingUpdated }) {
  const isAuthorized = role === 'OWNER' || role === 'ADMIN';

  const [shipping, setShipping] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Form mode for create / edit
  const [formMode, setFormMode] = useState(false);
  const [formData, setFormData] = useState({
    courier: 'Armada Bengkel',
    tracking_number: '',
    shipping_address: '',
    estimated_arrival: '',
    notes: '',
    status: '',
  });

  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState(null);

  const [reloadTrigger, setReloadTrigger] = useState(0);

  const reloadShipping = () => {
    setReloadTrigger((prev) => prev + 1);
  };

  useEffect(() => {
    let ignore = false;

    getShipping(order.id)
      .then((res) => {
        if (!ignore) {
          if (res?.success) {
            setShipping(res.data);
            if (res.data) {
              setFormData({
                courier: res.data.courier || 'Armada Bengkel',
                tracking_number: res.data.tracking_number || '',
                shipping_address: res.data.shipping_address || '',
                estimated_arrival: res.data.estimated_arrival || '',
                notes: res.data.notes || '',
                status: res.data.status || '',
              });
            } else {
              // Preload default address from customer if available
              setFormData((prev) => ({
                ...prev,
                shipping_address: order.customer?.address || '',
              }));
            }
            setError(null);
          }
          setLoading(false);
        }
      })
      .catch((err) => {
        if (!ignore) {
          setError(err.response?.data?.message || 'Gagal memuat data pengiriman.');
          setLoading(false);
        }
      });

    return () => {
      ignore = true;
    };
  }, [order.id, order.customer, reloadTrigger]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setFormError(null);

    if (!formData.courier.trim()) {
      setFormError('Nama kurir / ekspedisi wajib diisi.');
      return;
    }
    if (!formData.shipping_address.trim()) {
      setFormError('Alamat pengiriman wajib diisi.');
      return;
    }

    setSubmitting(true);
    try {
      const payload = {
        courier: formData.courier.trim(),
        tracking_number: formData.tracking_number.trim() || null,
        shipping_address: formData.shipping_address.trim(),
        estimated_arrival: formData.estimated_arrival || null,
        notes: formData.notes.trim() || null,
      };

      if (shipping) {
        // Update payload can include status
        if (formData.status) {
          payload.status = formData.status;
        }
        await updateShipping(order.id, payload);
      } else {
        await createShipping(order.id, payload);
      }

      setFormMode(false);
      reloadShipping();
      if (onShippingUpdated) onShippingUpdated();
    } catch (err) {
      if (err.response?.status === 422 && err.response?.data?.errors) {
        const errObj = err.response.data.errors;
        const msg = Object.values(errObj).flat().join(' ');
        setFormError(msg || 'Validasi pengiriman gagal.');
      } else {
        setFormError(err.response?.data?.message || 'Gagal menyimpan data pengiriman.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const getShippingBadgeClass = (status) => {
    switch (status) {
      case 'PENDING':
        return 'badge-shipping-pending';
      case 'READY':
        return 'badge-shipping-ready';
      case 'SHIPPED':
        return 'badge-shipping-shipped';
      case 'DELIVERED':
        return 'badge-shipping-delivered';
      default:
        return '';
    }
  };

  return (
    <div className="operational-module-card">
      <div className="operational-module-header">
        <div>
          <h2 className="operational-module-title">Manajemen Pengiriman (Shipping)</h2>
          <p className="operational-module-desc">
            Informasi ekspedisi, armada bengkel sendiri, dan alamat tujuan pengantaran. Transisi READY_TO_SHIP &rarr; SHIPPED mewajibkan data pengiriman telah terisi.
          </p>
        </div>
      </div>

      {error && <div className="alert-error" style={{ marginBottom: '1rem' }}>{error}</div>}
      {formError && <div className="alert-error" style={{ marginBottom: '1rem' }}>{formError}</div>}

      {loading ? (
        <p className="loading-text">Memuat data pengiriman...</p>
      ) : formMode ? (
        /* Form create / update */
        <form onSubmit={handleSubmit} className="shipping-form">
          <div className="form-row">
            <div className="form-group" style={{ flex: 1 }}>
              <label>Kurir / Armada Pengantaran <span className="req">*</span></label>
              <input
                type="text"
                className="form-input"
                placeholder="Armada Bengkel / JNE Trucking / Dakota Cargo"
                value={formData.courier}
                onChange={(e) => setFormData({ ...formData, courier: e.target.value })}
                required
                disabled={submitting}
              />
              <span className="field-hint">
                Gunakan "Armada Bengkel" untuk pengantaran mandiri atau pengambilan workshop.
              </span>
            </div>

            <div className="form-group" style={{ flex: 1 }}>
              <label>Nomor Resi / Pelacakan (Opsional)</label>
              <input
                type="text"
                className="form-input"
                placeholder="Kosongkan jika menggunakan armada sendiri"
                value={formData.tracking_number}
                onChange={(e) => setFormData({ ...formData, tracking_number: e.target.value })}
                disabled={submitting}
              />
            </div>
          </div>

          <div className="form-group">
            <label>Alamat Tujuan Pengiriman <span className="req">*</span></label>
            <textarea
              rows="2"
              className="form-input"
              placeholder="Alamat lengkap penerima mebel"
              value={formData.shipping_address}
              onChange={(e) => setFormData({ ...formData, shipping_address: e.target.value })}
              required
              disabled={submitting}
            />
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>Estimasi Tiba (Opsional)</label>
              <input
                type="date"
                className="form-input"
                value={formData.estimated_arrival}
                onChange={(e) => setFormData({ ...formData, estimated_arrival: e.target.value })}
                disabled={submitting}
              />
            </div>

            {shipping && (
              <div className="form-group">
                <label>Status Pengiriman</label>
                <select
                  className="form-input"
                  value={formData.status}
                  onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                  disabled={submitting}
                >
                  <option value="PENDING">Menunggu Pengiriman (PENDING)</option>
                  <option value="READY">Siap Dikirim (READY)</option>
                  <option value="SHIPPED">Dalam Pengiriman (SHIPPED)</option>
                  <option value="DELIVERED">Terkirim / Diterima (DELIVERED)</option>
                </select>
              </div>
            )}
          </div>

          <div className="form-group">
            <label>Catatan Pengiriman (Opsional)</label>
            <input
              type="text"
              className="form-input"
              placeholder="Instruksi sopir, kontak penerima di lokasi, dsb."
              value={formData.notes}
              onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
              disabled={submitting}
            />
          </div>

          <div style={{ display: 'flex', gap: '0.75rem', justifyContent: 'flex-end', marginTop: '1rem' }}>
            <button
              type="button"
              onClick={() => setFormMode(false)}
              className="btn btn-secondary"
              disabled={submitting}
            >
              Batal
            </button>
            <button
              type="submit"
              className="btn btn-primary"
              disabled={submitting}
            >
              {submitting ? 'Menyimpan...' : shipping ? 'Perbarui Pengiriman' : 'Simpan Data Pengiriman'}
            </button>
          </div>
        </form>
      ) : shipping ? (
        /* Shipping Overview Card */
        <div className="shipping-overview-box">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: '0.75rem' }}>
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.35rem' }}>
                <span className={`badge-shipping ${getShippingBadgeClass(shipping.status)}`}>
                  {shipping.status_label || shipping.status}
                </span>
                <strong style={{ fontSize: '1.05rem', color: 'var(--color-text-primary)' }}>
                  {shipping.courier}
                </strong>
              </div>
              <span style={{ fontSize: '0.875rem', color: 'var(--color-text-secondary)' }}>
                No. Resi: <strong>{shipping.tracking_number || '(Armada Mandiri / Tanpa Resi)'}</strong>
              </span>
            </div>

            {isAuthorized && (
              <button
                type="button"
                onClick={() => setFormMode(true)}
                className="btn btn-secondary btn-sm"
              >
                ✏️ Edit Data Pengiriman
              </button>
            )}
          </div>

          <div className="shipping-details-grid" style={{ marginTop: '1.25rem' }}>
            <div className="shipping-detail-item">
              <span className="detail-label">Alamat Pengiriman:</span>
              <p className="detail-address">{shipping.shipping_address}</p>
            </div>

            <div className="shipping-detail-item">
              <span className="detail-label">Estimasi Tiba:</span>
              <span>{shipping.estimated_arrival || '-'}</span>
            </div>

            <div className="shipping-detail-item">
              <span className="detail-label">Waktu Diberangkatkan (Shipped At):</span>
              <span>{shipping.shipped_at ? new Date(shipping.shipped_at).toLocaleString('id-ID') : 'Belum berangkat'}</span>
            </div>

            <div className="shipping-detail-item">
              <span className="detail-label">Waktu Diterima (Delivered At):</span>
              <span>{shipping.delivered_at ? new Date(shipping.delivered_at).toLocaleString('id-ID') : '-'}</span>
            </div>
          </div>

          {shipping.notes && (
            <div className="shipping-notes-box" style={{ marginTop: '1rem' }}>
              <span className="detail-label">Catatan:</span> {shipping.notes}
            </div>
          )}
        </div>
      ) : (
        /* Empty Shipping State */
        <div className="shipping-empty-box">
          <p>Belum ada data pengiriman untuk pesanan ini.</p>
          <span style={{ display: 'block', fontSize: '0.8125rem', color: 'var(--color-text-muted)', margin: '0.25rem 0 0.75rem' }}>
            Data pengiriman wajib dilengkapi sebelum pesanan dipindahkan ke status SHIPPED.
          </span>
          {isAuthorized && (
            <button
              type="button"
              onClick={() => setFormMode(true)}
              className="btn btn-primary"
            >
              + Buat Data Pengiriman
            </button>
          )}
        </div>
      )}
    </div>
  );
}
