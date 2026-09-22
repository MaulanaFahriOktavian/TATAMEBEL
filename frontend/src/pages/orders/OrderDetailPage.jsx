import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import { getOrderDetail } from '../../features/orders/services/orderService';
import OrderWhatsAppActions from '../../features/orders/components/OrderWhatsAppActions';

export default function OrderDetailPage() {
  const { id } = useParams();
  const { user, role, loading: authLoading, isAuthenticated } = useAuth();

  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!id) return;

    let isMounted = true;

    getOrderDetail(id)
      .then((res) => {
        if (!isMounted) return;
        if (res.success) {
          setOrder(res.data);
          setError(null);
        } else {
          setError(res.message || 'Gagal memuat detail pesanan.');
        }
      })
      .catch((err) => {
        if (!isMounted) return;
        const message =
          err.response?.status === 404
            ? 'Pesanan tidak ditemukan atau berada di luar workshop Anda.'
            : err.response?.status === 401
            ? 'Sesi tidak terotentikasi. Silakan masuk terlebih dahulu.'
            : err.response?.data?.message || 'Terjadi kesalahan saat memuat data pesanan.';
        setError(message);
      })
      .finally(() => {
        if (isMounted) {
          setLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [id]);

  if (authLoading || loading) {
    return (
      <div className="app-container" style={{ padding: '2rem', maxWidth: '800px', margin: '0 auto' }}>
        <p style={{ color: 'var(--color-text-secondary)' }}>Memuat data pesanan...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="app-container" style={{ padding: '2rem', maxWidth: '800px', margin: '0 auto' }}>
        <div
          style={{
            padding: '1rem',
            backgroundColor: 'var(--color-danger-bg, #fee2e2)',
            border: '1px solid #fca5a5',
            borderRadius: 'var(--radius-md, 6px)',
            color: 'var(--color-danger, #b91c1c)',
          }}
        >
          {error}
        </div>
        <div style={{ marginTop: '1rem' }}>
          <Link to="/" style={{ color: 'var(--color-brand, #b45309)', textDecoration: 'none' }}>
            &larr; Kembali ke Beranda
          </Link>
        </div>
      </div>
    );
  }

  if (!order) {
    return null;
  }

  return (
    <div className="app-container" style={{ padding: '2rem 1.5rem', maxWidth: '840px', margin: '0 auto' }}>
      {/* Navigation Breadcrumb */}
      <div style={{ marginBottom: '1.25rem' }}>
        <Link to="/" style={{ color: 'var(--color-brand, #b45309)', textDecoration: 'none', fontSize: '0.875rem' }}>
          &larr; Beranda
        </Link>
      </div>

      {/* Main Order Header Card */}
      <div
        className="card"
        style={{
          backgroundColor: 'var(--color-surface, #ffffff)',
          border: '1px solid var(--color-border, #e2e8f0)',
          borderRadius: 'var(--radius-lg, 8px)',
          padding: '1.5rem',
          boxShadow: 'var(--shadow-sm)',
        }}
      >
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: '1rem' }}>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
              <h1 style={{ fontSize: '1.375rem', fontWeight: 700, color: 'var(--color-text-primary, #0f172a)' }}>
                {order.order_number}
              </h1>
              <span
                style={{
                  fontSize: '0.75rem',
                  fontWeight: 600,
                  padding: '0.2rem 0.6rem',
                  backgroundColor: 'var(--color-brand-subtle, #fef3c7)',
                  color: 'var(--color-brand, #b45309)',
                  borderRadius: '4px',
                }}
              >
                {order.status}
              </span>
            </div>
            <p style={{ color: 'var(--color-text-secondary, #475569)', marginTop: '0.25rem', fontSize: '0.9375rem' }}>
              {order.title || 'Pesanan Mebel'}
            </p>
          </div>

          {/* User Session Info */}
          {isAuthenticated && (
            <div style={{ textAlign: 'right', fontSize: '0.8125rem', color: 'var(--color-text-muted, #64748b)' }}>
              <div>Staf: <strong>{user?.name}</strong></div>
              <div>Peran: <strong>{role}</strong></div>
            </div>
          )}
        </div>

        {/* Customer & Workshop Information */}
        <div
          style={{
            marginTop: '1.25rem',
            paddingTop: '1.25rem',
            borderTop: '1px solid var(--color-border, #e2e8f0)',
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
            gap: '1rem',
            fontSize: '0.875rem',
          }}
        >
          <div>
            <span style={{ color: 'var(--color-text-muted)', display: 'block', marginBottom: '0.25rem' }}>Pelanggan</span>
            <strong>{order.customer?.name || 'Pelanggan'}</strong>
            {order.customer?.phone && (
              <span style={{ display: 'block', color: 'var(--color-text-secondary)' }}>
                {order.customer.phone}
              </span>
            )}
          </div>
          <div>
            <span style={{ color: 'var(--color-text-muted)', display: 'block', marginBottom: '0.25rem' }}>Tanggal Pesanan</span>
            <span>{order.created_at ? new Date(order.created_at).toLocaleDateString('id-ID') : '-'}</span>
          </div>
        </div>

        {/* Items Summary */}
        {order.items && order.items.length > 0 && (
          <div style={{ marginTop: '1.25rem', paddingTop: '1.25rem', borderTop: '1px solid var(--color-border, #e2e8f0)' }}>
            <span style={{ color: 'var(--color-text-muted)', fontSize: '0.875rem', display: 'block', marginBottom: '0.5rem' }}>
              Daftar Produk
            </span>
            <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
              {order.items.map((item) => (
                <li
                  key={item.id}
                  style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    padding: '0.35rem 0',
                    fontSize: '0.875rem',
                    color: 'var(--color-text-primary)',
                  }}
                >
                  <span>{item.product_name}</span>
                  <span style={{ color: 'var(--color-text-secondary)' }}>x{item.quantity}</span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* WhatsApp Actions (conditionally rendered by role in OrderWhatsAppActions) */}
        <OrderWhatsAppActions order={order} role={role} />
      </div>
    </div>
  );
}
