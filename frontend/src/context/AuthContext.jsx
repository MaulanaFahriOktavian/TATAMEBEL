import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import authService from '../services/authService';
import { AuthContext } from './auth-context';

export function AuthProvider({ children }) {
  const navigate = useNavigate();

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

  // Validate existing session on mount if token is stored
  useEffect(() => {
    const token = localStorage.getItem('token');
    if (!token) {
      return;
    }

    let isMounted = true;

    authService.getMe()
      .then((res) => {
        if (!isMounted) return;
        const userData = res.data?.user || res.data;
        if (res.success && userData) {
          setUser(userData);
          localStorage.setItem('user', JSON.stringify(userData));
          setError(null);
        } else {
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          setUser(null);
        }
      })
      .catch(() => {
        if (!isMounted) return;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        setUser(null);
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

  const login = useCallback(async (email, password) => {
    setError(null);
    try {
      const res = await authService.login(email, password);
      const token = res.data?.token;

      if (!token) {
        throw new Error('Format token autentikasi tidak valid.');
      }

      localStorage.setItem('token', token);

      // Immediately fetch authoritative user profile and workshop context
      const meRes = await authService.getMe();
      const userData = meRes.data?.user || meRes.data;

      setUser(userData);
      localStorage.setItem('user', JSON.stringify(userData));
      setLoading(false);

      navigate('/', { replace: true });
      return userData;
    } catch (err) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      setUser(null);
      setLoading(false);
      throw err;
    }
  }, [navigate]);

  const logout = useCallback(async () => {
    const token = localStorage.getItem('token');
    try {
      if (token) {
        await authService.logout();
      }
    } catch {
      // Regardless of backend response or network failure, clean local session
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      setUser(null);
      setError(null);
      setLoading(false);
      navigate('/login', { replace: true });
    }
  }, [navigate]);

  const refreshUser = useCallback(async () => {
    try {
      const meRes = await authService.getMe();
      const userData = meRes.data?.user || meRes.data;
      if (meRes.success && userData) {
        setUser(userData);
        localStorage.setItem('user', JSON.stringify(userData));
        return userData;
      }
      return null;
    } catch (err) {
      setUser(null);
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      throw err;
    }
  }, []);

  const canShareWhatsApp = useMemo(() => {
    return Boolean(user && (user.role === 'OWNER' || user.role === 'ADMIN'));
  }, [user]);

  const value = useMemo(() => ({
    user,
    role: user?.role || null,
    loading,
    error,
    isAuthenticated: Boolean(user),
    canShareWhatsApp,
    login,
    logout,
    refreshUser,
  }), [user, loading, error, canShareWhatsApp, login, logout, refreshUser]);

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export default AuthProvider;
