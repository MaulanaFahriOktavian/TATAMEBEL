import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from '../context/AuthContext';
import ProtectedRoute from './ProtectedRoute';
import AdminLayout from '../components/layouts/AdminLayout';
import LoginPage from '../pages/auth/LoginPage';
import OrderListPage from '../pages/dashboard/OrderListPage';
import CreateOrderPage from '../pages/orders/CreateOrderPage';
import OrderDetailPage from '../pages/orders/OrderDetailPage';
import CustomerListPage from '../pages/customers/CustomerListPage';
import FoundationPage from '../pages/FoundationPage';
import CustomerPortalPage from '../pages/CustomerPortalPage';
import LandingPage from '../pages/landing/LandingPage';

export default function AppRoutes() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          {/* Public Routes */}
          <Route path="/" element={<LandingPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/track/:publicToken" element={<CustomerPortalPage />} />

          {/* Protected Workshop Admin Workspace Routes */}
          <Route
            element={
              <ProtectedRoute>
                <AdminLayout />
              </ProtectedRoute>
            }
          >
            <Route path="/orders" element={<OrderListPage />} />
            <Route path="/orders/new" element={<CreateOrderPage />} />
            <Route path="/orders/:id" element={<OrderDetailPage />} />
            <Route path="/customers" element={<CustomerListPage />} />
            <Route path="/foundation" element={<FoundationPage />} />
          </Route>
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
