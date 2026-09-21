import React, { useEffect, useState, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import { getOrderTracking } from '../features/customer-portal/services/customerPortalService';
import PortalHeader from '../features/customer-portal/components/PortalHeader';
import OrderStatusBanner from '../features/customer-portal/components/OrderStatusBanner';
import ProgressTracker from '../features/customer-portal/components/ProgressTracker';
import ItemSpecificationCard from '../features/customer-portal/components/ItemSpecificationCard';
import PhotoGallery from '../features/customer-portal/components/PhotoGallery';
import QcStatusCard from '../features/customer-portal/components/QcStatusCard';
import ShippingTrackerCard from '../features/customer-portal/components/ShippingTrackerCard';
import WorkshopContactCard from '../features/customer-portal/components/WorkshopContactCard';
import '../features/customer-portal/customerPortal.css';

export default function CustomerPortalPage() {
  const { publicToken } = useParams();

  const [orderData, setOrderData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [errorStatus, setErrorStatus] = useState(null); // 404 | 'NETWORK_ERROR' | null

  const fetchTracking = useCallback((token) => {
    if (!token) {
      setErrorStatus(404);
      setLoading(false);
      return;
    }

    setLoading(true);
    setErrorStatus(null);

    getOrderTracking(token)
      .then((response) => {
        if (response?.success && response?.data) {
          setOrderData(response.data);
          setErrorStatus(null);
        } else {
          setErrorStatus(404);
        }
      })
      .catch((err) => {
        setErrorStatus(err.response?.status === 404 ? 404 : 'NETWORK_ERROR');
      })
      .finally(() => {
        setLoading(false);
      });
  }, []);

  useEffect(() => {
    let ignore = false;

    if (!publicToken) {
      return;
    }

    getOrderTracking(publicToken)
      .then((response) => {
        if (!ignore) {
          if (response?.success && response?.data) {
            setOrderData(response.data);
            setErrorStatus(null);
          } else {
            setErrorStatus(404);
          }
          setLoading(false);
        }
      })
      .catch((err) => {
        if (!ignore) {
          setErrorStatus(err.response?.status === 404 ? 404 : 'NETWORK_ERROR');
          setLoading(false);
        }
      });

    return () => {
      ignore = true;
    };
  }, [publicToken]);

  const handleRetry = () => {
    fetchTracking(publicToken);
  };

  // 1. Loading State
  if (loading) {
    return (
      <div className="portal-wrapper">
        <header className="portal-header">
          <div className="portal-header-content">
            <div className="skeleton-line" style={{ width: '45%', height: '22px' }} />
            <div className="skeleton-line" style={{ width: '60%', height: '14px' }} />
          </div>
        </header>

        <div className="portal-container">
          <div className="portal-card">
            <div className="skeleton-line" style={{ width: '30%', height: '16px' }} />
            <div className="skeleton-line" style={{ width: '80%', height: '26px' }} />
            <div className="skeleton-line" style={{ width: '95%', height: '14px' }} />
          </div>

          <div className="portal-card">
            <div className="skeleton-line" style={{ width: '40%', height: '18px' }} />
            <div className="skeleton-line" style={{ width: '100%', height: '10px', marginTop: '1rem' }} />
            <div className="skeleton-line" style={{ width: '70%', height: '14px', marginTop: '1rem' }} />
          </div>
        </div>
      </div>
    );
  }

  // 3. 404 / Invalid Token State
  if (errorStatus === 404) {
    return (
      <div className="portal-wrapper">
        <div className="state-container">
          <div className="state-icon">🔍</div>
          <h2 className="state-title">Pesanan Tidak Ditemukan</h2>
          <p className="state-desc">
            Tautan pelacakan yang Anda buka tidak valid atau pesanan tidak tersedia.
            Pastikan tautan yang Anda buka dari WhatsApp sudah sesuai dan lengkap.
          </p>
        </div>
      </div>
    );
  }

  // 4. Server / Network Error State
  if (errorStatus === 'NETWORK_ERROR' || !orderData) {
    return (
      <div className="portal-wrapper">
        <div className="state-container">
          <div className="state-icon">⚠️</div>
          <h2 className="state-title">Gagal Memuat Informasi</h2>
          <p className="state-desc">
            Terjadi kendala saat menghubungkan ke server workshop. Silakan periksa jaringan internet Anda dan coba lagi.
          </p>
          <button className="btn-retry" onClick={handleRetry}>
            Coba Lagi
          </button>
        </div>
      </div>
    );
  }

  const { order, items, production, photos, quality_control, shipping } = orderData;

  // 2. Success State with clean modular composition
  return (
    <div className="portal-wrapper">
      {/* Workshop & Order Identitas */}
      <PortalHeader order={order} />

      <main className="portal-container">
        {/* Status Pesanan Banner */}
        <OrderStatusBanner order={order} />

        {/* Progres Produksi & Stepper Tahapan */}
        <ProgressTracker production={production} />

        {/* Spesifikasi Teknis Mebel (LOCKED specs) */}
        <ItemSpecificationCard items={items} />

        {/* Foto Dokumentasi Pengerjaan Workshop (CUSTOMER media) */}
        <PhotoGallery photos={photos} />

        {/* Status Quality Control */}
        <QcStatusCard qc={quality_control} />

        {/* Status Pengiriman & Resi (jika ada) */}
        <ShippingTrackerCard shipping={shipping} />

        {/* Kontak Workshop via WhatsApp */}
        <WorkshopContactCard
          workshop={order?.workshop}
          orderNumber={order?.order_number}
        />
      </main>
    </div>
  );
}
