import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import FoundationPage from '../pages/FoundationPage';

export default function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<FoundationPage />} />
      </Routes>
    </BrowserRouter>
  );
}
