import React, { useEffect, useState } from 'react';
import { getHealthStatus } from '../services/api';

export default function FoundationPage() {
  const [healthData, setHealthData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchHealth = () => {
    setLoading(true);
    setError(null);
    getHealthStatus()
      .then((data) => {
        setHealthData(data);
        setLoading(false);
      })
      .catch((err) => {
        setError(err.message || 'Gagal menghubungi backend API.');
        setHealthData(null);
        setLoading(false);
      });
  };

  useEffect(() => {
    let isMounted = true;
    getHealthStatus()
      .then((data) => {
        if (isMounted) {
          setHealthData(data);
          setLoading(false);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.message || 'Gagal menghubungi backend API.');
          setLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const getStatusBadge = () => {
    if (loading) {
      return <span className="badge badge-degraded">Memeriksa...</span>;
    }
    if (error) {
      return <span className="badge badge-offline">Offline</span>;
    }
    if (healthData?.data?.status === 'healthy') {
      return <span className="badge badge-healthy">Healthy</span>;
    }
    return <span className="badge badge-degraded">Degraded</span>;
  };

  return (
    <div className="app-container">
      <header className="header">
        <div className="header-content">
          <div>
            <div style={{ display: 'flex', alignItems: 'center' }}>
              <span className="brand-title">TATAMEBEL</span>
              <span className="brand-badge">PHASE 0 FOUNDATION</span>
            </div>
            <p className="brand-subtitle">
              Sistem Manajemen Pesanan & Produksi Mebel — Sumber Kebenaran Operasional Workshop
            </p>
          </div>
          <div>
            {getStatusBadge()}
          </div>
        </div>
      </header>

      <main className="main-content">
        <section className="card">
          <h2 className="card-title">Verifikasi Fondasi Sistem (Phase 0)</h2>
          <p className="card-description">
            Halaman ini memvalidasi kesiapan infrastruktur backend Laravel 13, frontend React + Vite, basis data MySQL, dan integrasi API REST sebelum melanjutkan ke Phase 1 (Database Core).
          </p>

          <table className="data-table">
            <thead>
              <tr>
                <th>Komponen</th>
                <th>Target Nilai / Spesifikasi</th>
                <th>Status Terdeteksi</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Backend Framework</strong></td>
                <td>Laravel 13.x REST API</td>
                <td>
                  <span className="code-pill">Laravel 13.32.0</span>
                </td>
              </tr>
              <tr>
                <td><strong>API Contract Base</strong></td>
                <td>/api/v1</td>
                <td>
                  <span className="code-pill">http://127.0.0.1:8000/api/v1</span>
                </td>
              </tr>
              <tr>
                <td><strong>Probe Endpoint</strong></td>
                <td>GET /api/v1/health</td>
                <td>
                  <span className="code-pill">/api/v1/health</span>
                </td>
              </tr>
              <tr>
                <td><strong>API Connectivity</strong></td>
                <td>200 OK & Standard Envelope</td>
                <td>
                  {loading && <em>Menghubungi API...</em>}
                  {!loading && error && (
                    <span className="badge badge-offline">Gagal: {error}</span>
                  )}
                  {!loading && healthData && (
                    <span className="badge badge-healthy">
                      {healthData.message || 'Healthy'} ({healthData.clientLatencyMs}ms)
                    </span>
                  )}
                </td>
              </tr>
              <tr>
                <td><strong>MySQL Database Probe</strong></td>
                <td>tatamebel (Port 3306)</td>
                <td>
                  {loading && <em>Memeriksa...</em>}
                  {!loading && error && (
                    <span className="badge badge-offline">Tidak terhubung</span>
                  )}
                  {!loading && healthData && (
                    <span className={`badge ${healthData.data?.database === 'connected' ? 'badge-healthy' : 'badge-offline'}`}>
                      {healthData.data?.database || 'unknown'}
                    </span>
                  )}
                </td>
              </tr>
              <tr>
                <td><strong>App Version</strong></td>
                <td>Config: app.version</td>
                <td>
                  <span className="code-pill">{healthData?.data?.version || '1.0.0'}</span>
                </td>
              </tr>
              <tr>
                <td><strong>App Environment</strong></td>
                <td>local</td>
                <td>
                  <span className="code-pill">{healthData?.data?.environment || 'local'}</span>
                </td>
              </tr>
              <tr>
                <td><strong>Server Timestamp</strong></td>
                <td>ISO-8601</td>
                <td>
                  <span className="code-pill">{healthData?.data?.timestamp || '-'}</span>
                </td>
              </tr>
            </tbody>
          </table>

          <div style={{ display: 'flex', gap: '0.75rem', marginTop: '1.5rem', alignItems: 'center' }}>
            <button
              className="btn btn-primary"
              onClick={fetchHealth}
              disabled={loading}
            >
              {loading ? 'Memeriksa...' : 'Uji Ulang Konektivitas API'}
            </button>
            <span style={{ fontSize: '0.8125rem', color: 'var(--color-text-muted)' }}>
              Backend is authoritative source of truth. Frontend visibility is purely diagnostic.
            </span>
          </div>
        </section>

        <section className="card">
          <h2 className="card-title">Prinsip Pengembangan & Batasan Phase 0</h2>
          <ul style={{ paddingLeft: '1.25rem', fontSize: '0.875rem', color: 'var(--color-text-secondary)', lineHeight: '1.8' }}>
            <li><strong>Anti-Slop:</strong> Tidak ada modul bisnis dummy (Orders, Customers, Production) atau fake data sebelum Phase 1 selesai dan di-approve.</li>
            <li><strong>Multi-Tenancy Ready:</strong> Arsitektur disiapkan untuk isolasi data berbasis <span className="code-pill">workshop_id</span> di level database dan policy.</li>
            <li><strong>WhatsApp Integration:</strong> TATAMEBEL bertindak sebagai operational backend & progress link generator, bukan pengganti WhatsApp.</li>
            <li><strong>Postman:</strong> Lokasi file resmi di <span className="code-pill">backend/postman/</span> dengan base URL <span className="code-pill">http://127.0.0.1:8000/api/v1</span>.</li>
          </ul>
        </section>
      </main>

      <footer className="footer">
        TATAMEBEL &copy; {new Date().getFullYear()} &mdash; "Tetap jualan lewat WhatsApp. Kelola pesanan dan produksinya lewat TATAMEBEL."
      </footer>
    </div>
  );
}
