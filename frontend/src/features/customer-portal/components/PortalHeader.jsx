import React from 'react';

/**
 * PortalHeader component displaying workshop brand, order number, and customer.
 */
export default function PortalHeader({ order }) {
  const workshop = order?.workshop;

  const formattedDate = order?.created_at
    ? new Date(order.created_at).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      })
    : '-';

  return (
    <header className="portal-header">
      <div className="portal-header-content">
        <h1 className="portal-workshop-name">{workshop?.name || 'TATAMEBEL Workshop'}</h1>
        <p className="portal-workshop-sub">
          {workshop?.address ? `${workshop.address} · ` : ''}
          Progress Pesanan Mebel
        </p>

        <div className="portal-order-meta">
          <div>
            <div className="portal-order-num">{order?.order_number}</div>
            <div className="portal-cust-name">Pemesan: {order?.customer_name || 'Pelanggan'}</div>
          </div>
          <div style={{ textAlign: 'right', color: '#64748b' }}>
            <div>Tanggal</div>
            <div style={{ fontWeight: 600, color: '#334155' }}>{formattedDate}</div>
          </div>
        </div>
      </div>
    </header>
  );
}
