import React, { useEffect, useState } from 'react';
import { listCustomers, createCustomer } from '../../services/customerService';
import useAuth from '../../hooks/useAuth';

export default function CustomerListPage() {
  const { role } = useAuth();

  const [customers, setCustomers] = useState([]);
  const [meta, setMeta] = useState(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Modal states
  const [showAddModal, setShowAddModal] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState(null);

  // Add customer form state
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    company_name: '',
    email: '',
    address: '',
    notes: '',
  });
  const [formSubmitting, setFormSubmitting] = useState(false);
  const [formErrors, setFormErrors] = useState({});
  const [formGeneralError, setFormGeneralError] = useState(null);

  const [reloadTrigger, setReloadTrigger] = useState(0);

  const refreshCustomers = () => {
    setLoading(true);
    setReloadTrigger((prev) => prev + 1);
  };

  useEffect(() => {
    let ignore = false;

    listCustomers(page)
      .then((res) => {
        if (!ignore) {
          if (res?.success) {
            setCustomers(res.data || []);
            setMeta(res.meta || null);
            setError(null);
          } else {
            setError(res?.message || 'Gagal memuat daftar pelanggan.');
          }
          setLoading(false);
        }
      })
      .catch((err) => {
        if (!ignore) {
          setError(err.response?.data?.message || 'Terjadi kesalahan saat memuat data pelanggan.');
          setLoading(false);
        }
      });

    return () => {
      ignore = true;
    };
  }, [page, reloadTrigger]);

  const handleOpenAddModal = () => {
    setFormData({
      name: '',
      phone: '',
      company_name: '',
      email: '',
      address: '',
      notes: '',
    });
    setFormErrors({});
    setFormGeneralError(null);
    setShowAddModal(true);
  };

  const handleCreateSubmit = async (e) => {
    e.preventDefault();
    setFormGeneralError(null);

    // Client-side validation
    const errors = {};
    if (!formData.name.trim()) errors.name = 'Nama pelanggan wajib diisi.';
    if (!formData.phone.trim()) errors.phone = 'Nomor telepon wajib diisi.';

    if (Object.keys(errors).length > 0) {
      setFormErrors(errors);
      return;
    }

    setFormErrors({});
    setFormSubmitting(true);

    try {
      const payload = {
        name: formData.name.trim(),
        phone: formData.phone.trim(),
        company_name: formData.company_name.trim() || null,
        email: formData.email.trim() || null,
        address: formData.address.trim() || null,
        notes: formData.notes.trim() || null,
      };

      const res = await createCustomer(payload);
      if (res?.success) {
        setShowAddModal(false);
        refreshCustomers();
      } else {
        setFormGeneralError(res?.message || 'Gagal menyimpan data pelanggan.');
      }
    } catch (err) {
      if (err.response?.status === 422 && err.response?.data?.errors) {
        setFormErrors(err.response.data.errors);
        setFormGeneralError(err.response.data.message || 'Data yang dimasukkan tidak valid.');
      } else {
        setFormGeneralError(err.response?.data?.message || 'Terjadi kesalahan pada server.');
      }
    } finally {
      setFormSubmitting(false);
    }
  };

  const canManageCustomers = role === 'OWNER' || role === 'ADMIN';

  return (
    <div className="admin-page-container">
      {/* Page Title & Add Button */}
      <div className="admin-page-header">
        <div>
          <h1 className="admin-page-title">Direktori Pelanggan</h1>
          <p className="admin-page-desc">
            Daftar kontak pelanggan workshop mebel untuk pemesanan dan komunikasi pengiriman.
          </p>
        </div>
        {canManageCustomers && (
          <button onClick={handleOpenAddModal} className="btn btn-primary">
            + Tambah Pelanggan Baru
          </button>
        )}
      </div>

      {/* Error Alert */}
      {error && (
        <div className="alert-error" role="alert">
          <span>{error}</span>
          <button onClick={() => fetchCustomers(page)} className="btn-link">
            Coba Lagi
          </button>
        </div>
      )}

      {/* Customer Table */}
      <div className="admin-card table-responsive">
        {loading ? (
          <div className="loading-state">
            <p>Memuat direktori pelanggan...</p>
          </div>
        ) : customers.length === 0 ? (
          <div className="empty-state">
            <p className="empty-title">Belum ada data pelanggan.</p>
            <p className="empty-desc">
              Tambahkan pelanggan baru untuk mulai mencatat pesanan mebel.
            </p>
            {canManageCustomers && (
              <button
                onClick={handleOpenAddModal}
                className="btn btn-primary"
                style={{ marginTop: '1rem' }}
              >
                + Tambah Pelanggan Pertama
              </button>
            )}
          </div>
        ) : (
          <table className="admin-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Perusahaan</th>
                <th>Telepon</th>
                <th>Email</th>
                <th>Alamat Singkat</th>
                <th style={{ textAlign: 'right' }}>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {customers.map((cust) => (
                <tr key={cust.id}>
                  <td>
                    <strong>{cust.name}</strong>
                  </td>
                  <td>{cust.company_name || '-'}</td>
                  <td>
                    <span className="code-pill" style={{ fontSize: '0.8125rem' }}>
                      {cust.phone}
                    </span>
                  </td>
                  <td>{cust.email || '-'}</td>
                  <td>
                    <span className="address-snippet" title={cust.address}>
                      {cust.address ? (cust.address.length > 35 ? `${cust.address.substring(0, 35)}...` : cust.address) : '-'}
                    </span>
                  </td>
                  <td style={{ textAlign: 'right' }}>
                    <button
                      onClick={() => setSelectedCustomer(cust)}
                      className="btn-detail-link"
                    >
                      Lihat Detail
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Pagination Controls */}
      {meta && meta.last_page > 1 && (
        <div className="admin-pagination">
          <div className="pagination-info">
            Halaman {meta.current_page} dari {meta.last_page} (Total {meta.total} pelanggan)
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

      {/* Add Customer Modal */}
      {showAddModal && (
        <div className="modal-backdrop">
          <div className="modal-dialog">
            <div className="modal-header">
              <h2 className="modal-title">Tambah Pelanggan Baru</h2>
              <button
                onClick={() => setShowAddModal(false)}
                className="btn-close-modal"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleCreateSubmit}>
              <div className="modal-body">
                {formGeneralError && (
                  <div className="alert-error" style={{ marginBottom: '1rem' }}>
                    {formGeneralError}
                  </div>
                )}

                <div className="form-group">
                  <label htmlFor="cust-name">
                    Nama Lengkap <span className="req">*</span>
                  </label>
                  <input
                    id="cust-name"
                    type="text"
                    className={`form-input ${formErrors.name ? 'is-invalid' : ''}`}
                    placeholder="Contoh: Bapak Andi Pratama"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    disabled={formSubmitting}
                  />
                  {formErrors.name && (
                    <span className="field-error">
                      {Array.isArray(formErrors.name) ? formErrors.name[0] : formErrors.name}
                    </span>
                  )}
                </div>

                <div className="form-row">
                  <div className="form-group">
                    <label htmlFor="cust-phone">
                      No. WhatsApp / Telepon <span className="req">*</span>
                    </label>
                    <input
                      id="cust-phone"
                      type="text"
                      className={`form-input ${formErrors.phone ? 'is-invalid' : ''}`}
                      placeholder="081234567890"
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      disabled={formSubmitting}
                    />
                    {formErrors.phone && (
                      <span className="field-error">
                        {Array.isArray(formErrors.phone) ? formErrors.phone[0] : formErrors.phone}
                      </span>
                    )}
                  </div>

                  <div className="form-group">
                    <label htmlFor="cust-company">Perusahaan (Opsional)</label>
                    <input
                      id="cust-company"
                      type="text"
                      className="form-input"
                      placeholder="PT / Toko / Instansi"
                      value={formData.company_name}
                      onChange={(e) => setFormData({ ...formData, company_name: e.target.value })}
                      disabled={formSubmitting}
                    />
                  </div>
                </div>

                <div className="form-group">
                  <label htmlFor="cust-email">Email (Opsional)</label>
                  <input
                    id="cust-email"
                    type="email"
                    className="form-input"
                    placeholder="andi@gmail.com"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    disabled={formSubmitting}
                  />
                </div>

                <div className="form-group">
                  <label htmlFor="cust-address">Alamat Pengiriman (Opsional)</label>
                  <textarea
                    id="cust-address"
                    rows="2"
                    className="form-input"
                    placeholder="Alamat lengkap tujuan pengiriman mebel"
                    value={formData.address}
                    onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                    disabled={formSubmitting}
                  />
                </div>

                <div className="form-group">
                  <label htmlFor="cust-notes">Catatan Khusus Pelanggan (Opsional)</label>
                  <textarea
                    id="cust-notes"
                    rows="2"
                    className="form-input"
                    placeholder="Preferensi khusus, catatan pembayaran, dsb."
                    value={formData.notes}
                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                    disabled={formSubmitting}
                  />
                </div>
              </div>

              <div className="modal-footer">
                <button
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="btn btn-secondary"
                  disabled={formSubmitting}
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="btn btn-primary"
                  disabled={formSubmitting}
                >
                  {formSubmitting ? 'Menyimpan...' : 'Simpan Pelanggan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Customer Detail Modal (Displays Notes as instructed) */}
      {selectedCustomer && (
        <div className="modal-backdrop">
          <div className="modal-dialog">
            <div className="modal-header">
              <h2 className="modal-title">Detail Pelanggan</h2>
              <button
                onClick={() => setSelectedCustomer(null)}
                className="btn-close-modal"
              >
                ✕
              </button>
            </div>

            <div className="modal-body">
              <div className="detail-field">
                <span className="detail-label">Nama Pelanggan</span>
                <span className="detail-value-bold">{selectedCustomer.name}</span>
              </div>
              <div className="detail-field">
                <span className="detail-label">Perusahaan</span>
                <span className="detail-value">{selectedCustomer.company_name || '-'}</span>
              </div>
              <div className="detail-field">
                <span className="detail-label">No. Telepon / WhatsApp</span>
                <span className="detail-value">{selectedCustomer.phone}</span>
              </div>
              <div className="detail-field">
                <span className="detail-label">Email</span>
                <span className="detail-value">{selectedCustomer.email || '-'}</span>
              </div>
              <div className="detail-field">
                <span className="detail-label">Alamat</span>
                <span className="detail-value">{selectedCustomer.address || '-'}</span>
              </div>
              <div className="detail-field">
                <span className="detail-label">Catatan Pelanggan</span>
                <div className="detail-notes-box">
                  {selectedCustomer.notes || 'Tidak ada catatan khusus.'}
                </div>
              </div>
            </div>

            <div className="modal-footer">
              <button
                onClick={() => setSelectedCustomer(null)}
                className="btn btn-secondary"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
