import React from 'react';

/**
 * OrderStatusBanner component displaying high-level order status and friendly context.
 */
export default function OrderStatusBanner({ order }) {
  const isCompleted = order?.status === 'COMPLETED';

  const getStatusDescription = (status) => {
    switch (status) {
      case 'DRAFT':
      case 'QUOTATION':
        return 'Rincian pesanan sedang dipersiapkan dan menunggu konfirmasi akhir.';
      case 'CONFIRMED':
      case 'WAITING_DP':
        return 'Pesanan telah dicatat dan menunggu jadwal pengerjaan bengkel.';
      case 'READY_FOR_PRODUCTION':
        return 'Spesifikasi teknis telah dikunci dan siap masuk antrean produksi pengrajin.';
      case 'IN_PRODUCTION':
        return 'Pengrajin sedang aktif mengerjakan tahapan produksi pesanan mebel Anda.';
      case 'QC':
        return 'Mebel sedang melalui tahapan pengecekan kualitas komprehensif sebelum dikemas.';
      case 'PACKING':
        return 'Mebel sedang dikemas dengan aman menggunakan perlindungan khusus pengiriman.';
      case 'READY_TO_SHIP':
        return 'Pesanan telah selesai dikemas rapi dan siap diserahkan kepada kurir ekspedisi.';
      case 'SHIPPED':
        return 'Pesanan sedang dalam perjalanan menuju alamat tujuan Anda.';
      case 'COMPLETED':
        return 'Pesanan Anda telah selesai dan diterima dengan baik. Terima kasih atas kepercayaan Anda!';
      case 'CANCELLED':
        return 'Pesanan ini telah dibatalkan.';
      default:
        return 'Informasi status pengerjaan pesanan Anda.';
    }
  };

  return (
    <div className={`status-banner ${isCompleted ? 'completed' : ''}`}>
      <span className="status-banner-badge">{order?.status_label || order?.status}</span>
      <h2 className="status-banner-title">{order?.title || 'Pesanan Mebel'}</h2>
      <p className="status-banner-desc">{getStatusDescription(order?.status)}</p>
    </div>
  );
}
