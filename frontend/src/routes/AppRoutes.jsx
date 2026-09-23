import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from '../context/AuthContext';
import ProtectedRoute from './ProtectedRoute';
import LoginPage from '../pages/auth/LoginPage';
import FoundationPage from '../pages/FoundationPage';
import CustomerPortalPage from '../pages/CustomerPortalPage';
import OrderDetailPage from '../pages/orders/OrderDetailPage';

export default function AppRoutes() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          {/* Public Routes */}
          <Route path="/login" element={<LoginPage />} />
          <Route path="/track/:publicToken" element={<CustomerPortalPage />} />

          {/* Protected Workshop Admin Routes */}
          <Route
            path="/"
            element={
              <ProtectedRoute>
                <FoundationPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/orders/:id"
            element={
              <ProtectedRoute>
                <OrderDetailPage />
              </ProtectedRoute>
            }
          />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
