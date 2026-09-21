import React, { useState } from 'react';

/**
 * PhotoGallery component for displaying customer-visible workshop production photos.
 */
export default function PhotoGallery({ photos = [] }) {
  const [activePhoto, setActivePhoto] = useState(null);

  return (
    <div className="portal-card">
      <div className="portal-card-header">
        <span className="portal-card-title">Foto Dokumentasi</span>
        <span style={{ fontSize: '0.75rem', color: '#64748b' }}>
          {photos.length} Foto
        </span>
      </div>

      {photos.length === 0 ? (
        <p style={{ fontSize: '0.8125rem', color: '#64748b', textAlign: 'center', margin: '0.75rem 0' }}>
          Dokumentasi foto pengerjaan akan diperbarui oleh tim workshop seiring berjalannya produksi.
        </p>
      ) : (
        <div className="photo-grid">
          {photos.map((photo, idx) => (
            <div
              key={idx}
              className="photo-thumb-container"
              onClick={() => setActivePhoto(photo)}
            >
              <img
                src={photo.url}
                alt={photo.caption || 'Foto Produksi Mebel'}
                className="photo-thumb"
                loading="lazy"
              />
              {photo.caption && (
                <div className="photo-caption-bar">
                  {photo.caption}
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Lightbox Modal */}
      {activePhoto && (
        <div className="lightbox-backdrop" onClick={() => setActivePhoto(null)}>
          <div className="lightbox-content" onClick={(e) => e.stopPropagation()}>
            <button
              className="lightbox-close-btn"
              onClick={() => setActivePhoto(null)}
              aria-label="Tutup foto"
            >
              ×
            </button>
            <img
              src={activePhoto.url}
              alt={activePhoto.caption || 'Foto Produksi'}
              className="lightbox-img"
            />
            {activePhoto.caption && (
              <div className="lightbox-caption-box">
                {activePhoto.caption}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
