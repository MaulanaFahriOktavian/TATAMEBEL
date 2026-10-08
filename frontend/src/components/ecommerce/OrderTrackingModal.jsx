import React, { useState } from 'react';
import { trackOrder } from '../../services/ecommerceApi';

export default function OrderTrackingModal({ isOpen, onClose }) {
  const [tokenInput, setTokenInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [orderResult, setOrderResult] = useState(null);
  const [errorMsg, setErrorMsg] = useState(null);

  if (!isOpen) return null;

  const handleTrack = async (e) => {
    e.preventDefault();
    if (!tokenInput.trim()) {
      setErrorMsg('Masukkan kode pelacakan atau nomor pesanan Anda.');
      return;
    }

    setLoading(true);
    setErrorMsg(null);
    setOrderResult(null);

    try {
      const res = await trackOrder(tokenInput);
      if (res.success && res.data) {
        setOrderResult(res.data);
      } else {
        setErrorMsg('Pesanan tidak ditemukan. Periksa kembali kode pelacakan Anda.');
      }
    } catch (err) {
      setErrorMsg(
        err.response?.data?.message ||
        'Pesanan tidak ditemukan atau tautan pelacakan tidak valid. Pastikan kode sesuai dengan yang dikirim via WhatsApp.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-stone-900/60 backdrop-blur-sm transition-opacity"
        onClick={onClose}
      />

      <div className="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div className="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-brand-border p-6 sm:p-8">
          {/* Close button */}
          <button
            type="button"
            onClick={onClose}
            className="absolute top-5 right-5 rounded-full p-2 text-stone-400 hover:text-brand-dark hover:bg-stone-100 transition-colors"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 rounded-2xl bg-stone-100 flex items-center justify-center text-brand-dark">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" />
              </svg>
            </div>
            <div>
              <h3 className="font-display text-xl font-bold text-brand-dark">
                Lacak Progres Pesanan
              </h3>
              <p className="text-xs text-brand-muted">
                Pantau proses pembuatan kriya &amp; status pengiriman mebel Anda secara langsung.
              </p>
            </div>
          </div>

          {/* Form */}
          <form onSubmit={handleTrack} className="mt-4">
            <div className="flex gap-2">
              <input
                type="text"
                value={tokenInput}
                onChange={(e) => setTokenInput(e.target.value)}
                placeholder="Masukkan kode pelacakan atau token WhatsApp..."
                className="flex-1 rounded-xl border border-brand-border px-4 py-3 text-xs font-medium text-brand-dark focus:border-brand-dark focus:ring-1 focus:ring-brand-dark"
              />
              <button
                type="submit"
                disabled={loading}
                className="inline-flex items-center justify-center rounded-xl bg-brand-dark px-5 py-3 text-xs font-bold uppercase tracking-wider text-white hover:bg-stone-800 disabled:opacity-50 transition-all"
              >
                {loading ? 'Mencari...' : 'Lacak'}
              </button>
            </div>
          </form>

          {/* Error Message */}
          {errorMsg && (
            <div className="mt-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700">
              {errorMsg}
            </div>
          )}

          {/* Result View */}
          {orderResult && (
            <div className="mt-6 border-t border-brand-border/70 pt-5 space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-stone-500">
                    Nomor Pesanan:
                  </span>
                  <h4 className="font-display text-base font-bold text-brand-dark">
                    {orderResult.order_number || orderResult.title}
                  </h4>
                </div>
                <span className="rounded-full bg-brand-dark px-3 py-1 text-xs font-bold text-white uppercase tracking-wider">
                  {orderResult.status}
                </span>
              </div>

              {orderResult.title && (
                <div className="text-xs text-brand-muted">
                  <span className="font-semibold text-brand-dark">Judul Proyek: </span>
                  {orderResult.title}
                </div>
              )}

              {/* Stages Timeline if available */}
              {orderResult.production_stages && orderResult.production_stages.length > 0 && (
                <div className="mt-4 pt-3 border-t border-brand-border/60">
                  <span className="text-xs font-bold text-brand-dark block mb-2">
                    Tahapan Pengerjaan Workshop:
                  </span>
                  <div className="space-y-2">
                    {orderResult.production_stages.map((stg, sIdx) => (
                      <div
                        key={sIdx}
                        className={`flex items-center justify-between p-2.5 rounded-xl text-xs ${
                          stg.is_current ? 'bg-stone-100 font-bold border border-stone-300' : 'bg-stone-50 text-stone-600'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <span className="w-5 h-5 rounded-full bg-brand-dark text-white text-[10px] flex items-center justify-center font-bold">
                            {sIdx + 1}
                          </span>
                          <span>{stg.name}</span>
                        </div>
                        <span className="text-[11px] uppercase font-semibold text-stone-500">
                          {stg.status}
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Action Button to Open Full Customer Portal Page */}
              <div className="mt-5 pt-3 border-t border-brand-border/60">
                <a
                  href={`/track/${orderResult.public_token}`}
                  className="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-brand-dark bg-white py-2.5 px-4 text-xs font-bold uppercase tracking-wider text-brand-dark hover:bg-stone-100 transition-all text-center"
                >
                  <span>Buka Halaman Pelacakan Lengkap &amp; Galeri Foto &rarr;</span>
                </a>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
