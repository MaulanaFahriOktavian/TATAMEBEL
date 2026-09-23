import { useContext } from 'react';
import { AuthContext } from '../context/auth-context';

/**
 * Custom hook to interact with the global reactive authentication session.
 * Exposes: user, role, loading, error, isAuthenticated, canShareWhatsApp, login, logout, refreshUser.
 */
export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

export default useAuth;
