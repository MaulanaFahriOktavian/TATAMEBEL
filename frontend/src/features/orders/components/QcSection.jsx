import React, { useEffect, useState } from 'react';
import {
  listQcInspections,
  createQcInspection,
  getQcInspection,
  evaluateQcItems,
  finalizeQcInspection,
  storeQcDefect,
  updateQcDefectStatus,
} from '../../../services/qcService';

export default function QcSection({ order, role, onQcUpdated }) {
  const isQcAuthorized = role === 'OWNER' || role === 'ADMIN' || role === 'QC';

  const [inspections, setInspections] = useState([]);
  const [activeInspection, setActiveInspection] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Checklist evaluation state
  const [itemEvaluations, setItemEvaluations] = useState({});
  const [savingItems, setSavingItems] = useState(false);

  // New defect form state
  const [showDefectForm, setShowDefectForm] = useState(false);
  const [defectDesc, setDefectDesc] = useState('');
  const [defectSeverity, setDefectSeverity] = useState('LOW');
  const [loggingDefect, setLoggingDefect] = useState(false);

  // Resolve defect state
  const [resolvingDefectId, setResolvingDefectId] = useState(null);
  const [resolutionText, setResolutionText] = useState('');
  const [resolving, setResolving] = useState(false);

  // Finalize state
  const [finalizing, setFinalizing] = useState(false);
  const [actionError, setActionError] = useState(null);

  const [reloadTrigger, setReloadTrigger] = useState(0);

  const reloadInspections = () => {
    setReloadTrigger((prev) => prev + 1);
  };

  useEffect(() => {
    let ignore = false;

    listQcInspections(order.id)
      .then(async (res) => {
        if (ignore) return;
        if (res?.success) {
          const list = res.data || [];
          setInspections(list);

          // Pick latest inspection
          if (list.length > 0) {
            const detailRes = await getQcInspection(list[0].id);
            if (!ignore && detailRes?.success) {
              setActiveInspection(detailRes.data);
              // Preload evaluation inputs
              const initialMap = {};
              (detailRes.data.items || []).forEach((item) => {
                initialMap[item.id] = {
                  status: item.status || 'PASS',
                  notes: item.notes || '',
                };
              });
              setItemEvaluations(initialMap);
            }
          } else {
            setActiveInspection(null);
          }
          setError(null);
        }
        setLoading(false);
      })
      .catch((err) => {
        if (!ignore) {
          setError(err.response?.data?.message || 'Gagal memuat data inspeksi QC.');
          setLoading(false);
        }
      });

    return () => {
      ignore = true;
    };
  }, [order.id, reloadTrigger]);

  const handleCreateInspection = async () => {
    setActionError(null);
    setLoading(true);
    try {
      await createQcInspection(order.id, 'Inspeksi mutu pra-packing.');
      reloadInspections();
      if (onQcUpdated) onQcUpdated();
    } catch (err) {
      setActionError(err.response?.data?.message || 'Gagal membuat lembar inspeksi QC.');
      setLoading(false);
    }
  };

  const handleItemChange = (itemId, field, value) => {
    setItemEvaluations((prev) => ({
      ...prev,
      [itemId]: {
        ...(prev[itemId] || {}),
        [field]: value,
      },
    }));
  };

  const handleSaveEvaluations = async () => {
    if (!activeInspection) return;
    setActionError(null);
    setSavingItems(true);

    try {
      const itemsPayload = Object.entries(itemEvaluations).map(([id, val]) => ({
        id: parseInt(id, 10),
        status: val.status,
        notes: val.notes || null,
      }));

      const res = await evaluateQcItems(activeInspection.id, itemsPayload);
      if (res?.success) {
        reloadInspections();
      }
    } catch (err) {
      setActionError(err.response?.data?.message || 'Gagal menyimpan evaluasi checklist.');
    } finally {
      setSavingItems(false);
    }
  };

  const handleLogDefect = async (e) => {
    e.preventDefault();
    if (!activeInspection || !defectDesc.trim()) return;
    setActionError(null);
    setLoggingDefect(true);

    try {
      await storeQcDefect(activeInspection.id, {
        description: defectDesc.trim(),
        severity: defectSeverity,
      });

      setShowDefectForm(false);
      setDefectDesc('');
      setDefectSeverity('LOW');
      reloadInspections();
    } catch (err) {
      setActionError(err.response?.data?.message || 'Gagal mencatat temuan defek.');
    } finally {
      setLoggingDefect(false);
    }
  };

  const handleResolveDefect = async (defectId) => {
    if (!resolutionText.trim()) {
      setActionError('Tuliskan catatan perbaikan mebel terlebih dahulu.');
      return;
    }
    setActionError(null);
    setResolving(true);

    try {
      await updateQcDefectStatus(defectId, 'RESOLVED', resolutionText.trim());
      setResolvingDefectId(null);
      setResolutionText('');
      reloadInspections();
    } catch (err) {
      setActionError(err.response?.data?.message || 'Gagal memperbarui status defek.');
    } finally {
      setResolving(false);
    }
  };

  const handleFinalize = async (targetStatus) => {
    if (!activeInspection) return;
    const confirmMsg =
      targetStatus === 'PASSED'
        ? 'Finalisasi inspeksi sebagai LOLOS (PASSED)? Pesanan akan diizinkan lanjut ke PACKING.'
        : 'Finalisasi inspeksi sebagai BUTUH PERBAIKAN (REWORK)?';

    if (!window.confirm(confirmMsg)) return;

    setActionError(null);
    setFinalizing(true);

    try {
      await finalizeQcInspection(activeInspection.id, targetStatus, 'Inspeksi QC difinalisasi.');
      reloadInspections();
      if (onQcUpdated) onQcUpdated();
    } catch (err) {
      setActionError(err.response?.data?.message || 'Finalisasi QC ditolak oleh sistem.');
    } finally {
      setFinalizing(false);
    }
  };

  const isPending = activeInspection?.status === 'PENDING';
  const defects = activeInspection?.defects || [];
  const checklistItems = activeInspection?.items || [];

  return (
    <div className="operational-module-card">
      <div className="operational-module-header">
        <div>
          <h2 className="operational-module-title">Quality Control & Pemeriksaan Mutu</h2>
          <p className="operational-module-desc">
            Standar konstruksi, presisi sudut, finishing, dan fungsionalitas mebel. Status PACKING mewajibkan inspeksi QC PASSED dan seluruh defek terselesaikan.
          </p>
        </div>
      </div>

      {error && <div className="alert-error" style={{ marginBottom: '1rem' }}>{error}</div>}
      {actionError && <div className="alert-error" style={{ marginBottom: '1rem' }}>{actionError}</div>}

      {loading ? (
        <p className="loading-text">Memuat data QC...</p>
      ) : !activeInspection ? (
        /* Empty QC state */
        <div className="qc-empty-box">
          <p>Belum ada lembar inspeksi QC yang dibuat untuk pesanan ini.</p>
          {isQcAuthorized && (
            <button
              type="button"
              onClick={handleCreateInspection}
              className="btn btn-primary"
              style={{ marginTop: '0.75rem' }}
            >
              + Buat Lembar Inspeksi QC Baru
            </button>
          )}
        </div>
      ) : (
        <div>
          {/* Inspection Header Card */}
          <div className="inspection-header-card">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <span style={{ fontSize: '0.8125rem', color: 'var(--color-text-secondary)' }}>
                  Inspeksi #{activeInspection.id} &mdash; Tanggal: {activeInspection.inspected_at ? new Date(activeInspection.inspected_at).toLocaleDateString('id-ID') : '-'}
                  {inspections.length > 1 && ` (Total ${inspections.length} lembar)`}
                </span>
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginTop: '0.25rem' }}>
                  <h3 style={{ margin: 0, fontSize: '1.125rem', fontWeight: 700 }}>
                    Status Mutu:
                  </h3>
                  <span className={`badge-qc-status ${activeInspection.status.toLowerCase()}`}>
                    {activeInspection.status_label || activeInspection.status}
                  </span>
                </div>
              </div>

              {/* Finalize Action Buttons if PENDING */}
              {isPending && isQcAuthorized && (
                <div style={{ display: 'flex', gap: '0.5rem' }}>
                  <button
                    type="button"
                    onClick={() => handleFinalize('REWORK')}
                    className="btn btn-secondary btn-sm"
                    disabled={finalizing}
                  >
                    Butuh Perbaikan (REWORK)
                  </button>
                  <button
                    type="button"
                    onClick={() => handleFinalize('PASSED')}
                    className="btn btn-primary btn-sm"
                    disabled={finalizing}
                  >
                    Lolos QC (PASSED)
                  </button>
                </div>
              )}
            </div>

            {/* Immutability Banner */}
            {!isPending && (
              <div className="qc-finalized-notice" style={{ marginTop: '0.75rem' }}>
                Lembar inspeksi ini telah berstatus <strong>{activeInspection.status}</strong> dan terkunci permanen.
              </div>
            )}
          </div>

          {/* Checklist Evaluation Table */}
          <div style={{ marginTop: '1.5rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' }}>
              <h4 style={{ margin: 0, fontSize: '1rem', fontWeight: 700 }}>
                1. Butir Evaluasi Checklist
              </h4>
              {isPending && isQcAuthorized && (
                <button
                  type="button"
                  onClick={handleSaveEvaluations}
                  className="btn btn-secondary btn-sm"
                  disabled={savingItems}
                >
                  {savingItems ? 'Menyimpan...' : 'Simpan Penilaian Checklist'}
                </button>
              )}
            </div>

            <div className="table-responsive">
              <table className="admin-table qc-table">
                <thead>
                  <tr>
                    <th>Item Pemeriksaan</th>
                    <th style={{ width: '180px' }}>Hasil Evaluasi</th>
                    <th>Catatan Temuan</th>
                  </tr>
                </thead>
                <tbody>
                  {checklistItems.map((item) => {
                    const evalState = itemEvaluations[item.id] || { status: item.status || 'PASS', notes: item.notes || '' };

                    return (
                      <tr key={item.id}>
                        <td>
                          <strong>{item.checklist_item || item.name}</strong>
                          {item.description && (
                            <span style={{ display: 'block', fontSize: '0.75rem', color: 'var(--color-text-secondary)' }}>
                              {item.description}
                            </span>
                          )}
                        </td>
                        <td>
                          {isPending && isQcAuthorized ? (
                            <select
                              className="form-input form-input-sm"
                              value={evalState.status}
                              onChange={(e) => handleItemChange(item.id, 'status', e.target.value)}
                            >
                              <option value="PASS">Lolos (PASS)</option>
                              <option value="FAIL">Gagal (FAIL)</option>
                              <option value="NA">Tidak Relevan (NA)</option>
                            </select>
                          ) : (
                            <span className={`badge-eval ${evalState.status.toLowerCase()}`}>
                              {evalState.status}
                            </span>
                          )}
                        </td>
                        <td>
                          {isPending && isQcAuthorized ? (
                            <input
                              type="text"
                              className="form-input form-input-sm"
                              placeholder="Keterangan pemeriksa..."
                              value={evalState.notes}
                              onChange={(e) => handleItemChange(item.id, 'notes', e.target.value)}
                            />
                          ) : (
                            <span>{evalState.notes || '-'}</span>
                          )}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>

          {/* Defect Tracking Section */}
          <div style={{ marginTop: '2rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.75rem' }}>
              <h4 style={{ margin: 0, fontSize: '1rem', fontWeight: 700 }}>
                2. Daftar Cacat (Defect Tracker)
              </h4>
              {isPending && isQcAuthorized && !showDefectForm && (
                <button
                  type="button"
                  onClick={() => setShowDefectForm(true)}
                  className="btn btn-secondary btn-sm"
                >
                  + Catat Temuan Cacat
                </button>
              )}
            </div>

            {/* Log Defect Form */}
            {showDefectForm && (
              <form onSubmit={handleLogDefect} className="defect-form-box">
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                  <strong>Form Catatan Cacat Mebel Baru</strong>
                  <button type="button" onClick={() => setShowDefectForm(false)} className="btn-link">
                    Batal
                  </button>
                </div>

                <div className="form-group">
                  <label>Deskripsi Temuan Cacat <span className="req">*</span></label>
                  <textarea
                    rows="2"
                    className="form-input"
                    placeholder="Contoh: Engsel pintu kiri agak kencang / permukaan cat samping tergores halus"
                    value={defectDesc}
                    onChange={(e) => setDefectDesc(e.target.value)}
                    required
                  />
                </div>

                <div className="form-group">
                  <label>Tingkat Keparahan (Severity)</label>
                  <select
                    className="form-input"
                    value={defectSeverity}
                    onChange={(e) => setDefectSeverity(e.target.value)}
                  >
                    <option value="LOW">Rendah (LOW) &mdash; Cacat kosmetik minor</option>
                    <option value="MEDIUM">Sedang (MEDIUM) &mdash; Butuh penyesuaian pengerjaan</option>
                    <option value="HIGH">Tinggi (HIGH) &mdash; Mempengaruhi fungsi mebel</option>
                    <option value="CRITICAL">Kritis (CRITICAL) &mdash; Kesalahan struktur utama</option>
                  </select>
                </div>

                <button
                  type="submit"
                  className="btn btn-primary btn-sm"
                  disabled={loggingDefect || !defectDesc.trim()}
                >
                  {loggingDefect ? 'Mencatat...' : 'Simpan Temuan Cacat'}
                </button>
              </form>
            )}

            {/* Defect Cards List */}
            {defects.length === 0 ? (
              <p className="empty-desc">Tidak ada temuan cacat (zero defect).</p>
            ) : (
              <div className="defects-list">
                {defects.map((defect) => (
                  <div key={defect.id} className={`defect-card ${defect.status.toLowerCase()}`}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                      <div>
                        <div style={{ display: 'flex', gap: '0.4rem', alignItems: 'center', marginBottom: '0.25rem' }}>
                          <span className={`badge-severity ${defect.severity.toLowerCase()}`}>
                            {defect.severity}
                          </span>
                          <span className={`badge-defect-status ${defect.status.toLowerCase()}`}>
                            {defect.status}
                          </span>
                        </div>
                        <p style={{ margin: '0.35rem 0', fontSize: '0.875rem', fontWeight: 600 }}>
                          {defect.description}
                        </p>
                        {defect.resolution && (
                          <p style={{ fontSize: '0.8125rem', color: 'var(--color-success)', margin: '0.2rem 0' }}>
                            ✓ Solusi: {defect.resolution}
                          </p>
                        )}
                      </div>

                      {/* Resolve defect button */}
                      {(defect.status === 'OPEN' || defect.status === 'IN_REWORK') && isQcAuthorized && (
                        <div>
                          {resolvingDefectId === defect.id ? (
                            <div className="resolve-box">
                              <input
                                type="text"
                                className="form-input form-input-sm"
                                placeholder="Tuliskan catatan perbaikan..."
                                value={resolutionText}
                                onChange={(e) => setResolutionText(e.target.value)}
                              />
                              <div style={{ display: 'flex', gap: '0.35rem', marginTop: '0.25rem' }}>
                                <button
                                  type="button"
                                  onClick={() => handleResolveDefect(defect.id)}
                                  className="btn btn-primary btn-sm"
                                  disabled={resolving}
                                >
                                  {resolving ? '...' : 'Simpan Solusi'}
                                </button>
                                <button
                                  type="button"
                                  onClick={() => {
                                    setResolvingDefectId(null);
                                    setResolutionText('');
                                  }}
                                  className="btn btn-secondary btn-sm"
                                >
                                  Batal
                                </button>
                              </div>
                            </div>
                          ) : (
                            <button
                              type="button"
                              onClick={() => setResolvingDefectId(defect.id)}
                              className="btn btn-secondary btn-sm"
                            >
                              Selesaikan Defek
                            </button>
                          )}
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
