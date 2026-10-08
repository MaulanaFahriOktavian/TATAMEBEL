import React, { useEffect, useState } from 'react';
import {
  getProductionOverview,
  updateStageStatus,
  uploadOrderMedia,
  listOrderMedia,
} from '../../../services/productionService';

export default function ProductionSection({ order, role, onProductionUpdated }) {
  const isAuthorized = role === 'OWNER' || role === 'ADMIN' || role === 'PRODUCTION';

  const [overview, setOverview] = useState(null);
  const [mediaList, setMediaList] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Stage update state
  const [updatingStageId, setUpdatingStageId] = useState(null);

  // Upload photo form state
  const [photoFile, setPhotoFile] = useState(null);
  const [photoCaption, setPhotoCaption] = useState('');
  const [photoVisibility, setPhotoVisibility] = useState('CUSTOMER');
  const [uploading, setUploading] = useState(false);
  const [uploadError, setUploadError] = useState(null);
  const [uploadSuccess, setUploadSuccess] = useState(null);

  const [reloadTrigger, setReloadTrigger] = useState(0);

  const reloadProduction = () => {
    setReloadTrigger((prev) => prev + 1);
  };

  useEffect(() => {
    let ignore = false;

    Promise.all([
      getProductionOverview(order.id),
      listOrderMedia(order.id),
    ])
      .then(([overviewRes, mediaRes]) => {
        if (!ignore) {
          if (overviewRes?.success) {
            setOverview(overviewRes.data);
            setError(null);
          }
          if (mediaRes?.success) {
            setMediaList(mediaRes.data || []);
          }
          setLoading(false);
        }
      })
      .catch((err) => {
        if (!ignore) {
          setError(err.response?.data?.message || 'Gagal memuat tahapan produksi.');
          setLoading(false);
        }
      });

    return () => {
      ignore = true;
    };
  }, [order.id, reloadTrigger]);

  const handleStageStatusChange = async (stageId, targetStatus) => {
    setUpdatingStageId(stageId);
    setError(null);
    try {
      await updateStageStatus(stageId, targetStatus);
      reloadProduction();
      if (onProductionUpdated) onProductionUpdated();
    } catch (err) {
      setError(err.response?.data?.message || 'Gagal memperbarui status tahapan produksi.');
    } finally {
      setUpdatingStageId(null);
    }
  };

  const handleUploadPhoto = async (e) => {
    e.preventDefault();
    setUploadError(null);
    setUploadSuccess(null);

    if (!photoFile) {
      setUploadError('Pilih berkas foto terlebih dahulu.');
      return;
    }

    setUploading(true);
    try {
      const formData = new FormData();
      formData.append('file', photoFile);
      formData.append('caption', photoCaption.trim());
      formData.append('visibility', photoVisibility);

      const res = await uploadOrderMedia(order.id, formData);
      if (res?.success) {
        setUploadSuccess('Foto progres berhasil diunggah.');
        setPhotoFile(null);
        setPhotoCaption('');
        // Reset file input
        const fileInput = document.getElementById('production-photo-input');
        if (fileInput) fileInput.value = '';
        reloadProduction();
      } else {
        setUploadError(res?.message || 'Gagal mengunggah foto.');
      }
    } catch (err) {
      setUploadError(err.response?.data?.message || 'Terjadi kesalahan saat mengunggah foto.');
    } finally {
      setUploading(false);
    }
  };

  const progressPercent = overview?.progress_percentage ?? 0;
  const stages = overview?.stages || [];

  return (
    <div className="operational-module-card">
      <div className="operational-module-header">
        <div>
          <h2 className="operational-module-title">Tahapan Produksi & Pengerjaan</h2>
          <p className="operational-module-desc">
            Daftar tahapan pengerjaan tukang mebel workshop. Tahapan dan persentase progres dihitung secara otoritatif oleh backend.
          </p>
        </div>
      </div>

      {error && <div className="alert-error" style={{ marginBottom: '1rem' }}>{error}</div>}

      {loading ? (
        <p className="loading-text">Memuat tahapan produksi...</p>
      ) : (
        <div>
          {/* Progress Bar Header */}
          <div className="production-progress-wrapper">
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.4rem' }}>
              <span style={{ fontSize: '0.875rem', fontWeight: 600, color: 'var(--color-text-secondary)' }}>
                Progres Produksi:
              </span>
              <strong style={{ fontSize: '1rem', color: 'var(--color-brand)' }}>
                {progressPercent}%
              </strong>
            </div>
            <div className="progress-track">
              <div
                className="progress-fill"
                style={{ width: `${Math.min(Math.max(progressPercent, 0), 100)}%` }}
              />
            </div>
          </div>

          {/* Stepper Stages List */}
          {stages.length === 0 ? (
            <div className="qc-empty-box" style={{ margin: '1rem 0' }}>
              <p>Belum ada tahapan produksi yang terdaftar untuk pesanan ini.</p>
            </div>
          ) : (
            <div className="stages-list">
              {stages.map((stage) => {
                const isUpdating = updatingStageId === stage.id;
                const isCompleted = stage.status === 'COMPLETED';
                const isInProgress = stage.status === 'IN_PROGRESS';

                return (
                  <div key={stage.id} className={`stage-card ${stage.status.toLowerCase()}`}>
                    <div className="stage-left">
                      <span className="stage-seq">{stage.sequence}</span>
                      <div>
                        <h4 className="stage-name">{stage.name}</h4>
                      <div className="stage-meta">
                        <span className={`badge-stage ${stage.status.toLowerCase()}`}>
                          {stage.status_label || stage.status}
                        </span>
                        {stage.started_at && (
                          <span className="stage-time">
                            Mulai: {new Date(stage.started_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
                          </span>
                        )}
                        {stage.completed_at && (
                          <span className="stage-time">
                            Selesai: {new Date(stage.completed_at).toLocaleDateString('id-ID')}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>

                  {/* Stage Action Controls */}
                  {isAuthorized && (
                    <div className="stage-actions">
                      {stage.status === 'PENDING' && (
                        <button
                          type="button"
                          onClick={() => handleStageStatusChange(stage.id, 'IN_PROGRESS')}
                          className="btn btn-secondary btn-sm"
                          disabled={isUpdating}
                        >
                          {isUpdating ? '...' : 'Mulai Pengerjaan'}
                        </button>
                      )}

                      {isInProgress && (
                        <button
                          type="button"
                          onClick={() => handleStageStatusChange(stage.id, 'COMPLETED')}
                          className="btn btn-primary btn-sm"
                          disabled={isUpdating}
                        >
                          {isUpdating ? '...' : 'Selesaikan Tahap'}
                        </button>
                      )}

                      {isCompleted && (
                        <span className="stage-done-tag">✓ Selesai</span>
                      )}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
          )}

          {/* Media & Documentation Section */}
          <div className="media-section" style={{ marginTop: '2rem', paddingTop: '1.5rem', borderTop: '1px solid var(--color-border)' }}>
            <h3 style={{ fontSize: '1.125rem', fontWeight: 700, marginBottom: '0.5rem', color: 'var(--color-text-primary)' }}>
              Dokumentasi Foto Pengerjaan
            </h3>
            <p style={{ fontSize: '0.875rem', color: 'var(--color-text-secondary)', marginBottom: '1.25rem' }}>
              Foto ber-visibilitas CUSTOMER akan tampil di portal pelacakan pelanggan. Foto INTERNAL hanya dapat diakses staf workshop.
            </p>

            {/* Upload Form */}
            {isAuthorized && (
              <form onSubmit={handleUploadPhoto} className="media-upload-form">
                {uploadError && <div className="alert-error" style={{ marginBottom: '0.75rem' }}>{uploadError}</div>}
                {uploadSuccess && <div className="alert-success" style={{ marginBottom: '0.75rem' }}>{uploadSuccess}</div>}

                <div className="form-row">
                  <div className="form-group" style={{ flex: 1 }}>
                    <label>Pilih File Foto <span className="req">*</span></label>
                    <input
                      id="production-photo-input"
                      type="file"
                      accept="image/*"
                      onChange={(e) => setPhotoFile(e.target.files[0] || null)}
                      className="form-input"
                      disabled={uploading}
                    />
                  </div>

                  <div className="form-group" style={{ flex: 2 }}>
                    <label>Keterangan Foto</label>
                    <input
                      type="text"
                      className="form-input"
                      placeholder="Contoh: Proses pengamplasan halus permukaan meja jati"
                      value={photoCaption}
                      onChange={(e) => setPhotoCaption(e.target.value)}
                      disabled={uploading}
                    />
                  </div>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '0.5rem', flexWrap: 'wrap', gap: '0.75rem' }}>
                  <div className="visibility-selector">
                    <span style={{ fontSize: '0.8125rem', fontWeight: 600, marginRight: '0.5rem' }}>
                      Visibilitas:
                    </span>
                    <label className="radio-label">
                      <input
                        type="radio"
                        name="visibility"
                        value="CUSTOMER"
                        checked={photoVisibility === 'CUSTOMER'}
                        onChange={() => setPhotoVisibility('CUSTOMER')}
                        disabled={uploading}
                      />
                      <span>Pelanggan (CUSTOMER)</span>
                    </label>
                    <label className="radio-label">
                      <input
                        type="radio"
                        name="visibility"
                        value="INTERNAL"
                        checked={photoVisibility === 'INTERNAL'}
                        onChange={() => setPhotoVisibility('INTERNAL')}
                        disabled={uploading}
                      />
                      <span>Internal Bengkel (INTERNAL)</span>
                    </label>
                  </div>

                  <button
                    type="submit"
                    className="btn btn-secondary"
                    disabled={uploading || !photoFile}
                  >
                    {uploading ? 'Mengunggah...' : 'Unggah Foto'}
                  </button>
                </div>
              </form>
            )}

            {/* Media Gallery Grid */}
            <div className="media-grid" style={{ marginTop: '1.25rem' }}>
              {mediaList.length === 0 ? (
                <p className="empty-desc">Belum ada foto pengerjaan yang diunggah.</p>
              ) : (
                mediaList.map((m) => (
                  <div key={m.id} className="media-card">
                    <img
                      src={m.url}
                      alt={m.caption || 'Foto pengerjaan mebel'}
                      className="media-thumb"
                    />
                    <div className="media-info">
                      <span className={`badge-visibility ${m.visibility.toLowerCase()}`}>
                        {m.visibility}
                      </span>
                      <p className="media-caption">{m.caption || 'Tanpa keterangan'}</p>
                      <span className="media-date">
                        {m.created_at ? new Date(m.created_at).toLocaleDateString('id-ID') : '-'}
                      </span>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
