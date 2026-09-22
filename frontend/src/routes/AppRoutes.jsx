import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import FoundationPage from '../pages/FoundationPage';
import CustomerPortalPage from '../pages/CustomerPortalPage';
import OrderDetailPage from '../pages/orders/OrderDetailPage';

export default function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<FoundationPage />} />
        <Route path="/orders/:id" element={<OrderDetailPage />} />
        <Route path="/track/:publicToken" element={<CustomerPortalPage />} />
      </Routes>
    </BrowserRouter>
  );
}
