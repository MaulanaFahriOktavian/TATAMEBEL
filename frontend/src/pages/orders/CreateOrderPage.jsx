import React, { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { createOrder } from '../../features/orders/services/orderService';
import { listCustomers, createCustomer } from '../../services/customerService';
import useAuth from '../../hooks/useAuth';

export default function CreateOrderPage() {
  const { role } = useAuth();
  const navigate = useNavigate();

  const [customers, setCustomers] = useState([]);
  const [loadingCustomers, setLoadingCustomers] = useState(true);

  // Form state
  const [customerId, setCustomerId] = useState('');
  const [title, setTitle] = useState('');
  const [notes, setNotes] = useState('');
  const [items, setItems] = useState([
    {
      product_name: '',
      product_code: '',
      quantity: 1,
      unit_price: '',
      notes: '',
    },
  ]);

  // Inline Add Customer state
  const [showInlineCustomer, setShowInlineCustomer] = useState(false);
  const [newCustName, setNewCustName] = useState('');
  const [newCustPhone, setNewCustPhone] = useState('');
  const [newCustAddress, setNewCustAddress] = useState('');
  const [inlineCustLoading, setInlineCustLoading] = useState(false);
  const [inlineCustError, setInlineCustError] = useState(null);

  // Form submission state
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [generalError, setGeneralError] = useState(null);

  useEffect(() => {
    // Only OWNER and ADMIN can create orders
    if (role && role !== 'OWNER' && role !== 'ADMIN') {
      navigate('/orders', { replace: true });
      return;
    }

    listCustomers(1)
      .then((res) => {
        if (res?.success) {
          setCustomers(res.data || []);
        }
      })
      .catch(() => {
        // Ignored or handled gracefully
      })
      .finally(() => {
        setLoadingCustomers(false);
      });
  }, [role, navigate]);

  const handleAddItem = () => {
    setItems([
      ...items,
      {
        product_name: '',
        product_code: '',
        quantity: 1,
        unit_price: '',
        notes: '',
      },
    ]);
  };

  const handleRemoveItem = (index) => {
    if (items.length <= 1) return;
    setItems(items.filter((_, idx) => idx !== index));
  };

  const handleItemChange = (index, field, value) => {
    const updated = [...items];
    updated[index][field] = value;
    setItems(updated);
  };

  const handleCreateInlineCustomer = async (e) => {
    e.preventDefault();
    setInlineCustError(null);

    if (!newCustName.trim() || !newCustPhone.trim()) {
      setInlineCustError('Nama dan nomor telepon pelanggan wajib diisi.');
      return;
    }

    setInlineCustLoading(true);
    try {
      const res = await createCustomer({
        name: newCustName.trim(),
        phone: newCustPhone.trim(),
        address: newCustAddress.trim() || null,
      });

      if (res?.success && res.data) {
        setCustomers([res.data, ...customers]);
        setCustomerId(String(res.data.id));
        setShowInlineCustomer(false);
        setNewCustName('');
        setNewCustPhone('');
        setNewCustAddress('');
      } else {
        setInlineCustError(res?.message || 'Gagal menambahkan pelanggan.');
      }
    } catch (err) {
      setInlineCustError(
        err.response?.data?.message || 'Terjadi kesalahan saat menyimpan pelanggan.'
      );
    } finally {
      setInlineCustLoading(false);
    }
  };

  const calculateSubtotal = () => {
    return items.reduce((acc, item) => {
      const qty = parseInt(item.quantity, 10) || 0;
      const price = parseFloat(item.unit_price) || 0;
      return acc + qty * price;
    }, 0);
  };

  const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val || 0);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setGeneralError(null);

    // Client-side validation
    const valErrors = {};
    if (!customerId) valErrors.customer_id = 'Pilih pelanggan pemesan.';
    if (!title.trim()) valErrors.title = 'Judul pesanan wajib diisi.';

    items.forEach((item, idx) => {
      if (!item.product_name.trim()) {
        valErrors[`item_${idx}_name`] = 'Nama produk wajib diisi.';
      }
      if (!item.quantity || parseInt(item.quantity, 10) < 1) {
        valErrors[`item_${idx}_qty`] = 'Jumlah minimal 1.';
      }
      if (item.unit_price === '' || parseFloat(item.unit_price) < 0) {
        valErrors[`item_${idx}_price`] = 'Harga satuan tidak boleh negatif.';
      }
    });

    if (Object.keys(valErrors).length > 0) {
      setErrors(valErrors);
      setGeneralError('Silakan periksa kembali isian formulir yang ditandai merah.');
      return;
    }

    setErrors({});
    setSubmitting(true);

    try {
      const payload = {
        customer_id: parseInt(customerId, 10),
        title: title.trim(),
        notes: notes.trim() || null,
        items: items.map((item) => ({
          product_name: item.product_name.trim(),
          product_code: item.product_code.trim() || null,
          quantity: parseInt(item.quantity, 10),
          unit_price: parseFloat(item.unit_price),
          notes: item.notes.trim() || null,
        })),
      };

      const res = await createOrder(payload);

      if (res?.success && res.data?.id) {
        navigate(`/orders/${res.data.id}`);
      } else {
        setGeneralError(res?.message || 'Gagal membuat pesanan mebel.');
      }
    } catch (err) {
      if (err.response?.status === 422 && err.response?.data?.errors) {
        setErrors(err.response.data.errors);
        setGeneralError(err.response.data.message || 'Data pesanan yang dimasukkan tidak valid.');
      } else {
        setGeneralError(err.response?.data?.message || 'Terjadi kesalahan pada server saat membuat pesanan.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="admin-page-container" style={{ maxWidth: '860px' }}>
      {/* Breadcrumb Navigation */}
      <div style={{ marginBottom: '1rem' }}>
        <Link to="/orders" className="btn-link" style={{ fontSize: '0.875rem' }}>
          &larr; Kembali ke Daftar Pesanan
        </Link>
      </div>

      <div className="admin-page-header" style={{ marginBottom: '1.5rem' }}>
        <div>
          <h1 className="admin-page-title">Buat Pesanan Mebel Baru</h1>
          <p className="admin-page-desc">
            Daftarkan pesanan kerja baru. Backend akan mengkalkulasi nomor pesanan, nilai total, dan inisialisasi status DRAFT.
          </p>
        </div>
      </div>

      {generalError && (
        <div className="alert-error" role="alert" style={{ marginBottom: '1.5rem' }}>
          {generalError}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        {/* 1. Customer Selection Card */}
        <div className="admin-card" style={{ marginBottom: '1.5rem' }}>
          <h2 className="admin-card-section-title">1. Informasi Pemesan</h2>

          {!showInlineCustomer ? (
            <div>
              <div className="form-group">
                <label htmlFor="customer-select">
                  Pilih Pelanggan <span className="req">*</span>
                </label>
                <div style={{ display: 'flex', gap: '0.75rem' }}>
                  <select
                    id="customer-select"
                    className={`form-input ${errors.customer_id ? 'is-invalid' : ''}`}
                    value={customerId}
                    onChange={(e) => setCustomerId(e.target.value)}
                    disabled={loadingCustomers || submitting}
                  >
                    <option value="">-- Pilih dari direktori pelanggan --</option>
                    {customers.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.name} ({c.phone}) {c.company_name ? `— ${c.company_name}` : ''}
                      </option>
                    ))}
                  </select>
                  <button
                    type="button"
                    onClick={() => setShowInlineCustomer(true)}
                    className="btn btn-secondary"
                    style={{ whiteSpace: 'nowrap' }}
                  >
                    + Pelanggan Baru
                  </button>
                </div>
                {errors.customer_id && (
                  <span className="field-error">
                    {Array.isArray(errors.customer_id) ? errors.customer_id[0] : errors.customer_id}
                  </span>
                )}
              </div>
            </div>
          ) : (
            <div className="inline-customer-box">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.75rem' }}>
                <strong>Tambah Cepat Pelanggan Baru</strong>
                <button
                  type="button"
                  onClick={() => setShowInlineCustomer(false)}
                  className="btn-link"
                >
                  Batal / Pilih yang Sudah Ada
                </button>
              </div>

              {inlineCustError && (
                <div className="alert-error" style={{ marginBottom: '0.75rem' }}>
                  {inlineCustError}
                </div>
              )}

              <div className="form-row">
                <div className="form-group">
                  <label>Nama Pelanggan <span className="req">*</span></label>
                  <input
                    type="text"
                    className="form-input"
                    placeholder="Bapak Andi Pratama"
                    value={newCustName}
                    onChange={(e) => setNewCustName(e.target.value)}
                  />
                </div>
                <div className="form-group">
                  <label>No. WhatsApp / HP <span className="req">*</span></label>
                  <input
                    type="text"
                    className="form-input"
                    placeholder="081234567890"
                    value={newCustPhone}
                    onChange={(e) => setNewCustPhone(e.target.value)}
                  />
                </div>
              </div>

              <div className="form-group">
                <label>Alamat Pengiriman (Opsional)</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="Jl. Pandanaran No. 12, Semarang"
                  value={newCustAddress}
                  onChange={(e) => setNewCustAddress(e.target.value)}
                />
              </div>

              <button
                type="button"
                onClick={handleCreateInlineCustomer}
                className="btn btn-secondary"
                disabled={inlineCustLoading}
              >
                {inlineCustLoading ? 'Menyimpan...' : 'Simpan & Pilih Pelanggan Ini'}
              </button>
            </div>
          )}

          <div className="form-group" style={{ marginTop: '1.25rem' }}>
            <label htmlFor="order-title">
              Judul / Deskripsi Ringkas Pesanan <span className="req">*</span>
            </label>
            <input
              id="order-title"
              type="text"
              className={`form-input ${errors.title ? 'is-invalid' : ''}`}
              placeholder="Contoh: Lemari Pakaian Jati 2 Pintu - Custom Bapak Andi"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              disabled={submitting}
            />
            {errors.title && (
              <span className="field-error">
                {Array.isArray(errors.title) ? errors.title[0] : errors.title}
              </span>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="order-notes">Catatan Tambahan Pesanan (Opsional)</label>
            <textarea
              id="order-notes"
              rows="2"
              className="form-input"
              placeholder="Instruksi pengerjaan umum, tenggat waktu kesepakatan, dsb."
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              disabled={submitting}
            />
          </div>
        </div>

        {/* 2. Items List Card */}
        <div className="admin-card" style={{ marginBottom: '1.5rem' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
            <h2 className="admin-card-section-title" style={{ margin: 0 }}>
              2. Daftar Item / Produk Mebel
            </h2>
            <button
              type="button"
              onClick={handleAddItem}
              className="btn btn-secondary"
              style={{ fontSize: '0.8125rem' }}
            >
              + Tambah Item Lain
            </button>
          </div>

          {items.map((item, idx) => (
            <div key={idx} className="order-item-form-card">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' }}>
                <span style={{ fontWeight: 700, fontSize: '0.875rem', color: 'var(--color-text-secondary)' }}>
                  Item #{idx + 1}
                </span>
                {items.length > 1 && (
                  <button
                    type="button"
                    onClick={() => handleRemoveItem(idx)}
                    className="btn-link"
                    style={{ color: 'var(--color-danger, #b91c1c)', fontSize: '0.8125rem' }}
                  >
                    Hapus Item
                  </button>
                )}
              </div>

              <div className="form-row">
                <div className="form-group" style={{ flex: 2 }}>
                  <label>Nama Produk <span className="req">*</span></label>
                  <input
                    type="text"
                    className={`form-input ${errors[`item_${idx}_name`] ? 'is-invalid' : ''}`}
                    placeholder="Contoh: Meja Makan Jati 6 Kursi"
                    value={item.product_name}
                    onChange={(e) => handleItemChange(idx, 'product_name', e.target.value)}
                    disabled={submitting}
                  />
                  {errors[`item_${idx}_name`] && (
                    <span className="field-error">{errors[`item_${idx}_name`]}</span>
                  )}
                </div>

                <div className="form-group" style={{ flex: 1 }}>
                  <label>Kode Produk</label>
                  <input
                    type="text"
                    className="form-input"
                    placeholder="MJA-JT-01"
                    value={item.product_code}
                    onChange={(e) => handleItemChange(idx, 'product_code', e.target.value)}
                    disabled={submitting}
                  />
                </div>
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Jumlah (Qty) <span className="req">*</span></label>
                  <input
                    type="number"
                    min="1"
                    className={`form-input ${errors[`item_${idx}_qty`] ? 'is-invalid' : ''}`}
                    value={item.quantity}
                    onChange={(e) => handleItemChange(idx, 'quantity', e.target.value)}
                    disabled={submitting}
                  />
                  {errors[`item_${idx}_qty`] && (
                    <span className="field-error">{errors[`item_${idx}_qty`]}</span>
                  )}
                </div>

                <div className="form-group">
                  <label>Harga Satuan (IDR) <span className="req">*</span></label>
                  <input
                    type="number"
                    min="0"
                    step="1000"
                    className={`form-input ${errors[`item_${idx}_price`] ? 'is-invalid' : ''}`}
                    placeholder="5000000"
                    value={item.unit_price}
                    onChange={(e) => handleItemChange(idx, 'unit_price', e.target.value)}
                    disabled={submitting}
                  />
                  {errors[`item_${idx}_price`] && (
                    <span className="field-error">{errors[`item_${idx}_price`]}</span>
                  )}
                </div>
              </div>

              <div className="form-group">
                <label>Catatan Item (Opsional)</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="Keterangan kayu / tipe bantalan jok / referensi khusus"
                  value={item.notes}
                  onChange={(e) => handleItemChange(idx, 'notes', e.target.value)}
                  disabled={submitting}
                />
              </div>
            </div>
          ))}

          {/* Subtotal Preview */}
          <div className="order-total-preview">
            <span style={{ color: 'var(--color-text-secondary)', fontSize: '0.9375rem' }}>
              Estimasi Nilai Total Pesanan:
            </span>
            <strong style={{ fontSize: '1.25rem', color: 'var(--color-brand, #92400e)' }}>
              {formatRupiah(calculateSubtotal())}
            </strong>
          </div>
        </div>

        {/* Action Buttons */}
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '1rem', marginTop: '1rem' }}>
          <button
            type="button"
            onClick={() => navigate('/orders')}
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
            {submitting ? 'Menyimpan Pesanan...' : 'Simpan & Lanjut ke Detail Pesanan'}
          </button>
        </div>
      </form>
    </div>
  );
}
