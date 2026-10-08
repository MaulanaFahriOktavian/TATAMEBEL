import React, { useState } from 'react';

export default function ProductDetailModal({
  product,
  isOpen,
  onClose,
  onAddToCart,
}) {
  const [quantity, setQuantity] = useState(1);
  const [selectedColor, setSelectedColor] = useState(product?.colors?.[0] || null);

  if (!isOpen || !product) return null;

  const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val || 0);
  };

  const getWhatsAppProductUrl = () => {
    const rawPhone = import.meta.env.VITE_WORKSHOP_WHATSAPP || '';
    const phone = rawPhone.replace(/\D/g, '').replace(/^0/, '62');
    const colorNote = selectedColor ? ` dengan opsi finishing warna: ${selectedColor}` : '';
    const text = `Halo TATAMEBEL, saya tertarik dengan produk ${product.name}${colorNote} (${formatRupiah(product.price)} x ${quantity} unit). Mohon informasi ketersediaan, opsi kustom ukuran, dan waktu pengerjaannya. Terima kasih!`;
    const target = phone ? `https://wa.me/${phone}` : 'https://wa.me/';
    return `${target}?text=${encodeURIComponent(text)}`;
  };

  const handleClose = () => {
    setQuantity(1);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-stone-900/60 backdrop-blur-sm transition-opacity"
        onClick={handleClose}
      />

      <div className="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div className="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-brand-border">
          {/* Close button */}
          <button
            type="button"
            onClick={handleClose}
            className="absolute top-4 right-4 z-10 rounded-full bg-white/80 p-2 text-stone-500 hover:text-brand-dark hover:bg-white shadow-sm backdrop-blur-xs transition-colors"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <div className="grid grid-cols-1 sm:grid-cols-2">
            {/* Image Preview */}
            <div className="relative h-72 sm:h-full bg-stone-100 overflow-hidden">
              <img
                src={product.image_url}
                alt={product.name}
                className="w-full h-full object-cover"
              />
              {product.badge && (
                <div className="absolute top-4 left-4 z-10">
                  <span className="rounded-full bg-brand-dark px-3 py-1 text-xs font-extrabold text-white shadow-sm">
                    {product.badge}
                  </span>
                </div>
              )}
            </div>

            {/* Details */}
            <div className="p-6 sm:p-8 flex flex-col justify-between">
              <div>
                <span className="text-[10px] font-bold uppercase tracking-widest text-stone-500 block">
                  {product.category_name || 'Koleksi Workshop'}
                </span>
                <h3 className="font-display text-2xl font-bold text-brand-dark mt-1">
                  {product.name}
                </h3>

                <div className="mt-3 flex items-baseline gap-2">
                  <span className="font-mono text-xl font-bold text-brand-dark">
                    {formatRupiah(product.price)}
                  </span>
                  {product.original_price && (
                    <span className="font-mono text-xs text-brand-muted line-through">
                      {formatRupiah(product.original_price)}
                    </span>
                  )}
                </div>

                <div className="mt-4 pt-4 border-t border-brand-border/60 space-y-2 text-xs">
                  {product.wood_type && (
                    <div className="flex items-center justify-between">
                      <span className="text-brand-muted">Bahan Utama:</span>
                      <span className="font-semibold text-brand-dark">{product.wood_type}</span>
                    </div>
                  )}
                  {product.stock !== undefined && (
                    <div className="flex items-center justify-between">
                      <span className="text-brand-muted">Ketersediaan:</span>
                      <span className="font-semibold text-emerald-700">Tersedia ({product.stock} unit)</span>
                    </div>
                  )}
                </div>

                <p className="mt-4 text-xs leading-relaxed text-brand-muted font-normal">
                  {product.description}
                </p>

                {/* Color/Finishing Swatches */}
                {product.colors && product.colors.length > 0 && (
                  <div className="mt-4">
                    <span className="block text-[11px] font-semibold text-brand-dark mb-1.5">
                      Pilihan Finishing / Warna:
                    </span>
                    <div className="flex items-center gap-2">
                      {product.colors.map((c, idx) => (
                        <button
                          key={idx}
                          type="button"
                          onClick={() => setSelectedColor(c)}
                          className={`h-5 w-5 rounded-full transition-all ${
                            selectedColor === c
                              ? 'ring-2 ring-brand-dark ring-offset-2 scale-110'
                              : 'ring-1 ring-stone-300 hover:scale-105'
                          }`}
                          style={{ backgroundColor: c }}
                          title={`Finishing ${idx + 1}`}
                        />
                      ))}
                    </div>
                  </div>
                )}

                {/* Quantity Selector */}
                <div className="mt-5 flex items-center justify-between border-t border-brand-border/60 pt-4">
                  <span className="text-xs font-semibold text-brand-dark">Jumlah Unit:</span>
                  <div className="flex items-center gap-2 rounded-xl border border-brand-border bg-stone-50 px-2 py-1">
                    <button
                      type="button"
                      onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                      disabled={quantity <= 1}
                      className="w-6 h-6 rounded-lg bg-white border border-brand-border text-brand-dark text-xs font-bold hover:bg-stone-100 disabled:opacity-40 transition-colors flex items-center justify-center"
                    >
                      -
                    </button>
                    <span className="w-8 text-center font-mono text-xs font-bold text-brand-dark">
                      {quantity}
                    </span>
                    <button
                      type="button"
                      onClick={() => setQuantity((q) => Math.min(product.stock || 99, q + 1))}
                      className="w-6 h-6 rounded-lg bg-white border border-brand-border text-brand-dark text-xs font-bold hover:bg-stone-100 transition-colors flex items-center justify-center"
                    >
                      +
                    </button>
                  </div>
                </div>
              </div>

              {/* Actions */}
              <div className="mt-6 pt-4 border-t border-brand-border/60 flex flex-col gap-2.5">
                <button
                  type="button"
                  onClick={() => {
                    onAddToCart(product, quantity, selectedColor);
                    handleClose();
                  }}
                  className="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-brand-dark py-3 px-4 text-xs font-bold uppercase tracking-wider text-white shadow-md hover:bg-stone-800 transition-all"
                >
                  <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9z" />
                  </svg>
                  <span>Tambah {quantity > 1 ? `(${quantity}) ` : ''}ke Keranjang</span>
                </button>
                <a
                  href={getWhatsAppProductUrl()}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-brand-dark bg-white py-2.5 px-4 text-xs font-bold uppercase tracking-wider text-brand-dark hover:bg-stone-100 transition-all text-center"
                >
                  <span>Konsultasi Kustom via WhatsApp</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
