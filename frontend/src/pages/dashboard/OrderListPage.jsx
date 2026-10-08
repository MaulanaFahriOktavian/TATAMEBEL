import React, { useEffect, useState, useMemo } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { listOrders } from '../../features/orders/services/orderService';
import useAuth from '../../hooks/useAuth';

export default function OrderListPage() {
  const { role } = useAuth();
  const navigate = useNavigate();

  const [orders, setOrders] = useState([]);
  const [meta, setMeta] = useState(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Client-side quick filter & search on current page records
  const [statusFilter, setStatusFilter] = useState('ALL');
  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    let ignore = false;

    listOrders(page)
      .then((res) => {
        if (!ignore) {
          if (res?.success) {
            setOrders(res.data || []);
            setMeta(res.meta || null);
            setError(null);
          } else {
            setError(res?.message || 'Gagal memuat daftar pesanan.');
          }
          setLoading(false);
        }
      })
      .catch((err) => {
        if (!ignore) {
          setError(err.response?.data?.message || 'Terjadi kesalahan saat memuat data pesanan.');
          setLoading(false);
        }
      });

    return () => {
      ignore = true;
    };
  }, [page]);

  // Client-side filtering on current page records
  const filteredOrders = useMemo(() => {
    return orders.filter((order) => {
      // 1. Status Filter
      if (statusFilter === 'QUOTATION_CONFIRMED') {
        if (!['DRAFT', 'QUOTATION', 'CONFIRMED', 'WAITING_DP'].includes(order.status)) {
          return false;
        }
      } else if (statusFilter === 'PRODUCTION') {
        if (!['READY_FOR_PRODUCTION', 'IN_PRODUCTION'].includes(order.status)) {
          return false;
        }
      } else if (statusFilter === 'QC') {
        if (order.status !== 'QC') {
          return false;
        }
      } else if (statusFilter === 'SHIPPING') {
        if (!['PACKING', 'READY_TO_SHIP', 'SHIPPED'].includes(order.status)) {
          return false;
        }
      } else if (statusFilter === 'COMPLETED') {
        if (order.status !== 'COMPLETED') {
          return false;
        }
      }

      // 2. Search Keyword Filter
      if (searchTerm.trim()) {
        const query = searchTerm.toLowerCase();
        const matchesNumber = order.order_number?.toLowerCase().includes(query);
        const matchesTitle = order.title?.toLowerCase().includes(query);
        const matchesCustomer = order.customer?.name?.toLowerCase().includes(query);
        return matchesNumber || matchesTitle || matchesCustomer;
      }

      return true;
    });
  }, [orders, statusFilter, searchTerm]);

  const canCreateOrder = role === 'OWNER' || role === 'ADMIN';

  const formatRupiah = (val) => {
    if (typeof val !== 'number') return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val);
  };

  const getStatusBadgeClass = (status) => {
    switch (status) {
      case 'DRAFT':
      case 'QUOTATION':
        return 'badge-status-draft';
      case 'CONFIRMED':
      case 'WAITING_DP':
        return 'badge-status-confirmed';
      case 'READY_FOR_PRODUCTION':
      case 'IN_PRODUCTION':
        return 'badge-status-production';
      case 'QC':
        return 'badge-status-qc';
      case 'PACKING':
      case 'READY_TO_SHIP':
      case 'SHIPPED':
        return 'badge-status-shipping';
      case 'COMPLETED':
        return 'badge-status-completed';
      case 'CANCELLED':
        return 'badge-status-cancelled';
      default:
        return 'badge-status-default';
    }
  };

  return (
    <div className="admin-page-container">
      {/* Page Title & Primary Actions */}
      <div className="admin-page-header">
        <div>
          <h1 className="admin-page-title">Daftar Pesanan Mebel</h1>
          <p className="admin-page-desc">
            Kelola seluruh siklus pesanan dari penawaran, pengerjaan tukang, hingga pengiriman.
          </p>
        </div>
        {canCreateOrder && (
          <button
            onClick={() => navigate('/orders/new')}
            className="btn btn-primary"
          >
            + Buat Pesanan Baru
          </button>
        )}
      </div>

      {/* Filter Tabs & Search Controls */}
      <div className="admin-filter-bar">
        <div className="admin-status-tabs">
          <button
            className={`tab-item ${statusFilter === 'ALL' ? 'active' : ''}`}
            onClick={() => setStatusFilter('ALL')}
          >
            Semua
          </button>
          <button
            className={`tab-item ${statusFilter === 'QUOTATION_CONFIRMED' ? 'active' : ''}`}
            onClick={() => setStatusFilter('QUOTATION_CONFIRMED')}
          >
            Penawaran & DP
          </button>
          <button
            className={`tab-item ${statusFilter === 'PRODUCTION' ? 'active' : ''}`}
            onClick={() => setStatusFilter('PRODUCTION')}
          >
            Produksi
          </button>
          <button
            className={`tab-item ${statusFilter === 'QC' ? 'active' : ''}`}
            onClick={() => setStatusFilter('QC')}
          >
            Inspeksi QC
          </button>
          <button
            className={`tab-item ${statusFilter === 'SHIPPING' ? 'active' : ''}`}
            onClick={() => setStatusFilter('SHIPPING')}
          >
            Pengiriman
          </button>
          <button
            className={`tab-item ${statusFilter === 'COMPLETED' ? 'active' : ''}`}
            onClick={() => setStatusFilter('COMPLETED')}
          >
            Selesai
          </button>
        </div>

        {/* Search input */}
        <div className="admin-search-box">
          <input
            type="text"
            placeholder="Cari nomor pesanan / pelanggan..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="input-search"
          />
        </div>
      </div>

      {/* Error Alert */}
      {error && (
        <div className="alert-error" role="alert">
          <span>{error}</span>
          <button onClick={() => fetchOrders(page)} className="btn-link">
            Coba Lagi
          </button>
        </div>
      )}

      {/* Table Content */}
      <div className="admin-card table-responsive">
        {loading ? (
          <div className="loading-state">
            <p>Memuat data pesanan workshop...</p>
          </div>
        ) : filteredOrders.length === 0 ? (
          <div className="empty-state">
            <p className="empty-title">Tidak ada pesanan yang sesuai.</p>
            <p className="empty-desc">
              {orders.length === 0
                ? 'Belum ada pesanan mebel yang dibuat pada workshop ini.'
                : 'Tidak ada data pesanan pada halaman ini yang sesuai dengan filter.'}
            </p>
            {canCreateOrder && orders.length === 0 && (
              <button
                onClick={() => navigate('/orders/new')}
                className="btn btn-primary"
                style={{ marginTop: '1rem' }}
              >
                Mulai Buat Pesanan
              </button>
            )}
          </div>
        ) : (
          <table className="admin-table">
            <thead>
              <tr>
                <th>No. Pesanan</th>
                <th>Pelanggan</th>
                <th>Item / Judul</th>
                <th>Status</th>
                <th>Nilai Pesanan</th>
                <th>Tanggal</th>
                <th style={{ textAlign: 'right' }}>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {filteredOrders.map((order) => {
                const itemSummary = order.items && order.items.length > 0
                  ? order.items.map((i) => `${i.product_name} (x${i.quantity})`).join(', ')
                  : order.title;

                return (
                  <tr key={order.id}>
                    <td>
                      <Link to={`/orders/${order.id}`} className="order-number-link">
                        {order.order_number}
                      </Link>
                    </td>
                    <td>
                      <div className="customer-cell">
                        <strong>{order.customer?.name || 'Pelanggan'}</strong>
                        {order.customer?.phone && (
                          <span className="customer-sub">{order.customer.phone}</span>
                        )}
                      </div>
                    </td>
                    <td>
                      <span className="order-items-cell" title={itemSummary}>
                        {itemSummary}
                      </span>
                    </td>
                    <td>
                      <span className={`badge-status ${getStatusBadgeClass(order.status)}`}>
                        {order.status}
                      </span>
                    </td>
                    <td>
                      <strong>{formatRupiah(order.total_amount)}</strong>
                    </td>
                    <td>
                      <span className="date-cell">
                        {order.created_at ? new Date(order.created_at).toLocaleDateString('id-ID') : '-'}
                      </span>
                    </td>
                    <td style={{ textAlign: 'right' }}>
                      <Link to={`/orders/${order.id}`} className="btn-detail-link">
                        Buka &rarr;
                      </Link>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}
      </div>

      {/* Pagination Controls */}
      {meta && meta.last_page > 1 && (
        <div className="admin-pagination">
          <div className="pagination-info">
            Halaman {meta.current_page} dari {meta.last_page} (Total {meta.total} pesanan)
          </div>
          <div className="pagination-buttons">
            <button
              onClick={() => setPage((p) => Math.max(p - 1, 1))}
              disabled={meta.current_page <= 1 || loading}
              className="btn-pagination"
            >
              &larr; Sebelumnya
            </button>
            <button
              onClick={() => setPage((p) => Math.min(p + 1, meta.last_page))}
              disabled={meta.current_page >= meta.last_page || loading}
              className="btn-pagination"
            >
              Berikutnya &rarr;
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
