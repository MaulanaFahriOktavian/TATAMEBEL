import React from 'react';

/**
 * ItemSpecificationCard component displaying ordered items and locked technical specifications.
 */
export default function ItemSpecificationCard({ items = [] }) {
  if (!items || items.length === 0) {
    return null;
  }

  return (
    <div className="portal-card">
      <div className="portal-card-header">
        <span className="portal-card-title">Spesifikasi Mebel</span>
        <span style={{ fontSize: '0.75rem', color: '#64748b' }}>
          {items.length} Barang
        </span>
      </div>

      {items.map((item, index) => {
        const spec = item.specification;
        const dims = spec?.dimensions;

        return (
          <div key={index} className="spec-item-box">
            <div className="spec-item-header">
              <div>
                <div className="spec-item-name">{item.product_name}</div>
                {item.product_code && (
                  <div style={{ fontSize: '0.6875rem', color: '#94a3b8' }}>
                    Kode: {item.product_code}
                  </div>
                )}
              </div>
              <span className="spec-item-qty">{item.quantity} Unit</span>
            </div>

            {spec ? (
              <div className="spec-grid">
                {dims && (
                  <div>
                    <div className="spec-cell-label">Dimensi (P x L x T)</div>
                    <div className="spec-cell-val">
                      {dims.width} × {dims.depth} × {dims.height} {dims.unit}
                    </div>
                  </div>
                )}
                {spec.material && (
                  <div>
                    <div className="spec-cell-label">Material Kayu</div>
                    <div className="spec-cell-val">
                      {spec.material} {spec.wood_grade ? `(${spec.wood_grade})` : ''}
                    </div>
                  </div>
                )}
                {spec.finishing && (
                  <div>
                    <div className="spec-cell-label">Finishing</div>
                    <div className="spec-cell-val">{spec.finishing}</div>
                  </div>
                )}
                {spec.color && (
                  <div>
                    <div className="spec-cell-label">Warna</div>
                    <div className="spec-cell-val">{spec.color}</div>
                  </div>
                )}
                {spec.fabric && (
                  <div>
                    <div className="spec-cell-label">Kain / Jok</div>
                    <div className="spec-cell-val">{spec.fabric}</div>
                  </div>
                )}
                {spec.special_request && (
                  <div style={{ gridColumn: 'span 2' }}>
                    <div className="spec-cell-label">Permintaan Khusus</div>
                    <div className="spec-cell-val">{spec.special_request}</div>
                  </div>
                )}
              </div>
            ) : (
              <div style={{ fontSize: '0.75rem', color: '#94a3b8', fontStyle: 'italic', marginTop: '0.35rem' }}>
                Spesifikasi teknis sedang dalam penyesuaian workshop.
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}
