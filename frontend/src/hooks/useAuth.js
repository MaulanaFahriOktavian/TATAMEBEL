import { useState, useEffect } from 'react';
import api from '../services/api';

/**
 * Custom hook to interact with the backend authentication session.
 * Connects directly with /auth/me and Sanctum bearer token.
 */
export function useAuth() {
  const [user, setUser] = useState(() => {
    try {
      const cached = localStorage.getItem('user');
      return cached ? JSON.parse(cached) : null;
    } catch {
      return null;
    }
  });
  const [loading, setLoading] = useState(() => {
    return Boolean(localStorage.getItem('token'));
  });
  const [error, setError] = useState(null);

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (!token) {
      return;
    }

    let isMounted = true;

    api.get('/auth/me')
      .then((res) => {
        if (isMounted && res.data?.success) {
          const userData = res.data.data;
          setUser(userData);
          localStorage.setItem('user', JSON.stringify(userData));
        }
      })
      .catch((err) => {
        if (!isMounted) return;
        setError(err.response?.data?.message || 'Sesi telah kedaluwarsa.');
        // If 401 unauthenticated, clear local session
        if (err.response?.status === 401) {
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          setUser(null);
        }
      })
      .finally(() => {
        if (isMounted) {
          setLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const canShareWhatsApp = Boolean(
    user && (user.role === 'OWNER' || user.role === 'ADMIN')
  );

  return {
    user,
    role: user?.role || null,
    loading,
    error,
    isAuthenticated: Boolean(user),
    canShareWhatsApp,
  };
}

export default useAuth;
