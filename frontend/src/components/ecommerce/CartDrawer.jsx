import React from 'react';

export default function CartDrawer({
  isOpen,
  onClose,
  cartData,
  onUpdateQty,
  onRemoveItem,
  onClearCart,
  loading,
}) {
  if (!isOpen) return null;

  const items = cartData?.items || [];
  const totalAmount = cartData?.total_amount || 0;

  const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val || 0);
  };

  const getWhatsAppCheckoutUrl = () => {
    if (!items.length) return '#';
    const rawPhone = import.meta.env.VITE_WORKSHOP_WHATSAPP || '';
    const phone = rawPhone.replace(/\D/g, '').replace(/^0/, '62');
    let text = 'Halo TATAMEBEL, saya ingin memesan produk berikut dari keranjang belanja:\n\n';
    items.forEach((it, idx) => {
      text += `${idx + 1}. ${it.product?.name || 'Produk'} (Qty: ${it.quantity}) - ${formatRupiah(it.unit_price * it.quantity)}\n`;
    });
    text += `\nTotal Estimasi: ${formatRupiah(totalAmount)}\nMohon konfirmasi ketersediaan dan detail pengirimannya. Terima kasih!`;
    const target = phone ? `https://wa.me/${phone}` : 'https://wa.me/';
    return `${target}?text=${encodeURIComponent(text)}`;
  };

  return (
    <div className="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-stone-900/60 backdrop-blur-sm transition-opacity"
        onClick={onClose}
      />

      <div className="fixed inset-y-0 right-0 flex max-w-full pl-10">
        <div className="w-screen max-w-md transform transition ease-in-out duration-300 bg-white shadow-2xl flex flex-col">
          {/* Header */}
          <div className="flex items-center justify-between px-6 py-5 border-b border-brand-border bg-brand-cream/80">
            <div className="flex items-center gap-2">
              <svg className="w-5 h-5 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.75" d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
              <h2 className="font-display text-lg font-bold text-brand-dark" id="slide-over-title">
                Keranjang Belanja
              </h2>
              <span className="ml-1 rounded-full bg-brand-dark text-white text-xs px-2 py-0.5 font-bold">
                {items.length}
              </span>
            </div>
            <button
              type="button"
              onClick={onClose}
              className="rounded-full p-2 text-stone-400 hover:text-brand-dark hover:bg-stone-100 transition-colors"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          {/* Cart Items List */}
          <div className="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar">
            {loading && items.length === 0 ? (
              <div className="py-20 text-center text-sm text-brand-muted">
                Memuat keranjang belanja...
              </div>
            ) : items.length === 0 ? (
              <div className="py-16 text-center">
                <div className="w-16 h-16 mx-auto rounded-full bg-stone-100 flex items-center justify-center text-stone-400 mb-4">
                  <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9z" />
                  </svg>
                </div>
                <h3 className="font-display text-base font-bold text-brand-dark">Keranjang Masih Kosong</h3>
                <p className="text-xs text-brand-muted mt-1 max-w-xs mx-auto">
                  Pilih karya furnitur pilihan dari katalog dan tambahkan ke keranjang Anda.
                </p>
                <button
                  type="button"
                  onClick={onClose}
                  className="mt-6 inline-flex items-center justify-center rounded-full bg-brand-dark px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-sm hover:bg-stone-800 transition-all"
                >
                  Jelajahi Produk
                </button>
              </div>
            ) : (
              items.map((item) => (
                <div
                  key={item.id}
                  className="flex gap-4 p-3.5 rounded-2xl border border-brand-border bg-brand-cream/40 hover:bg-white transition-all shadow-sm"
                >
                  <div className="w-20 h-20 rounded-xl overflow-hidden bg-stone-100 shrink-0 border border-brand-border/60">
                    <img
                      src={item.product?.image_url}
                      alt={item.product?.name}
                      className="w-full h-full object-cover"
                    />
                  </div>
                  <div className="flex-1 flex flex-col justify-between">
                    <div>
                      <div className="flex items-start justify-between gap-2">
                        <h4 className="font-display text-sm font-bold text-brand-dark leading-tight">
                          {item.product?.name}
                        </h4>
                        <button
                          type="button"
                          onClick={() => onRemoveItem(item.id)}
                          className="text-stone-400 hover:text-red-600 transition-colors"
                          title="Hapus"
                        >
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </div>
                      {item.product?.wood_type && (
                        <span className="text-[10px] text-stone-500 font-medium block mt-0.5">
                          {item.product?.wood_type}
                        </span>
                      )}
                    </div>
                    <div className="flex items-center justify-between mt-2 pt-2 border-t border-brand-border/50">
                      <span className="font-mono text-xs font-bold text-brand-dark">
                        {formatRupiah(item.unit_price * item.quantity)}
                      </span>
                      <div className="flex items-center border border-brand-border rounded-lg bg-white overflow-hidden shadow-xs">
                        <button
                          type="button"
                          onClick={() => onUpdateQty(item.id, Math.max(1, item.quantity - 1))}
                          className="w-6 h-6 flex items-center justify-center text-brand-dark hover:bg-stone-100 transition-colors"
                        >
                          -
                        </button>
                        <span className="w-7 text-center text-xs font-bold font-mono">
                          {item.quantity}
                        </span>
                        <button
                          type="button"
                          onClick={() => onUpdateQty(item.id, item.quantity + 1)}
                          className="w-6 h-6 flex items-center justify-center text-brand-dark hover:bg-stone-100 transition-colors"
                        >
                          +
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Footer Summary */}
          {items.length > 0 && (
            <div className="border-t border-brand-border p-6 bg-brand-cream/80 space-y-4">
              <div className="flex items-center justify-between text-xs text-brand-muted">
                <span>Total Item</span>
                <span className="font-semibold text-brand-dark">{cartData?.total_count || 0} unit</span>
              </div>
              <div className="flex items-baseline justify-between text-sm pt-2 border-t border-brand-border/60">
                <span className="font-bold text-brand-dark">Total Estimasi</span>
                <span className="font-mono text-lg font-extrabold text-brand-dark">
                  {formatRupiah(totalAmount)}
                </span>
              </div>
              <div className="pt-2 flex flex-col gap-2">
                <a
                  href={getWhatsAppCheckoutUrl()}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-brand-dark px-6 py-3.5 text-xs font-bold uppercase tracking-wider text-white shadow-md hover:bg-stone-800 transition-all text-center"
                >
                  <svg className="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91A9.86 9.86 0 0 0 12.04 2Zm0 18.15h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.38c0-4.54 3.7-8.23 8.25-8.23a8.23 8.23 0 0 1 8.23 8.24c0 4.54-3.7 8.23-8.23 8.23Z" />
                  </svg>
                  <span>Lanjut Konsultasi / Pesan via WhatsApp</span>
                </a>
                <button
                  type="button"
                  onClick={onClearCart}
                  className="text-[11px] font-semibold text-stone-500 hover:text-red-600 transition-colors py-1 text-center"
                >
                  Kosongkan Keranjang
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
