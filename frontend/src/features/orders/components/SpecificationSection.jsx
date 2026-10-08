import React, { useEffect, useState, useCallback } from 'react';
import {
  listSpecificationsByItem,
  createSpecification,
  updateSpecification,
  lockSpecification,
} from '../../../services/specificationService';

export default function SpecificationSection({ order, role, onSpecUpdated }) {
  const isAuthorized = role === 'OWNER' || role === 'ADMIN';

  // Map item.id -> { loading, specs, currentDraft, activeTab: 'CURRENT'|'FORM'|'HISTORY', error }
  const [itemSpecs, setItemSpecs] = useState({});

  const fetchSpecsForItem = useCallback(async (itemId) => {
    try {
      const res = await listSpecificationsByItem(order.id, itemId);
      if (res?.success) {
        const specs = res.data || [];
        const draft = specs.find((s) => s.status === 'DRAFT') || null;
        const locked = specs.filter((s) => s.status === 'LOCKED');
        const highestLocked = locked.length > 0 ? locked[0] : null;

        setItemSpecs((prev) => ({
          ...prev,
          [itemId]: {
            loading: false,
            specs,
            draft,
            highestLocked,
            error: null,
          },
        }));
      }
    } catch (err) {
      setItemSpecs((prev) => ({
        ...prev,
        [itemId]: {
          loading: false,
          specs: [],
          draft: null,
          highestLocked: null,
          error: err.response?.data?.message || 'Gagal memuat spesifikasi.',
        },
      }));
    }
  }, [order.id]);

  useEffect(() => {
    if (order?.items) {
      order.items.forEach((item) => {
        setItemSpecs((prev) => ({
          ...prev,
          [item.id]: { ...(prev[item.id] || {}), loading: true },
        }));
        fetchSpecsForItem(item.id);
      });
    }
  }, [order, fetchSpecsForItem]);

  return (
    <div className="operational-module-card">
      <div className="operational-module-header">
        <div>
          <h2 className="operational-module-title">Spesifikasi Teknis Mebel</h2>
          <p className="operational-module-desc">
            Panduan teknis ukuran, kayu, finishing, dan permintaan kustom tukang kayu. Status READY_FOR_PRODUCTION mewajibkan spesifikasi berstatus LOCKED.
          </p>
        </div>
      </div>

      {(!order.items || order.items.length === 0) ? (
        <p className="empty-desc">Tidak ada item mebel pada pesanan ini.</p>
      ) : (
        <div className="spec-items-container">
          {order.items.map((item) => (
            <ItemSpecCard
              key={item.id}
              orderId={order.id}
              item={item}
              state={itemSpecs[item.id] || { loading: true, specs: [] }}
              isAuthorized={isAuthorized}
              onReload={() => {
                fetchSpecsForItem(item.id);
                if (onSpecUpdated) onSpecUpdated();
              }}
            />
          ))}
        </div>
      )}
    </div>
  );
}

function ItemSpecCard({ orderId, item, state, isAuthorized, onReload }) {
  const { loading, draft, highestLocked, error } = state;

  const [formMode, setFormMode] = useState(false);
  const [formData, setFormData] = useState({
    width: '',
    height: '',
    depth: '',
    dimension_unit: 'cm',
    material: 'Kayu Jati',
    wood_grade: 'Grade A',
    finishing: '',
    color: '',
    fabric: '',
    design_reference: '',
    special_request: '',
    production_note: '',
  });

  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState(null);

  const handleOpenEdit = (draftSpec) => {
    if (draftSpec) {
      setFormData({
        width: draftSpec.width ?? '',
        height: draftSpec.height ?? '',
        depth: draftSpec.depth ?? '',
        dimension_unit: draftSpec.dimension_unit || 'cm',
        material: draftSpec.material || 'Kayu Jati',
        wood_grade: draftSpec.wood_grade || 'Grade A',
        finishing: draftSpec.finishing || '',
        color: draftSpec.color || '',
        fabric: draftSpec.fabric || '',
        design_reference: draftSpec.design_reference || '',
        special_request: draftSpec.special_request || '',
        production_note: draftSpec.production_note || '',
      });
    }
    setFormError(null);
    setFormMode(true);
  };

  const handleOpenCreate = () => {
    setFormData({
      width: '',
      height: '',
      depth: '',
      dimension_unit: 'cm',
      material: 'Kayu Jati',
      wood_grade: 'Grade A',
      finishing: '',
      color: '',
      fabric: '',
      design_reference: '',
      special_request: '',
      production_note: '',
    });
    setFormError(null);
    setFormMode(true);
  };

  const handleSaveDraft = async (e) => {
    e.preventDefault();
    setFormError(null);
    setSubmitting(true);

    try {
      const payload = {
        width: parseFloat(formData.width) || null,
        height: parseFloat(formData.height) || null,
        depth: parseFloat(formData.depth) || null,
        dimension_unit: formData.dimension_unit,
        material: formData.material,
        wood_grade: formData.wood_grade || null,
        finishing: formData.finishing || null,
        color: formData.color || null,
        fabric: formData.fabric || null,
        design_reference: formData.design_reference || null,
        special_request: formData.special_request || null,
        production_note: formData.production_note || null,
      };

      if (draft) {
        await updateSpecification(draft.id, payload);
      } else {
        await createSpecification(orderId, item.id, payload);
      }

      setFormMode(false);
      onReload();
    } catch (err) {
      setFormError(err.response?.data?.message || 'Gagal menyimpan spesifikasi draft.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleLockSpec = async (specId) => {
    if (!window.confirm('Kunci spesifikasi ini sekarang? Setelah dikunci, spesifikasi berstatus LOCKED dan tidak dapat diedit langsung.')) {
      return;
    }
    setSubmitting(true);
    setFormError(null);
    try {
      await lockSpecification(specId);
      setFormMode(false);
      onReload();
    } catch (err) {
      setFormError(err.response?.data?.message || 'Gagal mengunci spesifikasi.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="spec-card">
      <div className="spec-card-header">
        <div>
          <h3 className="spec-item-title">{item.product_name}</h3>
          <span className="spec-item-sub">
            Jumlah: {item.quantity} unit {item.product_code ? `| Kode: ${item.product_code}` : ''}
          </span>
        </div>
        <div>
          {highestLocked ? (
            <span className="badge-locked">
              🔒 Terkunci (v{highestLocked.version})
            </span>
          ) : draft ? (
            <span className="badge-draft">
              📝 DRAFT (v{draft.version})
            </span>
          ) : (
            <span className="badge-unspecified">Belum Ada Spesifikasi</span>
          )}
        </div>
      </div>

      {error && <div className="alert-error" style={{ marginBottom: '1rem' }}>{error}</div>}
      {formError && <div className="alert-error" style={{ marginBottom: '1rem' }}>{formError}</div>}

      {loading ? (
        <p className="loading-text">Memuat spesifikasi item...</p>
      ) : formMode ? (
        /* Edit / Create Form */
        <form onSubmit={handleSaveDraft} className="spec-form">
          <div className="form-row">
            <div className="form-group">
              <label>Lebar ({formData.dimension_unit})</label>
              <input
                type="number"
                step="0.1"
                className="form-input"
                placeholder="160"
                value={formData.width}
                onChange={(e) => setFormData({ ...formData, width: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label>Tinggi ({formData.dimension_unit})</label>
              <input
                type="number"
                step="0.1"
                className="form-input"
                placeholder="210"
                value={formData.height}
                onChange={(e) => setFormData({ ...formData, height: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label>Kedalaman/Tebal ({formData.dimension_unit})</label>
              <input
                type="number"
                step="0.1"
                className="form-input"
                placeholder="60"
                value={formData.depth}
                onChange={(e) => setFormData({ ...formData, depth: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label>Satuan</label>
              <select
                className="form-input"
                value={formData.dimension_unit}
                onChange={(e) => setFormData({ ...formData, dimension_unit: e.target.value })}
              >
                <option value="cm">Centimeter (cm)</option>
                <option value="mm">Milimeter (mm)</option>
                <option value="m">Meter (m)</option>
              </select>
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>Material Kayu</label>
              <input
                type="text"
                className="form-input"
                placeholder="Kayu Jati / Mahoni / Sungkai"
                value={formData.material}
                onChange={(e) => setFormData({ ...formData, material: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label>Grade Kayu</label>
              <input
                type="text"
                className="form-input"
                placeholder="Grade A / Grade B / Perhutani"
                value={formData.wood_grade}
                onChange={(e) => setFormData({ ...formData, wood_grade: e.target.value })}
              />
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>Tipe Finishing</label>
              <input
                type="text"
                className="form-input"
                placeholder="Natural Satin Polyurethane / Melamic Doff"
                value={formData.finishing}
                onChange={(e) => setFormData({ ...formData, finishing: e.target.value })}
              />
            </div>
            <div className="form-group">
              <label>Warna Finishing</label>
              <input
                type="text"
                className="form-input"
                placeholder="Dark Teak / Walnut / Salak Brown"
                value={formData.color}
                onChange={(e) => setFormData({ ...formData, color: e.target.value })}
              />
            </div>
          </div>

          <div className="form-group">
            <label>Kain / Busa / Jok (Jika Ada)</label>
            <input
              type="text"
              className="form-input"
              placeholder="Contoh: Kain Fabric Canvas Grey / Busa Rebounded D50"
              value={formData.fabric}
              onChange={(e) => setFormData({ ...formData, fabric: e.target.value })}
            />
          </div>

          <div className="form-group">
            <label>Referensi Desain</label>
            <input
              type="text"
              className="form-input"
              placeholder="Katalog Minimalis 2026 Mod. LP-02"
              value={formData.design_reference}
              onChange={(e) => setFormData({ ...formData, design_reference: e.target.value })}
            />
          </div>

          <div className="form-group">
            <label>Permintaan Khusus Pemesan</label>
            <textarea
              rows="2"
              className="form-input"
              placeholder="Instruksi khusus pemesan..."
              value={formData.special_request}
              onChange={(e) => setFormData({ ...formData, special_request: e.target.value })}
            />
          </div>

          <div className="form-group">
            <label>Catatan Konstruksi / Produksi Workshop</label>
            <textarea
              rows="2"
              className="form-input"
              placeholder="Instruksi sambungan kayu, ketebalan daun meja, toleransi dimensi, dll."
              value={formData.production_note}
              onChange={(e) => setFormData({ ...formData, production_note: e.target.value })}
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
              {submitting ? 'Menyimpan...' : 'Simpan DRAFT'}
            </button>
          </div>
        </form>
      ) : highestLocked ? (
        /* Read-only LOCKED specification view */
        <div className="spec-view-locked">
          <div className="spec-grid">
            <div className="spec-item">
              <span className="spec-label">Dimensi (P x L x T)</span>
              <strong>{highestLocked.width} x {highestLocked.depth} x {highestLocked.height} {highestLocked.dimension_unit}</strong>
            </div>
            <div className="spec-item">
              <span className="spec-label">Kayu & Grade</span>
              <strong>{highestLocked.material} {highestLocked.wood_grade ? `(${highestLocked.wood_grade})` : ''}</strong>
            </div>
            <div className="spec-item">
              <span className="spec-label">Finishing</span>
              <span>{highestLocked.finishing || '-'} {highestLocked.color ? `— ${highestLocked.color}` : ''}</span>
            </div>
            <div className="spec-item">
              <span className="spec-label">Jok / Kain</span>
              <span>{highestLocked.fabric || '-'}</span>
            </div>
          </div>

          {highestLocked.design_reference && (
            <div className="spec-full-field">
              <span className="spec-label">Referensi Desain:</span> {highestLocked.design_reference}
            </div>
          )}

          {highestLocked.special_request && (
            <div className="spec-full-field">
              <span className="spec-label">Permintaan Khusus:</span> {highestLocked.special_request}
            </div>
          )}

          {highestLocked.production_note && (
            <div className="spec-full-field">
              <span className="spec-label">Catatan Konstruksi:</span> {highestLocked.production_note}
            </div>
          )}

          <div className="spec-locked-notice">
            <span>Spesifikasi versi {highestLocked.version} terkunci dan menjadi acuan produksi resmi.</span>
            {draft && (
              <span style={{ display: 'block', marginTop: '0.35rem', color: 'var(--color-brand)' }}>
                Terdapat revisi DRAFT baru (v{draft.version}) yang sedang disusun.
              </span>
            )}
          </div>
        </div>
      ) : draft ? (
        /* Current DRAFT overview with Actions */
        <div className="spec-view-draft">
          <div className="spec-grid">
            <div className="spec-item">
              <span className="spec-label">Dimensi (P x L x T)</span>
              <strong>{draft.width} x {draft.depth} x {draft.height} {draft.dimension_unit}</strong>
            </div>
            <div className="spec-item">
              <span className="spec-label">Kayu</span>
              <strong>{draft.material} {draft.wood_grade ? `(${draft.wood_grade})` : ''}</strong>
            </div>
            <div className="spec-item">
              <span className="spec-label">Finishing</span>
              <span>{draft.finishing || '-'} {draft.color ? `— ${draft.color}` : ''}</span>
            </div>
            <div className="spec-item">
              <span className="spec-label">Catatan Workshop</span>
              <span>{draft.production_note || draft.special_request || '-'}</span>
            </div>
          </div>

          {isAuthorized && (
            <div className="spec-action-bar">
              <button
                type="button"
                onClick={() => handleOpenEdit(draft)}
                className="btn btn-secondary"
                disabled={submitting}
              >
                ✏️ Edit DRAFT (v{draft.version})
              </button>
              <button
                type="button"
                onClick={() => handleLockSpec(draft.id)}
                className="btn btn-primary"
                disabled={submitting}
              >
                🔒 Kunci Spesifikasi (LOCKED)
              </button>
            </div>
          )}
        </div>
      ) : (
        /* Empty specification state */
        <div className="spec-empty-box">
          <p>Belum ada rincian spesifikasi untuk item ini.</p>
          {isAuthorized && (
            <button
              type="button"
              onClick={() => handleOpenCreate()}
              className="btn btn-primary"
              style={{ marginTop: '0.5rem' }}
            >
              + Susun Spesifikasi Teknis (DRAFT)
            </button>
          )}
        </div>
      )}
    </div>
  );
}
