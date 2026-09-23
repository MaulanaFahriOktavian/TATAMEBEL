import React, { useState } from 'react';
import { Navigate } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';

export default function LoginPage() {
  const { isAuthenticated, loading: authLoading, login } = useAuth();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [validationErrors, setValidationErrors] = useState({});
  const [errorMessage, setErrorMessage] = useState(null);

  // If already authenticated, redirect to admin home
  if (authLoading) {
    return (
      <div
        className="app-container"
        style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          minHeight: '100vh',
          backgroundColor: 'var(--color-bg, #f8fafc)',
        }}
      >
        <p style={{ color: 'var(--color-text-secondary, #475569)', fontSize: '0.9375rem' }}>
          Memeriksa sesi...
        </p>
      </div>
    );
  }

  if (isAuthenticated) {
    return <Navigate to="/" replace />;
  }

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMessage(null);

    // Client-side validation
    const errors = {};
    if (!email.trim()) {
      errors.email = 'Alamat email wajib diisi.';
    }
    if (!password) {
      errors.password = 'Kata sandi wajib diisi.';
    }

    if (Object.keys(errors).length > 0) {
      setValidationErrors(errors);
      return;
    }

    setValidationErrors({});
    setSubmitting(true);

    try {
      await login(email.trim(), password);
      // Navigation to '/' is handled inside login() in AuthContext
    } catch (err) {
      setSubmitting(false);

      if (err.response?.status === 422 && err.response?.data?.errors) {
        const backendErrors = err.response.data.errors;
        const mappedErrors = {};
        if (backendErrors.email) mappedErrors.email = backendErrors.email[0];
        if (backendErrors.password) mappedErrors.password = backendErrors.password[0];
        setValidationErrors(mappedErrors);
        setErrorMessage(err.response?.data?.message || 'Data yang dimasukkan tidak valid.');
      } else if (err.response?.status === 401 || err.response?.status === 404) {
        setErrorMessage(err.response?.data?.message || 'Email atau kata sandi tidak sesuai.');
      } else if (!err.response) {
        setErrorMessage('Tidak dapat terhubung ke server backend. Periksa koneksi jaringan.');
      } else {
        setErrorMessage('Terjadi kesalahan saat masuk. Silakan coba kembali.');
      }
    }
  };

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: 'var(--color-bg, #f8fafc)',
        padding: '1.5rem',
      }}
    >
      <div
        style={{
          width: '100%',
          maxWidth: '400px',
          backgroundColor: 'var(--color-surface, #ffffff)',
          border: '1px solid var(--color-border, #e2e8f0)',
          borderRadius: 'var(--radius-lg, 8px)',
          boxShadow: 'var(--shadow-sm, 0 1px 2px 0 rgb(0 0 0 / 0.05))',
          padding: '2rem 1.75rem',
        }}
      >
        {/* Header / Brand Identity */}
        <div style={{ marginBottom: '1.75rem', textAlign: 'center' }}>
          <h1
            style={{
              fontSize: '1.5rem',
              fontWeight: 700,
              letterSpacing: '-0.02em',
              color: 'var(--color-text-primary, #0f172a)',
              margin: 0,
            }}
          >
            TATAMEBEL
          </h1>
          <p
            style={{
              fontSize: '0.875rem',
              color: 'var(--color-text-secondary, #475569)',
              marginTop: '0.35rem',
            }}
          >
            Portal Masuk Staf Workshop
          </p>
        </div>

        {/* Top Error Alert */}
        {errorMessage && (
          <div
            role="alert"
            style={{
              padding: '0.75rem 1rem',
              marginBottom: '1.25rem',
              backgroundColor: 'var(--color-danger-bg, #fee2e2)',
              border: '1px solid #fca5a5',
              borderRadius: 'var(--radius-md, 6px)',
              color: 'var(--color-danger, #b91c1c)',
              fontSize: '0.875rem',
              lineHeight: 1.4,
            }}
          >
            {errorMessage}
          </div>
        )}

        {/* Authentication Form */}
        <form onSubmit={handleSubmit} noValidate>
          {/* Email Field */}
          <div style={{ marginBottom: '1.25rem' }}>
            <label
              htmlFor="email"
              style={{
                display: 'block',
                fontSize: '0.875rem',
                fontWeight: 600,
                color: 'var(--color-text-primary, #0f172a)',
                marginBottom: '0.35rem',
              }}
            >
              Email
            </label>
            <input
              id="email"
              type="email"
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              disabled={submitting}
              placeholder="nama@workshop.com"
              style={{
                width: '100%',
                padding: '0.625rem 0.75rem',
                fontSize: '0.9375rem',
                border: `1px solid ${validationErrors.email ? 'var(--color-danger, #b91c1c)' : 'var(--color-border, #e2e8f0)'}`,
                borderRadius: 'var(--radius-md, 6px)',
                backgroundColor: submitting ? 'var(--color-surface-hover, #f1f5f9)' : '#ffffff',
                color: 'var(--color-text-primary, #0f172a)',
                outline: 'none',
                boxSizing: 'border-box',
              }}
            />
            {validationErrors.email && (
              <span
                style={{
                  display: 'block',
                  color: 'var(--color-danger, #b91c1c)',
                  fontSize: '0.8125rem',
                  marginTop: '0.25rem',
                }}
              >
                {validationErrors.email}
              </span>
            )}
          </div>

          {/* Password Field */}
          <div style={{ marginBottom: '1.5rem' }}>
            <label
              htmlFor="password"
              style={{
                display: 'block',
                fontSize: '0.875rem',
                fontWeight: 600,
                color: 'var(--color-text-primary, #0f172a)',
                marginBottom: '0.35rem',
              }}
            >
              Password
            </label>
            <input
              id="password"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              disabled={submitting}
              placeholder="••••••••"
              style={{
                width: '100%',
                padding: '0.625rem 0.75rem',
                fontSize: '0.9375rem',
                border: `1px solid ${validationErrors.password ? 'var(--color-danger, #b91c1c)' : 'var(--color-border, #e2e8f0)'}`,
                borderRadius: 'var(--radius-md, 6px)',
                backgroundColor: submitting ? 'var(--color-surface-hover, #f1f5f9)' : '#ffffff',
                color: 'var(--color-text-primary, #0f172a)',
                outline: 'none',
                boxSizing: 'border-box',
              }}
            />
            {validationErrors.password && (
              <span
                style={{
                  display: 'block',
                  color: 'var(--color-danger, #b91c1c)',
                  fontSize: '0.8125rem',
                  marginTop: '0.25rem',
                }}
              >
                {validationErrors.password}
              </span>
            )}
          </div>

          {/* Submit Button */}
          <button
            type="submit"
            disabled={submitting}
            style={{
              width: '100%',
              padding: '0.75rem 1rem',
              fontSize: '0.9375rem',
              fontWeight: 600,
              color: '#ffffff',
              backgroundColor: submitting ? 'var(--color-brand-hover, #92400e)' : 'var(--color-brand, #b45309)',
              border: 'none',
              borderRadius: 'var(--radius-md, 6px)',
              cursor: submitting ? 'not-allowed' : 'pointer',
              transition: 'background-color 0.15s ease',
            }}
          >
            {submitting ? 'Memproses...' : 'Masuk'}
          </button>
        </form>
      </div>

      {/* Footer attribution */}
      <div
        style={{
          marginTop: '1.5rem',
          textAlign: 'center',
          fontSize: '0.8125rem',
          color: 'var(--color-text-muted, #64748b)',
        }}
      >
        TATAMEBEL &mdash; Manajemen Pesanan & Produksi Mebel
      </div>
    </div>
  );
}
