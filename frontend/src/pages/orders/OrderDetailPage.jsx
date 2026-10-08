import React, { useEffect, useState, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import { getOrderDetail, changeOrderStatus } from '../../features/orders/services/orderService';
import OrderWhatsAppActions from '../../features/orders/components/OrderWhatsAppActions';
import SpecificationSection from '../../features/orders/components/SpecificationSection';
import ProductionSection from '../../features/orders/components/ProductionSection';
import QcSection from '../../features/orders/components/QcSection';
import ShippingSection from '../../features/orders/components/ShippingSection';

export default function OrderDetailPage() {
  const { id } = useParams();
  const { role, loading: authLoading } = useAuth();

  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Active module tab: 'SUMMARY' | 'SPEC' | 'PRODUCTION' | 'QC' | 'SHIPPING'
  const [activeTab, setActiveTab] = useState('SUMMARY');

  // Status transition state
  const [transitioning, setTransitioning] = useState(false);
  const [transitionError, setTransitionError] = useState(null);
  const [transitionSuccess, setTransitionSuccess] = useState(null);

  const [reloadTrigger, setReloadTrigger] = useState(0);

  const reloadOrder = useCallback(() => {
    setReloadTrigger((prev) => prev + 1);
  }, []);

  useEffect(() => {
    if (!id) return;
    let ignore = false;

    getOrderDetail(id)
      .then((res) => {
        if (!ignore) {
          if (res?.success) {
            setOrder(res.data);
            setError(null);
          } else {
            setError(res?.message || 'Gagal memuat detail pesanan.');
          }
          setLoading(false);
        }
      })
      .catch((err) => {
        if (!ignore) {
          const message =
            err.response?.status === 404
              ? 'Pesanan tidak ditemukan atau berada di luar workshop Anda.'
              : err.response?.status === 401
              ? 'Sesi telah kedaluwarsa. Silakan masuk kembali.'
              : err.response?.data?.message || 'Terjadi kesalahan saat memuat data pesanan.';
          setError(message);
          setLoading(false);
        }
      });

    return () => {
      ignore = true;
    };
  }, [id, reloadTrigger]);

  const canChangeStatus = role === 'OWNER' || role === 'ADMIN';

  // Determine logical next status transition action from current order status
  const getNextStatusAction = (currentStatus) => {
    switch (currentStatus) {
      case 'DRAFT':
        return { targetStatus: 'QUOTATION', label: 'Ajukan Penawaran (QUOTATION)', icon: '📋' };
      case 'QUOTATION':
        return { targetStatus: 'CONFIRMED', label: 'Konfirmasi Pesanan (CONFIRMED)', icon: '✓' };
      case 'CONFIRMED':
        return { targetStatus: 'WAITING_DP', label: 'Tandai Menunggu DP (WAITING_DP)', icon: '💳' };
      case 'WAITING_DP':
        return { targetStatus: 'READY_FOR_PRODUCTION', label: 'Tandai Siap Produksi', icon: '⚙️' };
      case 'READY_FOR_PRODUCTION':
        return { targetStatus: 'IN_PRODUCTION', label: 'Mulai Produksi (IN_PRODUCTION)', icon: '🔨' };
      case 'IN_PRODUCTION':
        return { targetStatus: 'QC', label: 'Kirim ke Inspeksi QC', icon: '🔍' };
      case 'QC':
        return { targetStatus: 'PACKING', label: 'Lanjut ke Pengemasan (PACKING)', icon: '📦' };
      case 'PACKING':
        return { targetStatus: 'READY_TO_SHIP', label: 'Tandai Siap Kirim (READY_TO_SHIP)', icon: '🚚' };
      case 'READY_TO_SHIP':
        return { targetStatus: 'SHIPPED', label: 'Kirim Pesanan (SHIPPED)', icon: '🚛' };
      case 'SHIPPED':
        return { targetStatus: 'COMPLETED', label: 'Selesaikan Pesanan (COMPLETED)', icon: '🏆' };
      default:
        return null;
    }
  };

  const handleStatusTransition = async (targetStatus, label) => {
    const actionText = label || targetStatus;
    if (!window.confirm(`Ubah status pesanan menjadi ${actionText}?`)) {
      return;
    }

    setTransitioning(true);
    setTransitionError(null);
    setTransitionSuccess(null);

    try {
      const res = await changeOrderStatus(order.id, targetStatus);
      if (res?.success) {
        setTransitionSuccess(`Status pesanan berhasil diubah menjadi ${targetStatus}.`);
        reloadOrder();
      } else {
        setTransitionError(res?.message || 'Gagal mengubah status pesanan.');
      }
    } catch (err) {
      if (err.response?.status === 422 && err.response?.data?.errors) {
        const errors = err.response.data.errors;
        const msg = Object.values(errors).flat().join(' ');
        setTransitionError(msg || err.response.data.message || 'Transisi status ditolak oleh aturan bisnis.');
      } else {
        setTransitionError(err.response?.data?.message || 'Terjadi kesalahan saat mengubah status.');
      }
      reloadOrder();
    } finally {
      setTransitioning(false);
    }
  };

  const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val || 0);
  };

  const nextAction = order ? getNextStatusAction(order.status) : null;

  if (authLoading || loading) {
    return (
      <div className="admin-page-container" style={{ padding: '3rem 1.5rem', textAlign: 'center' }}>
        <p className="loading-text">Memuat lembar kerja pesanan mebel...</p>
      </div>
    );
  }

  if (error || !order) {
    return (
      <div className="admin-page-container">
        <div className="alert-error" style={{ margin: '2rem 0' }}>
          {error || 'Pesanan tidak ditemukan.'}
        </div>
        <Link to="/orders" className="btn btn-secondary">
          &larr; Kembali ke Daftar Pesanan
        </Link>
      </div>
    );
  }

  return (
    <div className="admin-page-container">
      {/* Breadcrumb Navigation */}
      <div style={{ marginBottom: '1rem' }}>
        <Link to="/orders" className="btn-link" style={{ fontSize: '0.875rem' }}>
          &larr; Daftar Pesanan
        </Link>
      </div>

      {/* Main Order Workspace Header */}
      <div className="order-workspace-header">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: '1rem' }}>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', flexWrap: 'wrap' }}>
              <h1 className="order-workspace-title">{order.order_number}</h1>
              <span className={`badge-status badge-status-${order.status.toLowerCase()}`}>
                {order.status}
              </span>
            </div>
            <p className="order-workspace-subtitle">
              {order.title || 'Pesanan Mebel'} &mdash; Pemesan: <strong>{order.customer?.name || 'Pelanggan'}</strong>
            </p>
          </div>

          {/* Contextual Status Action Button */}
          {canChangeStatus && nextAction && (
            <div className="order-next-action-box">
              <button
                type="button"
                onClick={() => handleStatusTransition(nextAction.targetStatus, nextAction.label)}
                disabled={transitioning}
                className="btn btn-primary"
                style={{ fontSize: '0.9375rem' }}
              >
                {transitioning ? 'Memproses...' : `${nextAction.icon} ${nextAction.label}`}
              </button>
            </div>
          )}

          {(order.status === 'COMPLETED' || order.status === 'CANCELLED') && (
            <span className="order-terminal-tag">
              Pesanan Telah {order.status === 'COMPLETED' ? 'Selesai Sempurna' : 'Dibatalkan'}
            </span>
          )}
        </div>

        {/* Transition Feedback Alerts */}
        {transitionError && (
          <div className="alert-error" style={{ marginTop: '1rem' }} role="alert">
            <strong>Aturan Bisnis: </strong> {transitionError}
          </div>
        )}
        {transitionSuccess && (
          <div className="alert-success" style={{ marginTop: '1rem' }} role="alert">
            {transitionSuccess}
          </div>
        )}
      </div>

      {/* Operational Module Tab Switcher */}
      <div className="order-module-tabs">
        <button
          type="button"
          className={`module-tab-btn ${activeTab === 'SUMMARY' ? 'active' : ''}`}
          onClick={() => setActiveTab('SUMMARY')}
        >
          📋 Ringkasan
        </button>
        <button
          type="button"
          className={`module-tab-btn ${activeTab === 'SPEC' ? 'active' : ''}`}
          onClick={() => setActiveTab('SPEC')}
        >
          📐 Spesifikasi Teknis
        </button>
        <button
          type="button"
          className={`module-tab-btn ${activeTab === 'PRODUCTION' ? 'active' : ''}`}
          onClick={() => setActiveTab('PRODUCTION')}
        >
          🔨 Tahapan Produksi & Foto
        </button>
        <button
          type="button"
          className={`module-tab-btn ${activeTab === 'QC' ? 'active' : ''}`}
          onClick={() => setActiveTab('QC')}
        >
          🔍 Quality Control
        </button>
        <button
          type="button"
          className={`module-tab-btn ${activeTab === 'SHIPPING' ? 'active' : ''}`}
          onClick={() => setActiveTab('SHIPPING')}
        >
          🚚 Pengiriman
        </button>
      </div>

      {/* Two-Column Editorial Layout */}
      <div className="order-workspace-body">
        {/* Left Column: Active Operational Module */}
        <div className="order-workspace-main">
          {activeTab === 'SUMMARY' && (
            <div className="operational-module-card">
              <h2 className="operational-module-title">Rincian Item & Produk Pesanan</h2>
              <p className="operational-module-desc">
                Daftar mebel, jumlah, dan nilai kesepakatan pemesanan.
              </p>

              <div className="table-responsive" style={{ marginTop: '1rem' }}>
                <table className="admin-table">
                  <thead>
                    <tr>
                      <th>Nama Produk</th>
                      <th>Kode</th>
                      <th>Jumlah</th>
                      <th>Harga Satuan</th>
                      <th style={{ textAlign: 'right' }}>Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(order.items || []).map((item) => (
                      <tr key={item.id}>
                        <td>
                          <strong>{item.product_name}</strong>
                          {item.notes && (
                            <span style={{ display: 'block', fontSize: '0.75rem', color: 'var(--color-text-secondary)' }}>
                              {item.notes}
                            </span>
                          )}
                        </td>
                        <td>{item.product_code || '-'}</td>
                        <td>x{item.quantity}</td>
                        <td>{formatRupiah(item.unit_price)}</td>
                        <td style={{ textAlign: 'right' }}>
                          <strong>{formatRupiah(item.subtotal || item.quantity * item.unit_price)}</strong>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colSpan="4" style={{ textAlign: 'right', fontWeight: 700 }}>
                        Total Nilai Pesanan:
                      </td>
                      <td style={{ textAlign: 'right', fontSize: '1.1rem', fontWeight: 800, color: 'var(--color-brand)' }}>
                        {formatRupiah(order.total_amount)}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              {order.notes && (
                <div style={{ marginTop: '1.25rem', padding: '1rem', backgroundColor: 'var(--color-surface-hover)', borderRadius: '6px' }}>
                  <strong style={{ fontSize: '0.875rem', color: 'var(--color-text-secondary)', display: 'block', marginBottom: '0.25rem' }}>
                    Catatan Pesanan:
                  </strong>
                  <p style={{ margin: 0, fontSize: '0.9375rem' }}>{order.notes}</p>
                </div>
              )}
            </div>
          )}

          {activeTab === 'SPEC' && (
            <SpecificationSection
              order={order}
              role={role}
              onSpecUpdated={reloadOrder}
            />
          )}

          {activeTab === 'PRODUCTION' && (
            <ProductionSection
              order={order}
              role={role}
              onProductionUpdated={reloadOrder}
            />
          )}

          {activeTab === 'QC' && (
            <QcSection
              order={order}
              role={role}
              onQcUpdated={reloadOrder}
            />
          )}

          {activeTab === 'SHIPPING' && (
            <ShippingSection
              order={order}
              role={role}
              onShippingUpdated={reloadOrder}
            />
          )}
        </div>

        {/* Right Column: Customer Card, Tracking Link & WhatsApp Actions */}
        <aside className="order-workspace-sidebar">
          {/* Customer Information Card */}
          <div className="admin-card">
            <h3 className="sidebar-card-title">Informasi Pelanggan</h3>
            <div className="sidebar-field">
              <span className="sidebar-label">Nama Pemesan</span>
              <strong className="sidebar-value">{order.customer?.name || 'Pelanggan'}</strong>
            </div>
            <div className="sidebar-field">
              <span className="sidebar-label">No. Telepon / WhatsApp</span>
              <span className="sidebar-value">{order.customer?.phone || '-'}</span>
            </div>
            {order.customer?.email && (
              <div className="sidebar-field">
                <span className="sidebar-label">Email</span>
                <span className="sidebar-value">{order.customer.email}</span>
              </div>
            )}
            <div className="sidebar-field">
              <span className="sidebar-label">Alamat Pengiriman</span>
              <span className="sidebar-value">{order.customer?.address || '-'}</span>
            </div>
          </div>

          {/* Public Tracking Portal Card */}
          <div className="admin-card">
            <h3 className="sidebar-card-title">Portal Pelacakan Pelanggan</h3>
            <p style={{ fontSize: '0.8125rem', color: 'var(--color-text-secondary)', marginBottom: '0.75rem' }}>
              Tautan publik aman untuk pelanggan memantau progres produksi mebel tanpa login.
            </p>
            {order.tracking_url ? (
              <div>
                <input
                  type="text"
                  readOnly
                  value={order.tracking_url}
                  className="form-input form-input-sm"
                  style={{ fontSize: '0.75rem', marginBottom: '0.5rem' }}
                />
                <div style={{ display: 'flex', gap: '0.5rem' }}>
                  <button
                    type="button"
                    onClick={() => {
                      navigator.clipboard.writeText(order.tracking_url);
                      alert('Tautan pelacakan berhasil disalin ke clipboard.');
                    }}
                    className="btn btn-secondary btn-sm"
                    style={{ flex: 1 }}
                  >
                    Salin Tautan
                  </button>
                  <a
                    href={order.tracking_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn btn-secondary btn-sm"
                    style={{ textDecoration: 'none', textAlign: 'center', flex: 1 }}
                  >
                    Buka Portal ↗
                  </a>
                </div>
              </div>
            ) : (
              <span style={{ fontSize: '0.8125rem', color: 'var(--color-text-muted)' }}>
                Tautan pelacakan belum tersedia.
              </span>
            )}
          </div>

          {/* WhatsApp Action Dispatch Component */}
          <div className="admin-card">
            <h3 className="sidebar-card-title">Integrasi WhatsApp</h3>
            <OrderWhatsAppActions order={order} role={role} />
          </div>
        </aside>
      </div>
    </div>
  );
}
