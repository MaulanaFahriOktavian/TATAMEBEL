import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import FoundationPage from '../pages/FoundationPage';
import CustomerPortalPage from '../pages/CustomerPortalPage';

export default function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<FoundationPage />} />
        <Route path="/track/:publicToken" element={<CustomerPortalPage />} />
      </Routes>
    </BrowserRouter>
  );
}
