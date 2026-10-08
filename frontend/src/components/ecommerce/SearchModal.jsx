import React, { useState } from 'react';

export default function SearchModal({
  isOpen,
  onClose,
  products = [],
  onSelectProduct,
}) {
  const [keyword, setKeyword] = useState('');

  const handleClose = () => {
    setKeyword('');
    onClose();
  };

  if (!isOpen) return null;

  const filteredProducts = keyword.trim()
    ? products.filter((p) => {
        const text = `${p.name} ${p.category_name || ''} ${p.wood_type || ''} ${p.description || ''}`.toLowerCase();
        return text.includes(keyword.toLowerCase());
      })
    : [];

  const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val || 0);
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-stone-900/60 backdrop-blur-sm transition-opacity"
        onClick={handleClose}
      />

      <div className="flex min-h-full items-start justify-center p-4 pt-20 text-center sm:p-0">
        <div className="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-brand-border p-6">
          {/* Search Input Box */}
          <div className="flex items-center gap-3 border-b border-brand-border pb-4">
            <svg className="w-5 h-5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8" strokeWidth="2" />
              <path strokeLinecap="round" strokeWidth="2" d="m21 21-4.35-4.35" />
            </svg>
            <input
              type="text"
              autoFocus
              value={keyword}
              onChange={(e) => setKeyword(e.target.value)}
              placeholder="Cari mebel (contoh: meja makan, kursi jati, rak, credenza)..."
              className="w-full text-sm font-medium text-brand-dark placeholder-stone-400 outline-none border-none focus:ring-0 p-0"
            />
            <button
              type="button"
              onClick={handleClose}
              className="rounded-full p-1.5 text-stone-400 hover:text-brand-dark hover:bg-stone-100 transition-colors"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          {/* Results list */}
          <div className="mt-4 max-h-80 overflow-y-auto space-y-2 custom-scrollbar">
            {keyword.trim() === '' ? (
              <div className="py-8 text-center text-xs text-brand-muted">
                Ketikkan kata kunci untuk mencari furnitur pilihan TATAMEBEL.
              </div>
            ) : filteredProducts.length === 0 ? (
              <div className="py-8 text-center text-xs text-brand-muted">
                Tidak ada produk yang cocok dengan pencarian "{keyword}".
              </div>
            ) : (
              filteredProducts.map((prod) => (
                <div
                  key={prod.id}
                  onClick={() => {
                    onSelectProduct(prod);
                    handleClose();
                  }}
                  className="flex items-center gap-3.5 p-3 rounded-2xl hover:bg-brand-cream cursor-pointer transition-colors border border-transparent hover:border-brand-border/60"
                >
                  <div className="w-14 h-14 rounded-xl overflow-hidden bg-stone-100 shrink-0">
                    <img src={prod.image_url} alt={prod.name} className="w-full h-full object-cover" />
                  </div>
                  <div className="flex-1">
                    <h4 className="font-display text-sm font-bold text-brand-dark">
                      {prod.name}
                    </h4>
                    <span className="text-[11px] text-stone-500 block">
                      {prod.wood_type || prod.category_name}
                    </span>
                  </div>
                  <span className="font-mono text-xs font-bold text-brand-dark shrink-0">
                    {formatRupiah(prod.price)}
                  </span>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
