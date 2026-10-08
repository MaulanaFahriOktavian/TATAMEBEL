import React, { useEffect, useState, useMemo } from 'react';
import { Link } from 'react-router-dom';
import {
  fetchProducts,
  fetchCart,
  addToCart,
  updateCartItem,
  removeCartItem,
  clearCart,
  submitInquiry,
} from '../../services/ecommerceApi';
import CartDrawer from '../../components/ecommerce/CartDrawer';
import ProductDetailModal from '../../components/ecommerce/ProductDetailModal';
import OrderTrackingModal from '../../components/ecommerce/OrderTrackingModal';
import SearchModal from '../../components/ecommerce/SearchModal';

const FILTER_TABS = [
  { id: 'all', label: 'Semua' },
  { id: 'kursi', label: 'Kursi' },
  { id: 'meja', label: 'Meja' },
  { id: 'penyimpanan', label: 'Penyimpanan' },
  { id: 'sofa-santai', label: 'Sofa Santai' },
];

export default function LandingPage() {
  const [products, setProducts] = useState([]);
  const [activeTab, setActiveTab] = useState('all');
  const [loadingProducts, setLoadingProducts] = useState(true);

  // Cart state
  const [cartData, setCartData] = useState({ items: [], total_count: 0, total_amount: 0 });
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [cartLoading, setCartLoading] = useState(false);

  // Modals & Drawers
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [isDetailOpen, setIsDetailOpen] = useState(false);
  const [isTrackingOpen, setIsTrackingOpen] = useState(false);
  const [isSearchOpen, setIsSearchOpen] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  // Inquiry Form state
  const [inquiryForm, setInquiryForm] = useState({
    name: '',
    email: '',
    phone: '',
    project_type: 'Residensial (Hunian Pribadi)',
    timeline: 'Segera (< 1 bulan)',
    description: '',
  });
  const [inquirySubmitting, setInquirySubmitting] = useState(false);
  const [inquirySuccess, setInquirySuccess] = useState(false);
  const [inquiryError, setInquiryError] = useState(null);


  const loadCart = async () => {
    try {
      const res = await fetchCart();
      if (res.success && res.data) {
        setCartData(res.data);
      }
    } catch (err) {
      console.error('Failed to load cart:', err);
    } finally {
      setCartLoading(false);
    }
  };

  // Initial load
  useEffect(() => {
    let ignore = false;

    fetchProducts()
      .then((res) => {
        if (!ignore && res.success && res.data) {
          setProducts(res.data);
        }
      })
      .catch((err) => console.error('Failed to load products from API:', err))
      .finally(() => {
        if (!ignore) setLoadingProducts(false);
      });

    fetchCart()
      .then((res) => {
        if (!ignore && res.success && res.data) {
          setCartData(res.data);
        }
      })
      .catch((err) => console.error('Failed to load cart:', err));

    return () => {
      ignore = true;
    };
  }, []);

  const handleAddToCart = async (product, quantity = 1) => {
    try {
      await addToCart(product.id, quantity);
      await loadCart();
      setIsCartOpen(true);
    } catch (err) {
      console.error('Failed to add to cart:', err);
    }
  };

  const handleUpdateQty = async (itemId, newQty) => {
    try {
      await updateCartItem(itemId, newQty);
      await loadCart();
    } catch (err) {
      console.error('Failed to update qty:', err);
    }
  };

  const handleRemoveItem = async (itemId) => {
    try {
      await removeCartItem(itemId);
      await loadCart();
    } catch (err) {
      console.error('Failed to remove item:', err);
    }
  };

  const handleClearCart = async () => {
    try {
      await clearCart();
      await loadCart();
    } catch (err) {
      console.error('Failed to clear cart:', err);
    }
  };

  const handleInquirySubmit = async (e) => {
    e.preventDefault();
    setInquirySubmitting(true);
    setInquiryError(null);
    setInquirySuccess(false);

    try {
      const res = await submitInquiry(inquiryForm);
      if (res.success) {
        setInquirySuccess(true);
        setInquiryForm({
          name: '',
          email: '',
          phone: '',
          project_type: 'Residensial (Hunian Pribadi)',
          timeline: 'Segera (< 1 bulan)',
          description: '',
        });
      }
    } catch (err) {
      setInquiryError(
        err.response?.data?.message || 'Gagal mengirim pengajuan. Silakan periksa kembali data Anda.'
      );
    } finally {
      setInquirySubmitting(false);
    }
  };

  // Filtered Products
  const filteredProducts = useMemo(() => {
    if (activeTab === 'all') return products;
    return products.filter((p) => {
      const slug = p.category?.slug?.toLowerCase() || '';
      const catName = (p.category_name || '').toLowerCase();
      const tab = activeTab.toLowerCase();
      return slug.includes(tab) || catName.includes(tab);
    });
  }, [products, activeTab]);

  const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val || 0);
  };

  return (
    <div className="min-h-screen text-brand-dark antialiased selection:bg-brand-accent selection:text-white bg-[#F8F8F7]">
      {/* Modals & Drawers */}
      <CartDrawer
        isOpen={isCartOpen}
        onClose={() => setIsCartOpen(false)}
        cartData={cartData}
        onUpdateQty={handleUpdateQty}
        onRemoveItem={handleRemoveItem}
        onClearCart={handleClearCart}
        loading={cartLoading}
      />

      <ProductDetailModal
        product={selectedProduct}
        isOpen={isDetailOpen}
        onClose={() => setIsDetailOpen(false)}
        onAddToCart={handleAddToCart}
      />

      <OrderTrackingModal
        isOpen={isTrackingOpen}
        onClose={() => setIsTrackingOpen(false)}
      />

      <SearchModal
        isOpen={isSearchOpen}
        onClose={() => setIsSearchOpen(false)}
        products={products}
        onSelectProduct={(p) => {
          setSelectedProduct(p);
          setIsDetailOpen(true);
        }}
      />

      {/* BEGIN: Navigation */}
      <header className="sticky top-0 z-40 border-b border-brand-border/80 bg-brand-cream/90 backdrop-blur-md transition-all duration-300">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 sm:px-6 lg:px-8">
          {/* Brand Logo / Studio Emblem */}
          <a className="group flex items-center gap-2.5 shrink-0" href="#hero">
            <img
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuARxHJn9tzfOBg_REKmohbBQSxvkB18vhZ-Su7lknU6zr9FoQJLbGUUoal05sVaqCB-Yup63NjEJFsyezrMRvpz-HHGEO8LZg2EEpPBkrffD1FmjV1R7NCqbhc9uIJ6yHwPplHy9QPlxPPR9Dz_Ht_jnFebAil4LWWuvBiZobXCbUzBbVns_oaPs6tnjSGhD-AHedfpQbYvvP7su7t3RK_FQjq56szp_Y8xBZgU2x66KSdzQYIf3L71Ww"
              alt="TATAMEBEL"
              className="h-14 md:h-16 w-auto max-w-[320px] object-contain mix-blend-multiply transition-transform duration-300 group-hover:scale-105"
            />
          </a>

          {/* Desktop Nav Links */}
          <nav className="hidden lg:flex items-center gap-0.5 rounded-full border border-brand-border bg-white/80 shadow-sm badge-blur px-3.5 py-1.5">
            <a className="rounded-full px-4 py-2 text-sm font-semibold text-brand-dark transition-all hover:bg-stone-100" href="#hero">
              Beranda
            </a>
            <a className="rounded-full px-4 py-2 text-sm font-semibold text-brand-muted transition-all hover:text-brand-dark hover:bg-stone-100" href="#products">
              Produk
            </a>
            <a className="rounded-full px-4 py-2 text-sm font-semibold text-brand-muted transition-all hover:text-brand-dark hover:bg-stone-100" href="#about">
              Tentang Kami
            </a>
            <button
              type="button"
              onClick={() => setIsTrackingOpen(true)}
              className="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-brand-muted transition-all hover:text-brand-dark hover:bg-stone-100"
            >
              <span>Lacak Pesanan</span>
              <span className="flex h-4 w-4 items-center justify-center rounded-full bg-stone-200 text-stone-700 text-[9px]">
                <svg className="h-2.5 w-2.5" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" viewBox="0 0 24 24">
                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                </svg>
              </span>
            </button>
            <a className="rounded-full px-4 py-2 text-sm font-semibold text-brand-muted transition-all hover:text-brand-dark hover:bg-stone-100" href="#showrooms">
              Kontak
            </a>
          </nav>

          {/* Action Items on Right Side */}
          <div className="flex items-center gap-2 sm:gap-3">
            <button
              aria-label="Pencarian"
              onClick={() => setIsSearchOpen(true)}
              className="flex h-10 w-10 items-center justify-center rounded-full border border-brand-border bg-white text-brand-dark transition-all duration-200 hover:border-brand-dark hover:bg-stone-100 shadow-sm"
              type="button"
            >
              <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
              </svg>
            </button>

            <button
              aria-label="Keranjang Belanja"
              onClick={() => setIsCartOpen(true)}
              className="relative flex h-10 w-10 items-center justify-center rounded-full border border-brand-border bg-white text-brand-dark transition-all duration-200 hover:border-brand-dark hover:bg-stone-100 shadow-sm"
              type="button"
            >
              <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.75" viewBox="0 0 24 24">
                <path d="M16 11V7a4 4 0 0 0-8 0v4" />
                <path d="M5 9h14l1 12H4L5 9z" />
              </svg>
              <span className="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-brand-dark text-[9px] font-bold text-white shadow-sm">
                {cartData?.total_count || 0}
              </span>
            </button>

            <Link
              to="/login"
              className="hidden sm:inline-flex group items-center gap-2 rounded-full border border-brand-dark bg-white px-5 py-2 text-sm font-semibold uppercase tracking-wider text-brand-dark shadow-sm transition-all duration-300 hover:bg-brand-dark hover:text-white"
            >
              <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
              <span>Masuk</span>
            </Link>

            {/* Mobile Menu Toggle Button */}
            <button
              type="button"
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="lg:hidden flex h-10 w-10 items-center justify-center rounded-full border border-brand-border bg-white text-brand-dark hover:bg-stone-100 transition-colors shadow-sm"
              aria-label="Menu navigasi"
            >
              {isMobileMenuOpen ? (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              ) : (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              )}
            </button>
          </div>
        </div>

        {/* Mobile Navigation Dropdown */}
        {isMobileMenuOpen && (
          <div className="lg:hidden border-t border-brand-border bg-white/95 backdrop-blur-md px-4 py-4 space-y-2">
            <a
              href="#hero"
              onClick={() => setIsMobileMenuOpen(false)}
              className="block rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-dark hover:bg-stone-100 transition-colors"
            >
              Beranda
            </a>
            <a
              href="#products"
              onClick={() => setIsMobileMenuOpen(false)}
              className="block rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-dark hover:bg-stone-100 transition-colors"
            >
              Produk &amp; Katalog
            </a>
            <a
              href="#services"
              onClick={() => setIsMobileMenuOpen(false)}
              className="block rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-dark hover:bg-stone-100 transition-colors"
            >
              Layanan &amp; Arsitektur
            </a>
            <a
              href="#about"
              onClick={() => setIsMobileMenuOpen(false)}
              className="block rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-dark hover:bg-stone-100 transition-colors"
            >
              Tentang Kami &amp; Materialitas
            </a>
            <button
              type="button"
              onClick={() => {
                setIsMobileMenuOpen(false);
                setIsTrackingOpen(true);
              }}
              className="w-full text-left rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-dark hover:bg-stone-100 flex items-center justify-between transition-colors"
            >
              <span>Lacak Pesanan</span>
              <span className="flex h-5 w-5 items-center justify-center rounded-full bg-stone-200 text-stone-700 text-[10px]">
                &rarr;
              </span>
            </button>
            <a
              href="#showrooms"
              onClick={() => setIsMobileMenuOpen(false)}
              className="block rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-dark hover:bg-stone-100 transition-colors"
            >
              Showroom &amp; Workshop
            </a>
            <div className="pt-2 border-t border-brand-border/70">
              <Link
                to="/login"
                onClick={() => setIsMobileMenuOpen(false)}
                className="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-brand-dark py-2.5 text-xs font-bold uppercase tracking-wider text-white"
              >
                <span>Masuk Portal Staf</span>
              </Link>
            </div>
          </div>
        )}
      </header>
      {/* END: Navigation */}

      <main>
        {/* BEGIN: HeroSection */}
        <section className="relative overflow-hidden pt-12 pb-20 md:pt-16 md:pb-28" id="hero">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {/* Center Title Block */}
            <div className="mx-auto max-w-4xl text-center">
              <h1 className="font-display text-4xl font-extrabold tracking-tight text-brand-dark sm:text-6xl md:text-7xl lg:leading-[1.12]">
                Eksplorasi Furnitur Kustom &amp; Proyek Khusus
              </h1>
              <p className="mx-auto mt-6 max-w-2xl text-base text-brand-muted sm:text-lg">
                Furnitur kontemporer berkarakter arsitektural dan kriya kayu Indonesia. Berkolaborasi langsung dengan pemilik hunian, arsitek, dan desainer interior untuk menciptakan karya pesanan khusus yang abadi.
              </p>
              <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
                <a
                  className="inline-flex items-center justify-center rounded-full bg-brand-dark px-7 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-md transition-all hover:bg-stone-800 hover:shadow-lg"
                  href="#inquiry"
                >
                  Konsultasi Proyek
                </a>
                <a
                  className="inline-flex items-center justify-center rounded-full border border-brand-dark bg-white px-7 py-3 text-xs font-bold uppercase tracking-wider text-brand-dark transition-all hover:bg-stone-100"
                  href="#products"
                >
                  Jelajahi Portofolio
                </a>
              </div>
              <div className="mt-12 grid grid-cols-3 max-w-2xl mx-auto border-t border-brand-border/70 pt-8">
                <div className="text-center">
                  <span className="block font-display text-2xl sm:text-3xl font-extrabold text-brand-dark">120+</span>
                  <span className="mt-1 block text-xs text-brand-muted">Proyek Terselesaikan</span>
                </div>
                <div className="text-center border-x border-brand-border/70">
                  <span className="block font-display text-2xl sm:text-3xl font-extrabold text-brand-dark">10+</span>
                  <span className="mt-1 block text-xs text-brand-muted">Tahun Berkarya</span>
                </div>
                <div className="text-center">
                  <span className="block font-display text-2xl sm:text-3xl font-extrabold text-brand-dark">100%</span>
                  <span className="mt-1 block text-xs text-brand-muted">Kayu Lestari SVLK</span>
                </div>
              </div>
            </div>

            {/* Hero Trio Cards Showcase */}
            <div className="mt-14 grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-stretch">
              {/* Card 1: Curated Modern Charcoal Card */}
              <div className="charcoal-card-pattern relative flex flex-col justify-between overflow-hidden p-8 text-white shadow-xl lg:col-span-4 rounded-tl-[3.5rem] rounded-br-[3.5rem] rounded-tr-2xl rounded-bl-2xl border border-stone-800 transition-all duration-500 hover:-translate-y-1 hover:shadow-2xl">
                <div className="pointer-events-none absolute -bottom-10 -right-10 h-56 w-56 rounded-full bg-white/5 blur-2xl"></div>
                <div className="pointer-events-none absolute -top-8 -left-8 h-36 w-36 rounded-full bg-black/40 blur-xl"></div>
                <div className="relative z-10 flex flex-col h-full justify-between">
                  <div>
                    <span className="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white backdrop-blur-sm">
                      Atelier Kustom
                    </span>
                    <h3 className="mt-4 font-display text-3xl sm:text-4xl lg:text-[40px] font-bold tracking-tight text-white leading-[1.15]">
                      100+ Furnitur Modern &amp; Kustom
                    </h3>
                    <p className="mt-4 text-xs sm:text-sm leading-relaxed text-stone-300 font-light max-w-xs">
                      Mewujudkan harmoni ergonomi dan proporsi arsitektur melalui pengerjaan kayu presisi, sambungan tradisional, serta bahan alami pilihan.
                    </p>
                  </div>
                  <div className="mt-10 flex items-center justify-between pt-6 border-t border-white/10">
                    <a
                      className="group/btn inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-brand-dark shadow-md transition-all duration-300 hover:bg-stone-200 hover:shadow-lg"
                      href="#inquiry"
                    >
                      <span>Konsultasi Desain</span>
                      <svg className="h-3.5 w-3.5 transition-transform duration-300 group-hover/btn:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M14 5l7 7m0 0l-7 7m7-7H3" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" />
                      </svg>
                    </a>
                    <span className="font-mono text-xs text-stone-400 tracking-wider">Bespoke Studio</span>
                  </div>
                </div>
              </div>

              {/* Card 2: Central Statement Sofa Showcase */}
              <div className="relative flex flex-col justify-between overflow-hidden bg-white p-6 shadow-xl transition-all duration-500 hover:shadow-2xl lg:col-span-5 rounded-t-[3.5rem] rounded-b-2xl border border-brand-border/80 hover:-translate-y-1">
                <div className="relative h-64 sm:h-72 w-full overflow-hidden rounded-2xl bg-[#EAE8E4] shadow-sm">
                  <img
                    alt="Verve Modular Curved Sofa di Ruang Keluarga"
                    className="h-full w-full object-cover transition-transform duration-700 hover:scale-105"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuBlMY7PfDjp1eP1cOmziqxBwGmiFTBJET-NOf4uC58ErbusyTKXLzU5qbDssFxzgZ1ib3AoM_XE8vB0P9VR9rZyUuz4v6RYkO6W1q5ZlLHRdOK0SmBR9fCWqyZfFXlZe32ZZLgKV72DwCD3x9oaGjWULqou1xXStz8_0ocbz-2wsSZFd1RLEBv-KJ4sVEPYFNUrY78fk89_7mQDH5tAn6yyDSvKMqumjnuPp0LJl7tJ"
                  />
                </div>
                <div className="mt-5 flex flex-col justify-between flex-1">
                  <div>
                    <h4 className="font-display text-xl font-bold text-brand-dark leading-snug">
                      Verve Modular Curved Sofa
                    </h4>
                    <p className="mt-1.5 text-xs text-brand-muted leading-relaxed">
                      Sofa kurva modular kontemporer berbahan beludru terracotta dengan rangka kayu kokoh.
                    </p>
                  </div>
                  <div className="mt-4 flex items-center justify-between border-t border-brand-border/60 pt-3 text-xs">
                    <span className="font-medium text-brand-muted">Kayu Ash Solid &amp; Beludru Terracotta</span>
                    <button
                      type="button"
                      onClick={() => {
                        setActiveTab('sofa-santai');
                        document.getElementById('products')?.scrollIntoView({ behavior: 'smooth' });
                      }}
                      className="inline-flex items-center gap-1 font-bold text-brand-dark hover:text-stone-600 underline underline-offset-4 group/lnk text-left cursor-pointer"
                    >
                      <span>Jelajahi Koleksi</span>
                      <svg className="h-3 w-3 transition-transform duration-300 group-hover/lnk:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5l7 7-7 7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              {/* Card 3: Asymmetrical Lounge Chair Showcase */}
              <div className="relative flex flex-col justify-between overflow-hidden bg-white p-6 shadow-xl transition-all duration-500 hover:shadow-2xl lg:col-span-3 rounded-tr-[3.75rem] rounded-bl-[2.75rem] rounded-tl-2xl rounded-br-2xl border border-brand-border/80 hover:-translate-y-1">
                <div className="relative h-64 sm:h-72 w-full overflow-hidden rounded-tr-[2.75rem] rounded-bl-[2rem] rounded-tl-xl rounded-br-xl bg-[#F0ECE6] shadow-sm">
                  <img
                    alt="Kursi Santai Kura Teak"
                    className="h-full w-full object-cover transition-transform duration-700 hover:scale-105"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuBXHQhR7jZEM7BbTBbOXdpMlJ1uRAnzgWtPRZiOAXbfia370dOvQ5Xls6oIDZJFgVCIxVLBPEKH2QgaOPwGNW4IvTra_kV6vo3bSmKANG45r5si--UEHHPcVRp5AowqXIFyY2HnJlHsgVdxUoyjg68upl9z-zseeosJbDamzSkX5pPycYXNAa68s8UZrypX3dtNX9qT93ezPS6a3DFA5ElzRo-zWok6QDtp3nqIPeU_"
                  />
                </div>
                <div className="mt-5 flex flex-col justify-between flex-1">
                  <div>
                    <h4 className="font-display text-lg font-bold text-brand-dark leading-snug">
                      Kursi Santai Kura Teak
                    </h4>
                    <p className="mt-1.5 text-xs text-brand-muted leading-relaxed">
                      Kursi santai kayu jati solid dengan sandaran kanvas natural dan proporsi rileks.
                    </p>
                  </div>
                  <div className="mt-4 flex items-center justify-between border-t border-brand-border/60 pt-3 text-xs">
                    <span className="text-[11px] font-medium text-brand-muted">Pesanan Khusus</span>
                    <button
                      type="button"
                      onClick={() => {
                        const kura = products.find(p => p.slug?.includes('kura') || p.name?.toLowerCase().includes('kura')) || products[1];
                        if (kura) {
                          setSelectedProduct(kura);
                          setIsDetailOpen(true);
                        } else {
                          document.getElementById('products')?.scrollIntoView({ behavior: 'smooth' });
                        }
                      }}
                      className="inline-flex items-center gap-1 font-bold text-brand-dark hover:text-stone-600 underline underline-offset-4 group/lnk text-left cursor-pointer"
                    >
                      <span>Lihat Detail</span>
                      <svg className="h-3 w-3 transition-transform duration-300 group-hover/lnk:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5l7 7-7 7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* BEGIN: WhyUs Section */}
        <section className="bg-[#191A1C] text-white py-20 lg:py-24 border-y border-stone-800" id="why-us">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="text-center max-w-2xl mx-auto mb-16">
              <h2 className="mt-3 font-display text-3xl sm:text-5xl font-extrabold tracking-tight text-white">
                Mengapa Memilih Kami?
              </h2>
              <p className="mt-3 text-sm text-stone-400">
                Dibuat dari kayu lestari bersertifikat dan pengerjaan presisi oleh perajin kayu berpengalaman.
              </p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
              <div className="rounded-3xl border border-stone-800 bg-[#222326] p-8 text-center flex flex-col items-center hover:border-stone-700 transition-all duration-300">
                <div className="h-14 w-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-white mb-6">
                  <svg className="w-7 h-7 text-white" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.75" viewBox="0 0 24 24">
                    <ellipse cx="12" cy="6" rx="8" ry="3" />
                    <path d="M4 6v12c0 1.66 3.58 3 8 3s8-1.34 8-3V6" />
                    <ellipse cx="12" cy="6" rx="4.5" ry="1.6" />
                    <ellipse cx="12" cy="6" rx="1.5" ry="0.6" />
                    <path d="M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3" />
                  </svg>
                </div>
                <h3 className="font-display text-xl font-bold text-white">Kayu Jati Grade A Jepara</h3>
                <p className="mt-3 text-xs leading-relaxed text-stone-400">
                  Dipilih langsung dari Perhutani Jepara dengan kadar kekeringan oven optimal (kiln-dried), menjamin ketahanan kayu hingga puluhan tahun bebas rayap.
                </p>
              </div>

              <div className="rounded-3xl border border-stone-800 bg-[#222326] p-8 text-center flex flex-col items-center hover:border-stone-700 transition-all duration-300">
                <div className="h-14 w-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-white mb-6">
                  <svg className="w-7 h-7 text-white" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.75" viewBox="0 0 24 24">
                    <path d="m15 4 5 5" />
                    <path d="M17.5 6.5 12 12l-1.5 4.5L15 15l5.5-5.5" />
                    <path d="M3 21l6.5-6.5" />
                    <path d="m6 18 3 3" />
                    <path d="M7 11 11 7" />
                    <path d="M2 12l5-5 2 2-5 5z" />
                  </svg>
                </div>
                <h3 className="font-display text-xl font-bold text-white">Keahlian Kriya Warisan Leluhur</h3>
                <p className="mt-3 text-xs leading-relaxed text-stone-400">
                  Dikerjakan langsung oleh empu kriya dan perajin berpengalaman Jepara dengan presisi sambungan purus kokoh tanpa paku terbuka.
                </p>
              </div>

              <div className="rounded-3xl border border-stone-800 bg-[#222326] p-8 text-center flex flex-col items-center hover:border-stone-700 transition-all duration-300">
                <div className="h-14 w-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-white mb-6">
                  <svg className="w-7 h-7 text-white" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.75" viewBox="0 0 24 24">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    <path d="m9 11.5 2.5 2.5 5-5" />
                    <circle cx="12" cy="12" r="9" strokeDasharray="2 3" strokeWidth="1" />
                  </svg>
                </div>
                <h3 className="font-display text-xl font-bold text-white">Garansi Konstruksi &amp; Kayu Asli</h3>
                <p className="mt-3 text-xs leading-relaxed text-stone-400">
                  Jaminan keaslian kayu solid 100% dan garansi integritas konstruksi jangka panjang langsung dari atelier produksi Jepara.
                </p>
              </div>
            </div>
          </div>
        </section>
        {/* END: WhyUs Section */}

        {/* BEGIN: ServicesAndCategoriesSection */}
        <section className="border-y border-brand-border bg-white py-20" id="services">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-1 gap-12 lg:grid-cols-12 lg:items-start">
              {/* Left Column */}
              <div className="lg:col-span-5">
                <span className="block text-[11px] font-semibold uppercase tracking-widest text-stone-500 mb-2">
                  LAYANAN &amp; ARSITEKTUR
                </span>
                <h2 className="mt-3 font-display text-3xl font-extrabold tracking-tight text-brand-dark sm:text-4xl lg:text-[40px] lg:leading-[1.15]">
                  Layanan Desain Kustom &amp; Arsitektur
                </h2>
                <p className="mt-4 text-base leading-relaxed text-brand-muted font-normal">
                  Kami berkolaborasi erat dengan pemilik hunian, arsitek, dan desainer interior. Mulai dari kurasi dimensi presisi, pemilihan kayu jati grade-A, hingga instalasi berskala profesional.
                </p>
                <div className="mt-8 rounded-2xl border border-brand-border/70 bg-[#F8F8F7] p-5 transition-all duration-300 hover:border-brand-border hover:shadow-sm">
                  <div className="flex items-start gap-4">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-dark text-white shadow-sm">
                      <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" />
                      </svg>
                    </div>
                    <div>
                      <h4 className="font-display text-sm font-bold text-brand-dark tracking-tight">
                        Rekayasa Dimensi &amp; Gambar CAD
                      </h4>
                      <p className="mt-1 text-xs leading-relaxed text-brand-muted">
                        Pemodelan 3D terukur dan seleksi serat kayu khusus yang disesuaikan secara akurat dengan denah arsitektur Anda.
                      </p>
                    </div>
                  </div>
                </div>
                <div className="mt-8 flex items-center">
                  <a className="group inline-flex items-center gap-2.5 text-xs font-bold uppercase tracking-wider text-brand-dark transition-all duration-300 hover:text-stone-600" href="#inquiry">
                    <span>Jadwalkan Konsultasi Arsitektural</span>
                    <span className="flex h-7 w-7 items-center justify-center rounded-full bg-stone-100 text-brand-dark transition-transform duration-300 group-hover:translate-x-1 group-hover:bg-brand-dark group-hover:text-white">
                      <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M14 5l7 7m0 0l-7 7m7-7H3" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                      </svg>
                    </span>
                  </a>
                </div>
              </div>

              {/* Right Column: Numbered Category Browser */}
              <div className="divide-y divide-brand-border rounded-3xl border border-brand-border bg-white shadow-sm lg:col-span-7">
                {[
                  { num: '01', title: 'Ruang Keluarga', desc: '85 Produk Pilihan & Sofa Modular Kustom', slug: 'ruang-keluarga' },
                  { num: '02', title: 'Kamar Tidur', desc: '47 Tempat Tidur Arsitektural, Meja Nakas & Lemari', badge: 'Koleksi Baru', slug: 'kamar-tidur' },
                  { num: '03', title: 'Ruang Kerja Rumah', desc: '65 Meja Ergonomis, Rak Terintegrasi Kabel & Kursi Kerja', slug: 'ruang-kerja-rumah' },
                  { num: '04', title: 'Ruang Makan', desc: '38 Meja Kayu Solid, Meja Pedestal & Kursi Makan Skulptural', slug: 'ruang-makan' },
                  { num: '05', title: 'Dapur & Atelier', desc: '51 Kursi Bar, Meja Pulau & Unit Penyimpanan Kayu Jati', slug: 'dapur-atelier' },
                ].map((cat) => (
                  <div
                    key={cat.num}
                    onClick={() => {
                      setActiveTab(cat.slug);
                      document.getElementById('products')?.scrollIntoView({ behavior: 'smooth' });
                    }}
                    className="group flex items-center justify-between p-6 transition-all duration-300 hover:bg-[#F8F8F7] cursor-pointer"
                  >
                    <div className="flex items-center gap-6">
                      <span className="font-mono text-xs font-semibold text-stone-400 tracking-wider group-hover:text-brand-dark transition-colors">
                        {cat.num}
                      </span>
                      <div>
                        <div className="flex items-center gap-2">
                          <h3 className="font-display text-base sm:text-lg font-bold text-brand-dark group-hover:text-stone-700 transition-colors">
                            {cat.title}
                          </h3>
                          {cat.badge && (
                            <span className="rounded-full border border-stone-300 bg-stone-100 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-stone-600">
                              {cat.badge}
                            </span>
                          )}
                        </div>
                        <p className="text-xs text-brand-muted mt-0.5 font-normal">
                          {cat.desc}
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className="h-9 w-9 rounded-full border border-brand-border/80 flex items-center justify-center text-stone-400 transition-all duration-300 group-hover:border-brand-dark group-hover:bg-brand-dark group-hover:text-white group-hover:translate-x-0.5 shadow-sm">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path d="M9 5l7 7-7 7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" />
                        </svg>
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>
        {/* END: ServicesAndCategoriesSection */}

        {/* BEGIN: Inspire Showcase */}
        <section className="py-20 lg:py-24 bg-[#F8F8F7] border-b border-brand-border" id="inspire">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="relative rounded-[2.5rem] bg-[#F3F3F2] p-8 sm:p-12 lg:p-14 border border-brand-border/80 shadow-sm">
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">
                <div className="lg:col-span-6 relative flex flex-col justify-center">
                  <div className="relative rounded-[2rem] overflow-hidden shadow-sm border border-brand-border/80 bg-white group">
                    <img
                      alt="Inspirasi ruang interior Anda - ruang tamu Skandinavia"
                      className="w-full h-[380px] sm:h-[460px] object-cover rounded-[2rem] shadow-sm transition-transform duration-700 group-hover:scale-105"
                      src="https://lh3.googleusercontent.com/aida/AEtjO1UQz4xWy1IxLzBMlDLdHWzpq1kPEfhjVre-wYyx4B-pkY--CvB1m8jxjG2yqyjN3t8VRcu1NovKlpYbCXV1FgngnvsvgGQaTXaM1vAlcqXWXXUI5ZsFcNY3gqqtaLAiX7u7S72NFdAVTIYBq1WJRHH7vN_zDxmD7vNw5wsVi_e72ECOwDqsdP3NgvY_QFJewAfdto33ogTf2vxAc-exigucZQsB7jsgIpvGUot05SNpfg"
                    />
                  </div>
                </div>
                <div className="lg:col-span-6 flex flex-col justify-between space-y-8">
                  <div className="max-w-xl">
                    <h2 className="font-display text-3xl sm:text-4xl lg:text-[44px] font-extrabold tracking-tight text-brand-dark leading-[1.15]">
                      Inspirasi Ruang Interior Anda
                    </h2>
                    <p className="mt-4 text-base sm:text-lg text-brand-muted font-normal leading-relaxed max-w-md">
                      Hadirkan karakter unik pada hunian Anda, menjadikannya istimewa dalam setiap sudut. Koleksi kami menjembatani proporsi arsitektur dengan tekstur alami yang abadi.
                    </p>
                    <div className="mt-6 flex flex-wrap items-center gap-4">
                      <a className="inline-flex items-center justify-center rounded-2xl bg-brand-dark px-7 py-3.5 text-xs font-bold uppercase tracking-wider text-white shadow-md transition-all hover:bg-stone-800 hover:shadow-lg" href="#products">
                        Mulai Belanja
                      </a>
                      <a className="text-xs font-bold uppercase tracking-wider text-brand-dark hover:text-stone-600 underline underline-offset-4 transition-colors" href="#inquiry">
                        Konsultasi Desain
                      </a>
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-4 sm:gap-6 pt-2">
                    <div className="group relative rounded-2xl overflow-hidden border border-brand-border/80 bg-white shadow-sm">
                      <img
                        alt="Sudut ruang makan dan perpustakaan kriya kayu"
                        className="w-full h-44 sm:h-52 object-cover rounded-2xl shadow-sm transition-transform duration-700 group-hover:scale-105"
                        src="https://lh3.googleusercontent.com/aida/AEtjO1XiGw66YiMOeKbYj3o88qbT1LQp5bVFjbfMI_-S3HpcAxVnEUzPKfuFxcStCtZBQ9R3jY1CEBPk_SKy6EwB7HbE93qqfzuBTrwepWazKqYugFiAaXr9rSEUgjOgPa7e6VSxtq8bkm3pOLB3k_fj0RRlGRAK8S_ARel-Z6KKj5yGDWPCp-3RupSY0de-OR1sJ8X0LwDqt4NnZVfwSdsFBhyBqObQQy5LqNLZbRWtsW0I8A"
                      />
                    </div>
                    <div className="group relative rounded-2xl overflow-hidden border border-brand-border/80 bg-white shadow-sm">
                      <img
                        alt="Sudut tanaman interior dan dekorasi arsitektural"
                        className="w-full h-44 sm:h-52 object-cover rounded-2xl shadow-sm transition-transform duration-700 group-hover:scale-105"
                        src="https://lh3.googleusercontent.com/aida/AEtjO1UWEViUWM6tCMuqSFW86C3VAEjbW9R2NHXZSjyNmioqLp0jDeQZLw8oZ2dZzAxCA3u46PKUgkYhWbk1mAXCrNOrHUYVP97qMykkhBvEGtFIvUJ9xbEc3n8ytyVOcXJnMPZQzr2ILIsy8KUEfD_sxHQyw5-tR1oMPa2uI5IEIeo-Iyyqp4pkojB2_Oi_jo5n05qMqYj97qn80BY6dT-tARbbT6oJQx2LyONB8kdTIT_inw"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* BEGIN: ProductCatalogSection */}
        <section className="py-20 lg:py-28 bg-white" id="products">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {/* Header & Category Filter Tabs */}
            <div className="flex flex-col justify-between gap-6 md:flex-row md:items-end">
              <div>
                <span className="block text-[11px] font-semibold uppercase tracking-widest text-stone-500 mb-2">
                  KATALOG &amp; KARYA
                </span>
                <h2 className="mt-2 font-display text-3xl font-extrabold tracking-tight text-brand-dark sm:text-4xl">
                  Produk Pilihan Terbaik untuk Anda
                </h2>
                <p className="mt-2 text-sm text-brand-muted">
                  Keseimbangan fungsionalitas Skandinavia dengan kehangatan material tropis berkelas.
                </p>
              </div>

              {/* Filter Pills */}
              <div className="flex flex-wrap items-center gap-2">
                {FILTER_TABS.map((tab) => (
                  <button
                    key={tab.id}
                    type="button"
                    onClick={() => setActiveTab(tab.id)}
                    className={`rounded-full px-5 py-2 text-xs font-bold transition-all shadow-sm ${
                      activeTab === tab.id
                        ? 'bg-brand-dark text-white hover:bg-stone-800'
                        : 'border border-brand-border bg-white text-brand-muted hover:border-brand-dark hover:text-brand-dark'
                    }`}
                  >
                    {tab.label}
                  </button>
                ))}
              </div>
            </div>

            {/* Product Cards Grid */}
            <div className="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {loadingProducts ? (
                <div className="col-span-full py-16 text-center text-sm text-brand-muted">
                  Memuat katalog produk TATAMEBEL...
                </div>
              ) : filteredProducts.length === 0 ? (
                <div className="col-span-full py-16 text-center text-sm text-brand-muted">
                  Tidak ada produk pada kategori ini.
                </div>
              ) : (
                filteredProducts.map((prod) => (
                  <article
                    key={prod.id}
                    className="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-brand-border bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-brand-dark/40"
                  >
                    {prod.badge && (
                      <div className="absolute top-6 left-6 z-10">
                        <span className="rounded-full bg-brand-dark px-3 py-1 text-xs font-extrabold text-white shadow-sm">
                          {prod.badge}
                        </span>
                      </div>
                    )}
                    <div className="relative h-64 w-full overflow-hidden rounded-2xl bg-[#F8F8F7]">
                      <img
                        alt={prod.name}
                        className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                        src={prod.image_url}
                      />
                    </div>
                    <div className="mt-4 flex flex-col flex-1 justify-between">
                      <div>
                        <div className="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-brand-muted">
                          <span>{prod.category_name || 'Koleksi Studio'}</span>
                          <span className="text-stone-500 font-semibold">{prod.wood_type}</span>
                        </div>
                        <h3 className="mt-1.5 font-display text-base font-bold text-brand-dark group-hover:text-stone-700 transition-colors">
                          {prod.name}
                        </h3>
                        <div className="mt-3 flex items-baseline gap-2">
                          <span className="font-mono text-base sm:text-lg font-bold text-brand-dark">
                            {formatRupiah(prod.price)}
                          </span>
                          {prod.original_price && (
                            <span className="font-mono text-xs text-brand-muted line-through">
                              {formatRupiah(prod.original_price)}
                            </span>
                          )}
                        </div>
                        {prod.colors && prod.colors.length > 0 && (
                          <div className="mt-3 flex items-center justify-between text-xs text-stone-500">
                            <span>Pilihan Warna:</span>
                            <div className="flex items-center gap-1.5" title="Pilihan Finishing">
                              {prod.colors.map((c, idx) => (
                                <span
                                  key={idx}
                                  className="h-3 w-3 rounded-full"
                                  style={{ backgroundColor: c }}
                                />
                              ))}
                            </div>
                          </div>
                        )}
                      </div>
                      <div className="mt-4 pt-3 border-t border-brand-border/60 flex flex-col gap-2">
                        <div className="grid grid-cols-2 gap-2 w-full">
                          <button
                            type="button"
                            onClick={() => {
                              setSelectedProduct(prod);
                              setIsDetailOpen(true);
                            }}
                            className="inline-flex items-center justify-center rounded-xl border border-brand-border bg-white py-2.5 px-3 text-xs font-semibold text-brand-dark transition-all duration-200 hover:border-brand-dark hover:bg-stone-100 shadow-sm text-center"
                          >
                            Lihat Detail
                          </button>
                          <button
                            type="button"
                            onClick={() => handleAddToCart(prod)}
                            className="inline-flex items-center justify-center gap-1.5 rounded-xl bg-brand-dark text-white py-2.5 px-3 text-xs font-bold shadow-sm transition-all duration-200 hover:bg-stone-800"
                          >
                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span>+ Keranjang</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </article>
                ))
              )}
            </div>
          </div>
        </section>

        {/* BEGIN: Collections Section */}
        <section className="py-20 lg:py-24 bg-[#F3F3F2] border-b border-brand-border" id="collections">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
              <div>
                <span className="block text-[11px] font-semibold uppercase tracking-widest text-stone-500 mb-2">
                  EDISI TERPILIH
                </span>
                <h2 className="mt-2 font-display text-3xl sm:text-4xl font-extrabold text-brand-dark">
                  Koleksi Terlaris Musim Ini
                </h2>
              </div>
              <p className="text-xs text-brand-muted max-w-md">
                Koleksi yang dirancang khusus untuk ruang komersial dan hunian pribadi berkelas tinggi.
              </p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {/* Collection Card 1 */}
              <div className="group rounded-3xl border border-brand-border bg-white overflow-hidden shadow-sm flex flex-col justify-between hover:shadow-xl transition-all duration-300">
                <div className="p-6 pb-0">
                  <span className="text-[10px] uppercase font-bold tracking-widest text-stone-500">
                    Nuansa Kayu Alami
                  </span>
                  <h3 className="font-display text-2xl font-bold text-brand-dark mt-1">Fjord Whisper</h3>
                  <p className="mt-2 text-xs text-brand-muted leading-relaxed">
                    Kombinasi nada minimalis Skandinavia dengan kayu ash pucat serta linen organik bernapas alami.
                  </p>
                </div>
                <div className="p-6">
                  <div className="h-56 rounded-2xl overflow-hidden bg-brand-sand">
                    <img
                      alt="Koleksi Fjord Whisper"
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                      src="https://lh3.googleusercontent.com/aida-public/AB6AXuC0KPPQSc6RNlOEpLBctKbWI2ut8ReYlJ08T1YHsVml-uvWe1ES9ZR8jtiy3F8W7OipIO6GO16H-4n6msxFX5gG8rawI8vag5SPLPyyINn9eUQVOXWDBtZccFoN0u3-mET4v5cL639fUfBzrPc5aZHJdK6u_bjOoYYcJXEleB_2CmkqvPxezdNIemfR8UzMXAuRPw5g7oxjTDlZgDKb9ch6Pu_H-H7Wub_z1VwvJYX5"
                    />
                  </div>
                  <div className="mt-5 pt-3 border-t border-brand-border/60 flex flex-col gap-2.5">
                    <div className="flex items-baseline justify-between mb-1">
                      <div>
                        <span className="block text-[10px] uppercase tracking-wider text-stone-500 font-semibold">
                          Mulai dari
                        </span>
                        <span className="text-sm font-mono font-bold text-brand-dark">Rp 3.850.000</span>
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                      <button
                        type="button"
                        onClick={() => {
                          const prod = products.find((p) => p.slug === 'koleksi-fjord-whisper' || p.name.toLowerCase().includes('fjord')) || products[0];
                          if (prod) {
                            setSelectedProduct(prod);
                            setIsDetailOpen(true);
                          } else {
                            document.getElementById('products')?.scrollIntoView({ behavior: 'smooth' });
                          }
                        }}
                        className="inline-flex items-center justify-center rounded-xl border border-brand-border bg-white px-3 py-2 text-xs font-semibold text-brand-dark hover:border-brand-dark hover:bg-stone-100 transition-colors shadow-sm text-center cursor-pointer"
                      >
                        Lihat Detail
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          const prod = products.find((p) => p.slug === 'koleksi-fjord-whisper') || products[0];
                          if (prod) handleAddToCart(prod);
                        }}
                        className="inline-flex items-center justify-center gap-1.5 rounded-xl bg-brand-dark text-white px-3 py-2 text-xs font-bold shadow-sm transition-all hover:bg-stone-800"
                      >
                        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                          <path d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span>+ Keranjang</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              {/* Collection Card 2 */}
              <div className="group rounded-3xl border border-stone-800 bg-white overflow-hidden shadow-lg flex flex-col justify-between hover:shadow-2xl transition-all duration-300 relative">
                <div className="absolute top-4 right-4 z-10">
                  <span className="rounded-full bg-brand-dark px-3 py-1 text-[10px] font-extrabold uppercase tracking-widest text-white shadow-sm">
                    Terlaris
                  </span>
                </div>
                <div className="p-6 pb-0">
                  <span className="text-[10px] uppercase font-bold tracking-widest text-stone-500">
                    Harmoni Arsitektural
                  </span>
                  <h3 className="font-display text-2xl font-bold text-brand-dark mt-1">Urban Elegance</h3>
                  <p className="mt-2 text-xs text-brand-muted leading-relaxed">
                    Siluet skulptural dan pelapis terstruktur yang dirancang untuk memperkaya ruang metropolitan modern.
                  </p>
                </div>
                <div className="p-6">
                  <div className="h-56 rounded-2xl overflow-hidden bg-brand-sand">
                    <img
                      alt="Koleksi Urban Elegance"
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                      src="https://lh3.googleusercontent.com/aida-public/AB6AXuA-Zu6bMFVJolDQeO2bHLBCTPGcwGDSK_maRF-61sRDT2L3w_kg1rpOQTfIlOLiVJyNlmPvR4TCe3oLlm7-JYNQB4O47g1o7qb4Ocxnw9OoSv9fsexI9clJ4Q3PikhqHCWfSkNBE1VbT3kPx-vOLDTfWdz06kJZ-VJ54Dk7OsKNuT6eRpb0gBgEjQ1ex-AbkOESE24nMLfxzOemYrlibggzxTwVH7a8ubL9aC8RS0a-"
                    />
                  </div>
                  <div className="mt-5 pt-3 border-t border-brand-border/60 flex flex-col gap-2.5">
                    <div className="flex items-baseline justify-between mb-1">
                      <div>
                        <span className="block text-[10px] uppercase tracking-wider text-stone-500 font-semibold">
                          Mulai dari
                        </span>
                        <span className="text-sm font-mono font-bold text-brand-dark">Rp 7.200.000</span>
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                      <button
                        type="button"
                        onClick={() => {
                          const prod = products.find((p) => p.slug === 'koleksi-urban-elegance' || p.name.toLowerCase().includes('urban') || p.name.toLowerCase().includes('sumba')) || products[1];
                          if (prod) {
                            setSelectedProduct(prod);
                            setIsDetailOpen(true);
                          } else {
                            document.getElementById('products')?.scrollIntoView({ behavior: 'smooth' });
                          }
                        }}
                        className="inline-flex items-center justify-center rounded-xl border border-brand-border bg-white px-3 py-2 text-xs font-semibold text-brand-dark hover:border-brand-dark hover:bg-stone-100 transition-colors shadow-sm text-center cursor-pointer"
                      >
                        Lihat Detail
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          const prod = products.find((p) => p.slug === 'koleksi-urban-elegance') || products[1];
                          if (prod) handleAddToCart(prod);
                        }}
                        className="inline-flex items-center justify-center gap-1.5 rounded-xl bg-brand-dark text-white px-3 py-2 text-xs font-bold shadow-sm transition-all hover:bg-stone-800"
                      >
                        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                          <path d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span>+ Keranjang</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              {/* Collection Card 3 */}
              <div className="group rounded-3xl border border-brand-border bg-white overflow-hidden shadow-sm flex flex-col justify-between hover:shadow-xl transition-all duration-300">
                <div className="p-6 pb-0">
                  <span className="text-[10px] uppercase font-bold tracking-widest text-stone-500">
                    Tekstur Hangat Alami
                  </span>
                  <h3 className="font-display text-2xl font-bold text-brand-dark mt-1">Cozy Haven</h3>
                  <p className="mt-2 text-xs text-brand-muted leading-relaxed">
                    Harmoni menenangkan dengan kain bouclé lembut, anyaman rotan, serta kayu keras alami pilihan.
                  </p>
                </div>
                <div className="p-6">
                  <div className="h-56 rounded-2xl overflow-hidden bg-brand-sand">
                    <img
                      alt="Koleksi Cozy Haven"
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                      src="https://lh3.googleusercontent.com/aida-public/AB6AXuAROIpGicWNYHZWzG4CIaU2Bv3uhryF-REEcdk-9wwrJfbt8JslQDdDLBd7r9gf9K8-SEB7Hyg0BZUJDpKGUaed3ihz-sI9zfCT8FmoAamUxzQoeIKJfwJZovYs7nEo-7Ot-Axur5F7XO5dzusuUCb-9b-G4qSJc5gO99hOU5gA9dtzuX6wK_1FLBoIb9GSBzAicZFU4-Wytm3XzbABWpS0Vbq56zmfM2pImrpnt7Kc"
                    />
                  </div>
                  <div className="mt-5 pt-3 border-t border-brand-border/60 flex flex-col gap-2.5">
                    <div className="flex items-baseline justify-between mb-1">
                      <div>
                        <span className="block text-[10px] uppercase tracking-wider text-stone-500 font-semibold">
                          Mulai dari
                        </span>
                        <span className="text-sm font-mono font-bold text-brand-dark">Rp 5.600.000</span>
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                      <button
                        type="button"
                        onClick={() => {
                          const prod = products.find((p) => p.slug === 'koleksi-cozy-haven' || p.name.toLowerCase().includes('cane') || p.name.toLowerCase().includes('haven')) || products[2];
                          if (prod) {
                            setSelectedProduct(prod);
                            setIsDetailOpen(true);
                          } else {
                            document.getElementById('products')?.scrollIntoView({ behavior: 'smooth' });
                          }
                        }}
                        className="inline-flex items-center justify-center rounded-xl border border-brand-border bg-white px-3 py-2 text-xs font-semibold text-brand-dark hover:border-brand-dark hover:bg-stone-100 transition-colors shadow-sm text-center cursor-pointer"
                      >
                        Lihat Detail
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          const prod = products.find((p) => p.slug === 'koleksi-cozy-haven') || products[2];
                          if (prod) handleAddToCart(prod);
                        }}
                        className="inline-flex items-center justify-center gap-1.5 rounded-xl bg-brand-dark text-white px-3 py-2 text-xs font-bold shadow-sm transition-all hover:bg-stone-800"
                      >
                        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                          <path d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span>+ Keranjang</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* BEGIN: FeatureHighlightsAndTimerBanner */}
        <section className="border-y border-brand-border bg-[#F8F8F7] py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-1 gap-12 lg:grid-cols-12 lg:items-center">
              {/* Left Column */}
              <div className="lg:col-span-6">
                <span className="block text-[11px] font-semibold uppercase tracking-widest text-stone-500 mb-2">
                  STANDAR &amp; PRESTASI
                </span>
                <h2 className="mt-3 font-display text-3xl font-extrabold tracking-tight text-brand-dark sm:text-4xl">
                  Kami Hadirkan Arsitektur Terbaik dan Kriya Abadi
                </h2>
                <p className="mt-4 text-base text-brand-muted">
                  Furnitur kantor ergonomis &amp; residensial memprioritaskan kenyamanan serta masa pakai panjang melalui mekanisme presisi, kayu kering oven, dan finishing ramah lingkungan.
                </p>
                <div className="mt-8 space-y-4">
                  <div className="flex items-start gap-3.5">
                    <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-dark text-white">
                      <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" />
                      </svg>
                    </div>
                    <div>
                      <h4 className="font-display text-sm font-bold text-brand-dark">
                        Instalasi Sarung Tangan Putih Gratis
                      </h4>
                      <p className="text-xs text-brand-muted">
                        Tim instalasi profesional berskala nasional lengkap dengan pembersihan sisa kemasan serta kalibrasi perataan lantai.
                      </p>
                    </div>
                  </div>

                  <div className="flex items-start gap-3.5">
                    <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-dark text-white">
                      <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" />
                      </svg>
                    </div>
                    <div>
                      <h4 className="font-display text-sm font-bold text-brand-dark">
                        Garansi Konstruksi 10 Tahun
                      </h4>
                      <p className="text-xs text-brand-muted">
                        Sambungan purus dan lubang (mortise and tenon) kokoh yang tahan terhadap pergantian cuaca serta pemakaian bertahun-tahun.
                      </p>
                    </div>
                  </div>

                  <div className="flex items-start gap-3.5">
                    <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-dark text-white">
                      <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" />
                      </svg>
                    </div>
                    <div>
                      <h4 className="font-display text-sm font-bold text-brand-dark">
                        Kustomisasi Kain &amp; Tekstur Sesuai Selera
                      </h4>
                      <p className="text-xs text-brand-muted">
                        Pilih dari 40+ variasi kain bouclé bertekstur, linen Belgia, dan beragam pewarnaan kayu jati atau ash yang telah dikeringkan secara presisi.
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Right Column: Deep Slate Limited Deal Banner */}
              <div className="charcoal-card-pattern relative overflow-hidden rounded-arch p-8 text-white shadow-2xl lg:col-span-6 border border-stone-800">
                <div className="pointer-events-none absolute -bottom-10 -right-10 h-72 w-72 rounded-full bg-white/5 blur-3xl"></div>
                <div className="relative z-10 flex flex-col justify-between">
                  <div>
                    <span className="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-stone-300">
                      Slot Produksi Terbatas
                    </span>
                    <h3 className="mt-4 font-display text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                      Slot Pengerjaan Proyek Kustom Musim Ini
                    </h3>
                    <p className="mt-3 text-sm leading-relaxed text-stone-300">
                      Workshop kriya kami menerima kuota terbatas per siklus produksi demi menjaga kualitas sambungan kayu, finishing alami, dan presisi setiap komisi arsitektural.
                    </p>
                  </div>
                  <div className="mt-8 grid grid-cols-4 gap-3 text-center">
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-md shadow-inner transition-transform duration-300 hover:scale-105">
                      <span className="block font-mono text-2xl font-black tracking-tight text-white">03</span>
                      <span className="mt-1 block text-[10px] font-medium uppercase tracking-wider text-stone-400">Slot Tersedia</span>
                    </div>
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-md shadow-inner transition-transform duration-300 hover:scale-105">
                      <span className="block font-mono text-2xl font-black tracking-tight text-white">Q2</span>
                      <span className="mt-1 block text-[10px] font-medium uppercase tracking-wider text-stone-400">Periode Kerja</span>
                    </div>
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-md shadow-inner transition-transform duration-300 hover:scale-105">
                      <span className="block font-mono text-2xl font-black tracking-tight text-white">06</span>
                      <span className="mt-1 block text-[10px] font-medium uppercase tracking-wider text-stone-400">Minggu Lead Time</span>
                    </div>
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-md shadow-inner transition-transform duration-300 hover:scale-105">
                      <span className="block font-mono text-2xl font-black tracking-tight text-white">100%</span>
                      <span className="mt-1 block text-[10px] font-medium uppercase tracking-wider text-stone-400">Bespoke</span>
                    </div>
                  </div>
                  <div className="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-6">
                    <div>
                      <span className="block text-[10px] font-bold uppercase tracking-widest text-stone-400">Status Pendaftaran</span>
                      <span className="font-mono text-base font-bold tracking-widest text-white">KOMISI TERBUKA</span>
                    </div>
                    <a className="group inline-flex items-center gap-2 rounded-full bg-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-brand-dark shadow-md transition-all duration-300 hover:bg-stone-200 hover:shadow-xl" href="#inquiry">
                      <span>Jadwalkan Konsultasi</span>
                      <svg className="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M14 5l7 7m0 0l-7 7m7-7H3" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" />
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* BEGIN: StorytellingAndMateriality */}
        <section className="py-20 lg:py-28 bg-white" id="about">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-4xl text-center">
              <span className="block text-[11px] font-semibold uppercase tracking-widest text-stone-500 mb-2">
                ATELIER &amp; CRAFTSMANSHIP
              </span>
              <h2 className="mt-4 font-display text-3xl font-extrabold leading-tight text-brand-dark sm:text-5xl">
                Dikelola dan diproduksi langsung oleh perajin lokal —
                <span className="inline-flex items-center align-middle mx-1.5 h-9 w-20 overflow-hidden rounded-full border border-brand-border bg-stone-200">
                  <img
                    alt="Detail sambungan kriya kayu"
                    className="h-full w-full object-cover"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuAuqV5Z8cPfIDTcVyXevn-386sXMEWWRzH0UPa6fwUCKWjB_SpAQP2VMEUqjh8AJbysBhLa9f5pUhLPRpM3LyYNujPY8vglbkPgf7xVIim-eYPJxvzzncLHrw_xBLw3876gi72O6UW0op536zO5FS_r0rDWcNHlSXiT_qE2I_-fE0bQ-ZhrNCXsP74bIJ-YXpqznzGL_RpAiu6lOQ-DGDRhcGNykpr8mHA4CKvXxJ5h"
                  />
                </span>
                berspesialisasi dalam desain pesanan khusus, arsitektur, dan kriya kayu warisan leluhur.
              </h2>
              <p className="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-brand-muted">
                Didirikan di Jawa Tengah dan disempurnakan untuk interior kontemporer global. Kami memadukan sambungan purus tradisional dengan minimalisme modern untuk menghasilkan karya yang menyuntikkan ketenangan ke ruang arsitektural.
              </p>
            </div>

            {/* 3 Tactile Material Cards */}
            <div className="mt-16 grid grid-cols-1 gap-8 md:grid-cols-3">
              <div className="rounded-3xl border border-brand-border bg-[#F8F8F7] p-6 shadow-sm">
                <div className="h-48 overflow-hidden rounded-2xl bg-stone-100">
                  <img
                    alt="Kayu jati lestari pilihan Indonesia"
                    className="h-full w-full object-cover"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCwtm6p6EfsznP6HWDisVgyupk3JUSFLhWNzhsH6NUoqRojIMMjTW0vFxCt_yFL0286cO2IzNerCcZy2qhcMU5sMQ12-8vN4YcaT6rruTsdHC7lPpCYGpZIutcT2ONiASIcy4T9LEbYn7zLozffL1pasQIWaRmV-oxcGSulx8OxfGTvStwdSxUBgjOidN1mVPv24og2LhYCeAQJyWiAn9McvWQbTgL4s2Ek4LxvQnI2"
                  />
                </div>
                <h3 className="mt-5 font-display text-lg font-bold text-brand-dark">
                  Kayu Jati Grade-A Oven (Kiln-Dried)
                </h3>
                <p className="mt-2 text-xs leading-relaxed text-brand-muted">
                  Bersumber secara bertanggung jawab dari perkebunan lestari bersertifikasi. Dikeringkan hingga kadar air 8–10% untuk mencegah perubahan bentuk musiman.
                </p>
              </div>

              <div className="rounded-3xl border border-brand-border bg-[#F8F8F7] p-6 shadow-sm">
                <div className="h-48 overflow-hidden rounded-2xl bg-stone-100">
                  <img
                    alt="Anyaman rotan dan cane alami"
                    className="h-full w-full object-cover"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuD6NqINiu_my8tltaAC49N7dvJ0VWGO4gpalDW7CukP7qF-0GY3RaujCv-MGPvox8GUs_5jLu5Ee7F8sDUCKHVNpmq_NQ4FAEyistXU_KYz6bBH4WJR3LFqX88IfDbCqbAg5rN1-p1u600UijfKW9XDwemnEXsoq4pTJlBIzWxhDVBSZ5nGA2_mhBwOvldtgHeLO7DruujA5Tt6RowehjGwhsUFnnMhkKDdxYYiMoSr"
                  />
                </div>
                <h3 className="mt-5 font-display text-lg font-bold text-brand-dark">
                  Anyaman Rotan &amp; Cane Alami
                </h3>
                <p className="mt-2 text-xs leading-relaxed text-brand-muted">
                  Dianyam tangan dengan presisi oleh perajin berpengalaman, menghadirkan kelembutan akustik dan sirkulasi udara alami pada rak penyimpanan.
                </p>
              </div>

              <div className="rounded-3xl border border-brand-border bg-[#F8F8F7] p-6 shadow-sm">
                <div className="h-48 overflow-hidden rounded-2xl bg-stone-100">
                  <img
                    alt="Kain pelapis bouclé dan linen organik"
                    className="h-full w-full object-cover"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuAIV3BmlHlIccmXUhAT-Eucrd9fAIqB_9efIBEp9Of2PpfC6ihQpuGn8sMAXZHDQMK2tLXKV_J_udDmJetDKlnWWT22v8rrrK7mYTOCnIzLqXBPxcxJHUHT2YzKIw2EBT4y0iMgYPpVXZj_3w9_IwBGZM2n3bu51f8GWf8OF775n1DFOF8W0aZNRcIEIvZ2xtFLMfJfbBXKsp9L_NGzJ5_Brc81I2x7z_lo_5GJBVtN"
                  />
                </div>
                <h3 className="mt-5 font-display text-lg font-bold text-brand-dark">
                  Bouclé &amp; Linen Arsitektural
                </h3>
                <p className="mt-2 text-xs leading-relaxed text-brand-muted">
                  Tekstil tahan aus dengan kerapatan tinggi serta linen organik bebas klorin yang teruji durabilitas dan kelembutan seratnya.
                </p>
              </div>
            </div>
          </div>
        </section>

        {/* BEGIN: ProjectsGallery */}
        <section className="border-t border-brand-border bg-[#F3F3F2] py-20" id="projects-showcase">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
              <div>
                <span className="block text-[11px] font-semibold uppercase tracking-widest text-stone-500 mb-2">
                  PORTOFOLIO PROYEK
                </span>
                <h2 className="mt-2 font-display text-3xl font-extrabold tracking-tight text-brand-dark sm:text-4xl">
                  Ruang yang Dirancang Bersama TATAMEBEL
                </h2>
              </div>
              <p className="max-w-md text-xs text-brand-muted">
                Eksplorasi proyek residensial dan hospitality mewah yang telah kami wujudkan dengan lini furnitur arsitektural pesanan khusus kami.
              </p>
            </div>
            <div className="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
              <div className="group relative overflow-hidden rounded-3xl border border-brand-border bg-white shadow-sm">
                <div className="h-80 w-full overflow-hidden">
                  <img
                    alt="Villa Minimalis di Canggu"
                    className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuBXMdF7_dUBqbyXzG-qeyxLkSzCZ3LzpJHzdkIJXbr8qj3ogKPI5foMLXQOPXGJ7JKVO2oHHRRHd6XUzmnq1yQetZDEJjrIyyF7IkW_ll7JY3xgiaciqHCpo-wp3e2EImeS5spoa41IZs_Kb2SYmOzUdM1nqG-hkvvdyeNorgl7o5VlC3fpCf8TL4ey17YOXcLE77JSpIT4iqmuKVkD03GWN8emnkBgIzie0c52xqTb"
                  />
                </div>
                <div className="p-5">
                  <span className="text-[11px] font-bold uppercase tracking-wider text-stone-500">
                    Residensial Mewah • Canggu
                  </span>
                  <h3 className="mt-1 font-display text-lg font-bold text-brand-dark">
                    Hunian The Teak Pavilion — Canggu
                  </h3>
                  <p className="mt-1 text-xs text-brand-muted">
                    Penyediaan furnitur lengkap termasuk meja makan kustom 12 kursi, daybed modular, dan lemari arsitektural.
                  </p>
                </div>
              </div>

              <div className="group relative overflow-hidden rounded-3xl border border-brand-border bg-white shadow-sm">
                <div className="h-80 w-full overflow-hidden">
                  <img
                    alt="Studio Arsitektur di Tokyo"
                    className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuAvfeTUz0tnp3C-vIrGzJKZAUQITLyU-DfWKKYgOXgJczZ4QoEqHqVpmw8wyXzxFaerooL6OrRACaBbQHcp9_-AOzYMn2xWOH81p1C37FONrco9DcwlUlFx_F8FVjBFx9cKhFtwZiCuyEyw57NvoPZuYpx1xSLsBuOaEDl7VlDXJgl-XzxmJ-8C-O9P-wxWvrt3U5WxX5i5DYwuPCwd3vukTxEWIZdI1hUVkQy34xVX"
                  />
                </div>
                <div className="p-5">
                  <span className="text-[11px] font-bold uppercase tracking-wider text-stone-500">
                    Studio Kreatif • Tokyo
                  </span>
                  <h3 className="mt-1 font-display text-lg font-bold text-brand-dark">
                    Atelier Arsitektur Komorebi — Tokyo
                  </h3>
                  <p className="mt-1 text-xs text-brand-muted">
                    Meja kerja kayu oak solid dengan manajemen kabel tersembunyi dan kursi kerja ergonomis untuk 24 arsitek.
                  </p>
                </div>
              </div>

              <div className="group relative overflow-hidden rounded-3xl border border-brand-border bg-white shadow-sm">
                <div className="h-80 w-full overflow-hidden">
                  <img
                    alt="Lounge Penthouse di Melbourne"
                    className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCM79uEXEzbsHWTd7D2BrMO1a26we2zHsSrtsvIuGFzoV7rtA0GljhJ5cIah_s_kxNVVyLrI5Y59xzQN4wxFu3OmwjZmkyO5zRKhjqmtkMQNyLyRKKKwLjTI9XHV2zCAqHE5pVd4yjynH5Aukhvj1oDe1d_SCYF2EraHTcW_eMooqegRjIAXu4O-WMp6-QLJA3v5nb_XVviFcAOi4vOc_JBnGT3eBEwkercQbYNRn39"
                  />
                </div>
                <div className="p-5">
                  <span className="text-[11px] font-bold uppercase tracking-wider text-stone-500">
                    Hospitality &amp; Kafe • Melbourne
                  </span>
                  <h3 className="mt-1 font-display text-lg font-bold text-brand-dark">
                    The Calyx Executive Lounge — Melbourne
                  </h3>
                  <p className="mt-1 text-xs text-brand-muted">
                    Banquette melengkung bergaris tegas dan komposisi meja kopi rendah rotan berpadu kayu jati.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* BEGIN: Testimonials Section */}
        <section className="py-20 lg:py-28 bg-[#F8F8F7] border-t border-brand-border" id="feedback">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="flex flex-col sm:flex-row sm:items-end justify-between mb-14 gap-4">
              <div>
                <span className="block text-[11px] font-semibold uppercase tracking-widest text-stone-500 mb-2">
                  TESTIMONI KLIEN
                </span>
                <h2 className="mt-2 font-display text-3xl sm:text-4xl font-extrabold text-brand-dark">
                  Ulasan Berharga dari Klien Kami
                </h2>
              </div>
              <div className="flex items-center gap-3">
                <a className="rounded-full border border-brand-dark bg-white px-5 py-2 text-xs font-bold uppercase tracking-wider text-brand-dark hover:bg-brand-dark hover:text-white transition-all" href="#projects-showcase">
                  Lihat Semua
                </a>
              </div>
            </div>
            <div className="rounded-3xl border border-brand-border bg-white p-8 md:p-12 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
              <div className="lg:col-span-7 flex flex-col justify-between">
                <div className="text-stone-300 font-display text-6xl leading-none select-none">“</div>
                <blockquote className="font-display text-xl sm:text-2xl text-brand-dark leading-relaxed font-medium mt-2">
                  Sejak pertama kali bersantai di sofa ini, saya langsung merasakan kenyamanan optimal dan kemewahan yang tenang. Desainnya menyatu sempurna sebagai titik fokal ruang tamu saya. Kualitas sambungan kayunya benar-benar luar biasa.
                </blockquote>
                <div className="mt-8 flex items-center gap-4 pt-6 border-t border-brand-border/60">
                  <div className="h-12 w-12 rounded-full bg-stone-300 overflow-hidden">
                    <img
                      alt="Mia Wilson"
                      className="w-full h-full object-cover"
                      src="https://lh3.googleusercontent.com/aida-public/AB6AXuAuqV5Z8cPfIDTcVyXevn-386sXMEWWRzH0UPa6fwUCKWjB_SpAQP2VMEUqjh8AJbysBhLa9f5pUhLPRpM3LyYNujPY8vglbkPgf7xVIim-eYPJxvzzncLHrw_xBLw3876gi72O6UW0op536zO5FS_r0rDWcNHlSXiT_qE2I_-fE0bQ-ZhrNCXsP74bIJ-YXpqznzGL_RpAiu6lOQ-DGDRhcGNykpr8mHA4CKvXxJ5h"
                    />
                  </div>
                  <div>
                    <span className="block font-display text-base font-bold text-brand-dark">Mia Wilson</span>
                    <span className="block text-xs text-brand-muted">Arsitek Utama di Studio Apex, Sydney</span>
                  </div>
                </div>
              </div>
              <div className="lg:col-span-5">
                <div className="w-full h-[340px] rounded-2xl overflow-hidden shadow-sm border border-brand-border bg-brand-sand group">
                  <img
                    alt="Ruang keluarga arsitektural yang ditata dengan furnitur TATAMEBEL"
                    className="w-full h-[340px] object-cover rounded-2xl transition-transform duration-700 group-hover:scale-105"
                    src="https://lh3.googleusercontent.com/aida/AEtjO1WaSc9fUMNclmMAssWLU0JY5Q71S_Hevt8un9Kppv1jX3CCJvAO17sJ9L4ow1w63u_JPuyWWDZ26iP4W8hdp7LXEx54T_9NBb54E3vMstJwPIbug-8OFW_pqsaTrsMbHfy_LzGTbA7Y-eP_HWwOayp_Ed0MBPmqTWt2chRC5h4o-bh-Kcp9Rt8zdpW2dL7ZPYdg0HJAfCij6QFmx8DYa62rgVmlPjBcI54hbPAjeCAgDA"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* BEGIN: FAQSection */}
        <section className="border-t border-brand-border bg-white py-20" id="faq">
          <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div className="text-center">
              <span className="block text-[11px] font-semibold uppercase tracking-widest text-stone-500 mb-2">
                TANYA JAWAB
              </span>
              <h2 className="mt-2 font-display text-3xl font-extrabold tracking-tight text-brand-dark sm:text-4xl">
                Pertanyaan yang Sering Diajukan
              </h2>
              <p className="mt-2 text-sm text-brand-muted">
                Semua hal yang perlu Anda ketahui mengenai pemesanan kustom, material, garansi, dan jadwal pengiriman.
              </p>
            </div>
            <div className="mt-12 space-y-4">
              <details className="group rounded-2xl border border-brand-border bg-[#F8F8F7] p-5 transition-all open:bg-stone-100">
                <summary className="flex cursor-pointer list-none items-center justify-between font-display text-base font-bold text-brand-dark">
                  <span>Bisakah saya menyesuaikan dimensi dan finishing kayu dari katalog standar?</span>
                  <span className="ml-4 flex h-6 w-6 items-center justify-center rounded-full bg-white text-brand-dark transition group-open:rotate-180">
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M19 9l-7 7-7-7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                    </svg>
                  </span>
                </summary>
                <p className="mt-3 text-xs leading-relaxed text-brand-muted">
                  Ya, tentu. Setiap produk diproduksi berdasarkan pesanan di workshop utama kami. Anda dapat menyesuaikan dimensi secara presisi hingga satuan sentimeter, menentukan pilihan warna kayu (jati natural, smoked charcoal, bleached oak), serta menyediakan kain pelapis sendiri (COM).
                </p>
              </details>

              <details className="group rounded-2xl border border-brand-border bg-[#F8F8F7] p-5 transition-all open:bg-stone-100">
                <summary className="flex cursor-pointer list-none items-center justify-between font-display text-base font-bold text-brand-dark">
                  <span>Berapa lama estimasi waktu produksi standar untuk furnitur pesanan khusus (bespoke)?</span>
                  <span className="ml-4 flex h-6 w-6 items-center justify-center rounded-full bg-white text-brand-dark transition group-open:rotate-180">
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M19 9l-7 7-7-7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                    </svg>
                  </span>
                </summary>
                <p className="mt-3 text-xs leading-relaxed text-brand-muted">
                  Untuk pesanan satuan standar membutuhkan 4 hingga 6 minggu sejak persetujuan gambar teknis hingga tahap pengemasan. Untuk proyek skala arsitektur penuh (lebih dari 10 unit kustom), estimasi pengerjaan kami adalah 8 hingga 10 minggu.
                </p>
              </details>

              <details className="group rounded-2xl border border-brand-border bg-[#F8F8F7] p-5 transition-all open:bg-stone-100">
                <summary className="flex cursor-pointer list-none items-center justify-between font-display text-base font-bold text-brand-dark">
                  <span>Bagaimana prosedur pengiriman internasional dan layanan instalasi sarung tangan putih?</span>
                  <span className="ml-4 flex h-6 w-6 items-center justify-center rounded-full bg-white text-brand-dark transition group-open:rotate-180">
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M19 9l-7 7-7-7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                    </svg>
                  </span>
                </summary>
                <p className="mt-3 text-xs leading-relaxed text-brand-muted">
                  Kami melayani pengiriman DDP (Delivered Duty Paid) ke berbagai penjuru dunia termasuk Amerika Utara, Eropa, Australia, dan Asia. Seluruh furnitur dikemas dengan peti kayu bersertifikasi fitosanitari dan diantar langsung ke ruangan yang Anda tentukan beserta perakitan dan pembersihan sisa kemasan.
                </p>
              </details>

              <details className="group rounded-2xl border border-brand-border bg-[#F8F8F7] p-5 transition-all open:bg-stone-100">
                <summary className="flex cursor-pointer list-none items-center justify-between font-display text-base font-bold text-brand-dark">
                  <span>Apakah semua jenis kayu yang digunakan bersertifikasi lestari dan legal (FSC/SVLK)?</span>
                  <span className="ml-4 flex h-6 w-6 items-center justify-center rounded-full bg-white text-brand-dark transition group-open:rotate-180">
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M19 9l-7 7-7-7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                    </svg>
                  </span>
                </summary>
                <p className="mt-3 text-xs leading-relaxed text-brand-muted">
                  Pasti, tanpa terkecuali. Seluruh kayu yang kami olah bersumber resmi dari hutan tanaman industri yang dikelola secara lestari dan mengantongi sertifikasi legalitas kayu SVLK resmi serta pengawasan rantai pasok ketat.
                </p>
              </details>
            </div>
          </div>
        </section>

        {/* BEGIN: ShowroomsSection */}
        <section className="py-20 lg:py-28 bg-[#F3F3F2] border-t border-brand-border" id="showrooms">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
              <div>
                <span className="block text-[11px] font-semibold uppercase tracking-widest text-stone-500 mb-2">
                  RUANG &amp; LOKASI
                </span>
                <h2 className="mt-3 font-display text-3xl sm:text-5xl font-extrabold tracking-tight text-brand-dark">
                  Kunjungi Showroom &amp; Workshop Kami
                </h2>
                <p className="mt-3 max-w-xl text-sm leading-relaxed text-brand-muted">
                  Rasakan langsung tekstur material kayu, kain pelapis, dan konsultasikan proyek Anda bersama tim arsitek kami dalam suasana eksklusif.
                </p>
              </div>
              <div className="flex items-center gap-3">
                <a
                  className="inline-flex items-center gap-2 rounded-full border border-brand-dark bg-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-brand-dark transition-all duration-300 hover:bg-brand-dark hover:text-white"
                  href="#inquiry"
                >
                  <span>Jadwalkan Konsultasi</span>
                  <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M17 8l4 4m0 0l-4 4m4-4H3" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                  </svg>
                </a>
              </div>
            </div>

            <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
              {/* Showroom 1 */}
              <div className="group overflow-hidden rounded-3xl border border-brand-border bg-white shadow-sm transition-all duration-300 hover:shadow-xl hover:border-stone-400 flex flex-col">
                <div className="relative h-72 w-full overflow-hidden bg-stone-100">
                  <img
                    alt="Showroom & Laboratorium Material Jakarta"
                    className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuAvfeTUz0tnp3C-vIrGzJKZAUQITLyU-DfWKKYgOXgJczZ4QoEqHqVpmw8wyXzxFaerooL6OrRACaBbQHcp9_-AOzYMn2xWOH81p1C37FONrco9DcwlUlFx_F8FVjBFx9cKhFtwZiCuyEyw57NvoPZuYpx1xSLsBuOaEDl7VlDXJgl-XzxmJ-8C-O9P-wxWvrt3U5WxX5i5DYwuPCwd3vukTxEWIZdI1hUVkQy34xVX"
                  />
                  <div className="absolute top-4 left-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-brand-dark backdrop-blur-sm shadow-sm">
                    Flagship Showroom
                  </div>
                  <div className="absolute bottom-4 right-4 rounded-full bg-[#191A1C]/80 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm flex items-center gap-1.5">
                    <span className="h-2 w-2 rounded-full bg-emerald-400"></span> Buka untuk Kunjungan
                  </div>
                </div>
                <div className="p-8 flex flex-col flex-1 justify-between">
                  <div className="space-y-5">
                    <div>
                      <span className="text-[10px] font-bold uppercase tracking-widest text-stone-500">
                        Jakarta Selatan • Kebayoran Baru
                      </span>
                      <h3 className="mt-1 font-display text-2xl font-bold text-brand-dark">
                        Showroom &amp; Laboratorium Material Jakarta
                      </h3>
                      <p className="mt-1 text-xs text-brand-muted leading-relaxed flex items-center gap-1.5">
                        <svg className="h-4 w-4 text-stone-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                          <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                        </svg>
                        Jl. Senopati No. 81, Kebayoran Baru, Jakarta Selatan 12190
                      </p>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-2xl bg-[#F8F8F7] p-4 text-xs">
                      <div className="space-y-1">
                        <span className="block font-semibold text-brand-dark">Jam Operasional</span>
                        <span className="block text-brand-muted">Sen – Sab: 10:00 – 20:00</span>
                        <span className="block text-brand-muted">Min: 11:00 – 18:00</span>
                      </div>
                      <div className="space-y-1">
                        <span className="block font-semibold text-brand-dark">Kontak &amp; Janji Temu</span>
                        <span className="block text-brand-muted">+62 21 5792 0188</span>
                        <span className="block text-brand-dark font-medium underline">jakarta@tatamebel.com</span>
                      </div>
                    </div>
                    <div className="space-y-1.5">
                      <span className="block text-[11px] font-bold uppercase tracking-wider text-brand-dark">Fasilitas Utama</span>
                      <div className="flex flex-wrap gap-2">
                        <span className="rounded-full border border-brand-border bg-white px-3 py-1 text-[11px] text-brand-muted font-medium">Vinyet ruang terkurasi</span>
                        <span className="rounded-full border border-brand-border bg-white px-3 py-1 text-[11px] text-brand-muted font-medium">Perpustakaan serat kayu &amp; kain</span>
                        <span className="rounded-full border border-brand-border bg-white px-3 py-1 text-[11px] text-brand-muted font-medium">Konsultasi interior privat</span>
                      </div>
                    </div>
                  </div>
                  <div className="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-brand-border/60 pt-5">
                    <span className="text-xs text-brand-muted">Slot kunjungan privat tersedia setiap hari</span>
                    <a className="inline-flex items-center gap-2 rounded-full bg-brand-dark px-6 py-2.5 text-xs font-bold text-white transition-all hover:bg-stone-800 shadow-md" href="#inquiry">
                      <span>Jadwalkan Kunjungan ke Studio</span>
                      <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M14 5l7 7m0 0l-7 7m7-7H3" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" />
                      </svg>
                    </a>
                  </div>
                </div>
              </div>

              {/* Showroom 2 */}
              <div className="group overflow-hidden rounded-3xl border border-brand-border bg-white shadow-sm transition-all duration-300 hover:shadow-xl hover:border-stone-400 flex flex-col">
                <div className="relative h-72 w-full overflow-hidden bg-stone-100">
                  <img
                    alt="Atelier Kriya Kayu Jepara"
                    className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuBXMdF7_dUBqbyXzG-qeyxLkSzCZ3LzpJHzdkIJXbr8qj3ogKPI5foMLXQOPXGJ7JKVO2oHHRRHd6XUzmnq1yQetZDEJjrIyyF7IkW_ll7JY3xgiaciqHCpo-wp3e2EImeS5spoa41IZs_Kb2SYmOzUdM1nqG-hkvvdyeNorgl7o5VlC3fpCf8TL4ey17YOXcLE77JSpIT4iqmuKVkD03GWN8emnkBgIzie0c52xqTb"
                  />
                  <div className="absolute top-4 left-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-brand-dark backdrop-blur-sm shadow-sm">
                    Atelier Kriya &amp; Pusat Produksi
                  </div>
                  <div className="absolute bottom-4 right-4 rounded-full bg-[#191A1C]/80 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm flex items-center gap-1.5">
                    <span className="h-2 w-2 rounded-full bg-emerald-400"></span> Buka untuk Kunjungan
                  </div>
                </div>
                <div className="p-8 flex flex-col flex-1 justify-between">
                  <div className="space-y-5">
                    <div>
                      <span className="text-[10px] font-bold uppercase tracking-widest text-stone-500">
                        Jawa Tengah • Sentra Warisan Budaya
                      </span>
                      <h3 className="mt-1 font-display text-2xl font-bold text-brand-dark">
                        Atelier Kriya Kayu Jepara
                      </h3>
                      <p className="mt-1 text-xs text-brand-muted leading-relaxed flex items-center gap-1.5">
                        <svg className="h-4 w-4 text-stone-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                          <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                        </svg>
                        Jl. Pemuda No. 12, Tahunan, Jepara, Jawa Tengah 59427
                      </p>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-2xl bg-[#F8F8F7] p-4 text-xs">
                      <div className="space-y-1">
                        <span className="block font-semibold text-brand-dark">Jam Operasional</span>
                        <span className="block text-brand-muted">Sen – Jum: 08:30 – 17:00</span>
                        <span className="block text-brand-muted">Sab: Berdasarkan Janji Temu</span>
                      </div>
                      <div className="space-y-1">
                        <span className="block font-semibold text-brand-dark">Kontak &amp; Janji Temu</span>
                        <span className="block text-brand-muted">+62 291 591 0422</span>
                        <span className="block text-brand-dark font-medium underline">atelier@tatamebel.com</span>
                      </div>
                    </div>
                    <div className="space-y-1.5">
                      <span className="block text-[11px] font-bold uppercase tracking-wider text-brand-dark">Fasilitas Utama</span>
                      <div className="flex flex-wrap gap-2">
                        <span className="rounded-full border border-brand-border bg-white px-3 py-1 text-[11px] text-brand-muted font-medium">Demonstrasi sambungan kayu</span>
                        <span className="rounded-full border border-brand-border bg-white px-3 py-1 text-[11px] text-brand-muted font-medium">Inspeksi oven pengering kayu</span>
                        <span className="rounded-full border border-brand-border bg-white px-3 py-1 text-[11px] text-brand-muted font-medium">Area seleksi papan kayu solid</span>
                      </div>
                    </div>
                  </div>
                  <div className="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-brand-border/60 pt-5">
                    <span className="text-xs text-brand-muted">Tur langsung ke lantai produksi pengrajin</span>
                    <a className="inline-flex items-center gap-2 rounded-full bg-brand-dark px-6 py-2.5 text-xs font-bold text-white transition-all hover:bg-stone-800 shadow-md" href="#inquiry">
                      <span>Jadwalkan Tur Workshop</span>
                      <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M14 5l7 7m0 0l-7 7m7-7H3" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" />
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* BEGIN: InquiryFormSection */}
        <section className="py-20 lg:py-28 bg-white border-t border-brand-border" id="inquiry">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="relative overflow-hidden rounded-arch border border-brand-border bg-[#F8F8F7] p-8 shadow-sm md:p-14">
              <div className="grid grid-cols-1 gap-12 lg:grid-cols-12">
                <div className="lg:col-span-5">
                  <span className="block text-[11px] font-semibold uppercase tracking-widest text-stone-500 mb-2">
                    KONSULTASI &amp; PROYEK
                  </span>
                  <h2 className="mt-3 font-display text-3xl font-extrabold text-brand-dark sm:text-4xl">
                    Formulir Konsultasi &amp; Pengajuan Proyek Khusus
                  </h2>
                  <p className="mt-4 text-sm leading-relaxed text-brand-muted">
                    Diskusikan visi proyek hunian, komersial, atau pesanan arsitektural Anda. Direktur desain dan tim teknis kami akan meninjau denah serta memberikan tanggapan dalam 24 jam.
                  </p>
                  <div className="mt-8 space-y-3 text-xs text-brand-muted">
                    <div className="flex items-center gap-3">
                      <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-dark text-white font-bold">1</span>
                      <span>Konsultasi konsep, tata ruang 3D &amp; pengiriman sampel material kayu</span>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-dark text-white font-bold">2</span>
                      <span>Pengembangan gambar kerja CAD presisi dan penentuan detail sambungan</span>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-dark text-white font-bold">3</span>
                      <span>Pengerjaan di workshop kriya dengan laporan progres berkala</span>
                    </div>
                  </div>
                </div>

                <div className="lg:col-span-7">
                  {inquirySuccess ? (
                    <div className="rounded-2xl border border-emerald-300 bg-emerald-50 p-6 text-center">
                      <div className="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center mx-auto mb-3">
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                      </div>
                      <h4 className="font-display text-lg font-bold text-emerald-900">
                        Pengajuan Proyek Berhasil Terkirim!
                      </h4>
                      <p className="text-xs text-emerald-700 mt-1.5 max-w-md mx-auto">
                        Terima kasih. Formulir proyek Anda telah tercatat di sistem atelier TATAMEBEL. Tim desain kami akan menghubungi Anda dalam 24 jam.
                      </p>
                      <button
                        type="button"
                        onClick={() => setInquirySuccess(false)}
                        className="mt-5 inline-flex items-center justify-center rounded-full bg-emerald-700 px-6 py-2 text-xs font-bold uppercase tracking-wider text-white hover:bg-emerald-800 transition-colors"
                      >
                        Kirim Pengajuan Baru
                      </button>
                    </div>
                  ) : (
                    <form onSubmit={handleInquirySubmit} className="space-y-4">
                      {inquiryError && (
                        <div className="p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700">
                          {inquiryError}
                        </div>
                      )}

                      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                          <label className="block text-xs font-bold uppercase tracking-wider text-brand-dark">
                            Nama Lengkap / Studio
                          </label>
                          <input
                            type="text"
                            required
                            value={inquiryForm.name}
                            onChange={(e) => setInquiryForm({ ...inquiryForm, name: e.target.value })}
                            placeholder="cth. Budi Santoso / Studio Arsitek"
                            className="mt-1 w-full rounded-xl border border-brand-border bg-white px-4 py-3 text-xs font-medium text-brand-dark focus:border-brand-dark focus:ring-1 focus:ring-brand-dark outline-none"
                          />
                        </div>
                        <div>
                          <label className="block text-xs font-bold uppercase tracking-wider text-brand-dark">
                            Alamat Email
                          </label>
                          <input
                            type="email"
                            required
                            value={inquiryForm.email}
                            onChange={(e) => setInquiryForm({ ...inquiryForm, email: e.target.value })}
                            placeholder="budi@studio.com"
                            className="mt-1 w-full rounded-xl border border-brand-border bg-white px-4 py-3 text-xs font-medium text-brand-dark focus:border-brand-dark focus:ring-1 focus:ring-brand-dark outline-none"
                          />
                        </div>
                        <div>
                          <label className="block text-xs font-bold uppercase tracking-wider text-brand-dark">
                            Nomor WhatsApp / Telp
                          </label>
                          <input
                            type="tel"
                            value={inquiryForm.phone}
                            onChange={(e) => setInquiryForm({ ...inquiryForm, phone: e.target.value })}
                            placeholder="0812-xxxx-xxxx"
                            className="mt-1 w-full rounded-xl border border-brand-border bg-white px-4 py-3 text-xs font-medium text-brand-dark focus:border-brand-dark focus:ring-1 focus:ring-brand-dark outline-none"
                          />
                        </div>
                      </div>

                      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                          <label className="block text-xs font-bold uppercase tracking-wider text-brand-dark">
                            Jenis Proyek
                          </label>
                          <select
                            value={inquiryForm.project_type}
                            onChange={(e) => setInquiryForm({ ...inquiryForm, project_type: e.target.value })}
                            className="mt-1 w-full rounded-xl border border-brand-border bg-white px-4 py-3 text-xs font-medium text-brand-dark focus:border-brand-dark focus:ring-1 focus:ring-brand-dark outline-none"
                          >
                            <option>Residensial (Hunian Pribadi)</option>
                            <option>Komersial &amp; Hospitality (Hotel/Resto/Kafe)</option>
                            <option>Kolaborasi Kantor Arsitektur &amp; Interior</option>
                            <option>Kustom Satuan (Statement Furniture)</option>
                          </select>
                        </div>
                        <div>
                          <label className="block text-xs font-bold uppercase tracking-wider text-brand-dark">
                            Estimasi Garis Waktu Proyek
                          </label>
                          <select
                            value={inquiryForm.timeline}
                            onChange={(e) => setInquiryForm({ ...inquiryForm, timeline: e.target.value })}
                            className="mt-1 w-full rounded-xl border border-brand-border bg-white px-4 py-3 text-xs font-medium text-brand-dark focus:border-brand-dark focus:ring-1 focus:ring-brand-dark outline-none"
                          >
                            <option>Segera (&lt; 1 bulan)</option>
                            <option>1–3 Bulan</option>
                            <option>3–6 Bulan (Fase Perencanaan)</option>
                          </select>
                        </div>
                      </div>

                      <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-brand-dark">
                          Dimensi, Kebutuhan Material &amp; Deskripsi Proyek
                        </label>
                        <textarea
                          rows="4"
                          required
                          value={inquiryForm.description}
                          onChange={(e) => setInquiryForm({ ...inquiryForm, description: e.target.value })}
                          placeholder="Jelaskan denah ruang, preferensi jenis kayu (jati, ash, dsb.), jumlah unit, perkiraan anggaran, atau tautan referensi desain..."
                          className="mt-1 w-full rounded-xl border border-brand-border bg-white px-4 py-3 text-xs font-medium text-brand-dark focus:border-brand-dark focus:ring-1 focus:ring-brand-dark outline-none"
                        ></textarea>
                      </div>

                      <div className="pt-2">
                        <button
                          type="submit"
                          disabled={inquirySubmitting}
                          className="inline-flex w-full items-center justify-center gap-3 rounded-full bg-brand-dark px-8 py-3.5 text-xs font-bold uppercase tracking-wider text-white shadow-lg transition-all hover:bg-stone-800 disabled:opacity-50 sm:w-auto"
                        >
                          <span>{inquirySubmitting ? 'Mengirim...' : 'Kirim Pengajuan Proyek'}</span>
                          <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M14 5l7 7m0 0l-7 7m7-7H3" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                          </svg>
                        </button>
                      </div>
                    </form>
                  )}
                </div>
              </div>
            </div>
          </div>
        </section>
        {/* END: InquiryFormSection */}
      </main>

      {/* BEGIN: Footer */}
      <footer className="border-t border-stone-800 bg-[#191A1C] py-16 text-white" id="site-footer">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-5">
            {/* Brand Summary */}
            <div className="lg:col-span-2">
              <div className="flex items-center">
                <img
                  src="https://lh3.googleusercontent.com/aida-public/AB6AXuDAhGc8V9QoHrstx34NSkP7e08AY2xH-JNqJer32Pm--8uF7k3FRMVjO34IFhdZe4rogBxAaW3xzFdyzBJUZWZi2qrT0KMgcAPFACSBbbJ4pxKGfZIEjZ82viTXdzQ2mReqJGZoNY3G2A5-iinaKrxJfrrcBHCVdAHHcKiL7I-y1eIR-lJYh3AG6I7B1VzAHHjhRTc6Cq83HxRhZqKu9Lq1v_diM_Qm4ixYWZccpvaKEa6l5MYK5sN8UA"
                  alt="TATAMEBEL"
                  className="h-16 md:h-20 w-auto max-w-[340px] object-contain brightness-0 invert opacity-95 transition-opacity duration-300 hover:opacity-100"
                />
              </div>
              <p className="mt-4 max-w-sm text-xs leading-relaxed text-stone-400">
                TATAMEBEL merancang dan membuat furnitur kriya arsitektural berbahan kayu tropis bersertifikasi lestari. Dibuat dengan presisi untuk ruang kontemporer di seluruh dunia.
              </p>
            </div>

            {/* Col 1: Collections */}
            <div>
              <h4 className="font-display text-xs font-bold uppercase tracking-wider text-white">Koleksi</h4>
              <ul className="mt-4 space-y-2.5 text-xs text-stone-400">
                <li><a className="hover:text-white transition-colors" href="#products">Sofa &amp; Kursi Santai</a></li>
                <li><a className="hover:text-white transition-colors" href="#products">Meja Kerja &amp; Rak Buku</a></li>
                <li><a className="hover:text-white transition-colors" href="#products">Meja Makan Candi</a></li>
                <li><a className="hover:text-white transition-colors" href="#products">Kredensa Kayu Jati</a></li>
                <li><a className="hover:text-white transition-colors" href="#products">Koleksi Modular Verve</a></li>
              </ul>
            </div>

            {/* Col 2: Services & Studio */}
            <div>
              <h4 className="font-display text-xs font-bold uppercase tracking-wider text-white">Studio &amp; Layanan</h4>
              <ul className="mt-4 space-y-2.5 text-xs text-stone-400">
                <li><a className="hover:text-white transition-colors" href="#services">Spesifikasi Arsitektural</a></li>
                <li><a className="hover:text-white transition-colors" href="#services">Layanan Pesanan Khusus</a></li>
                <li><a className="hover:text-white transition-colors" href="#services">Sampel &amp; Material Perpustakaan</a></li>
                <li><a className="hover:text-white transition-colors" href="#projects-showcase">Pengadaan Proyek Komersial</a></li>
                <li><button type="button" onClick={() => setIsTrackingOpen(true)} className="hover:text-white transition-colors text-left">Lacak Progres Pesanan</button></li>
              </ul>
            </div>

            {/* Col 3: Workshop Locations */}
            <div>
              <h4 className="font-display text-xs font-bold uppercase tracking-wider text-white">Showroom &amp; Galeri</h4>
              <div className="mt-4 space-y-4 text-xs text-stone-400">
                <div>
                  <span className="block font-semibold text-white">Atelier &amp; Workshop Pusat</span>
                  <span>Jl. Raya Jepara-Kudus KM 12, Jawa Tengah</span>
                  <span className="block text-[11px] text-stone-400 mt-0.5">Sen–Sab: 10:00 – 19:00 • Min: Tutup</span>
                </div>
                <div>
                  <span className="block font-semibold text-white">Galeri Desain Jakarta Selatan</span>
                  <span>Kompon Kreatif Senopati, Jakarta</span>
                  <span className="block text-[11px] text-stone-400 mt-0.5">Sen–Sab: 10:00 – 19:00 • Min: 10:00 – 16:00</span>
                </div>
                <div className="pt-1 border-t border-stone-800">
                  <span className="font-mono text-[11px] text-stone-300 font-medium">inquiries@tatamebel.com</span>
                </div>
              </div>
            </div>
          </div>

          {/* Copyright bar */}
          <div className="mt-14 flex flex-col justify-between gap-4 border-t border-stone-800 pt-6 text-[11px] text-stone-400 sm:flex-row sm:items-center">
            <p>&copy; {new Date().getFullYear()} TATAMEBEL Atelier Inc. Hak cipta dilindungi undang-undang. Dirancang dengan penuh ketelitian di Indonesia.</p>
            <div className="flex items-center gap-6">
              <a className="hover:underline text-stone-300" href="#hero">Kebijakan Privasi</a>
              <a className="hover:underline text-stone-300" href="#hero">Syarat &amp; Ketentuan</a>
              <Link to="/login" className="hover:underline text-stone-300">Masuk Portal Staf &rarr;</Link>
            </div>
          </div>
        </div>
      </footer>
      {/* END: Footer */}
    </div>
  );
}
