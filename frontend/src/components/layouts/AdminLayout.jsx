import React, { useState } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';

export default function AdminLayout({ children }) {
  const { user, role, logout } = useAuth();
  const navigate = useNavigate();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  const navItemClass = ({ isActive }) =>
    isActive
      ? 'admin-nav-item active'
      : 'admin-nav-item';

  return (
    <div className="admin-layout">
      {/* Top Application Bar */}
      <header className="admin-header">
        <div className="admin-header-inner">
          {/* Brand & Workshop Identity */}
          <div className="admin-brand-group">
            <NavLink to="/orders" className="admin-brand-title">
              TATAMEBEL
            </NavLink>
            <span className="admin-workshop-tag">
              {user?.workshop?.name || 'Workshop Kayu'}
            </span>
          </div>

          {/* Desktop Navigation */}
          <nav className="admin-nav-desktop">
            <NavLink to="/orders" end className={navItemClass}>
              Pesanan
            </NavLink>
            <NavLink to="/customers" className={navItemClass}>
              Pelanggan
            </NavLink>
            <NavLink to="/foundation" className={navItemClass}>
              Diagnostik
            </NavLink>
          </nav>

          {/* User Session Info & Logout */}
          <div className="admin-user-group">
            <div className="admin-user-info">
              <span className="admin-user-name">{user?.name || 'Pengguna'}</span>
              <span className="admin-role-badge">{role || 'STAFF'}</span>
            </div>
            <button
              onClick={handleLogout}
              className="btn-logout"
              title="Keluar dari sesi"
            >
              Keluar
            </button>
            {/* Mobile Menu Toggle Button */}
            <button
              className="btn-mobile-menu"
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              aria-label="Toggle menu"
            >
              {mobileMenuOpen ? '✕' : '☰'}
            </button>
          </div>
        </div>

        {/* Mobile Navigation Drawer */}
        {mobileMenuOpen && (
          <nav className="admin-nav-mobile">
            <NavLink
              to="/orders"
              end
              className={navItemClass}
              onClick={() => setMobileMenuOpen(false)}
            >
              Pesanan
            </NavLink>
            <NavLink
              to="/customers"
              className={navItemClass}
              onClick={() => setMobileMenuOpen(false)}
            >
              Pelanggan
            </NavLink>
            <NavLink
              to="/foundation"
              className={navItemClass}
              onClick={() => setMobileMenuOpen(false)}
            >
              Diagnostik
            </NavLink>
          </nav>
        )}
      </header>

      {/* Main Content Area */}
      <main className="admin-main">
        {children || <Outlet />}
      </main>

      {/* Editorial Footer */}
      <footer className="admin-footer">
        <div className="admin-footer-inner">
          <span>TATAMEBEL &mdash; Sistem Manajemen Pesanan & Produksi Mebel</span>
          <span>Sesi: {user?.email} ({role})</span>
        </div>
      </footer>
    </div>
  );
}
