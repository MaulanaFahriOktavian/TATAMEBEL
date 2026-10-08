import axios from 'axios';

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});

// Helper for cart session token
export const getCartSessionToken = () => {
  let token = localStorage.getItem('tatamebel_cart_session');
  if (!token) {
    token = 'cart_' + Math.random().toString(36).substring(2, 15) + Date.now().toString(36);
    localStorage.setItem('tatamebel_cart_session', token);
  }
  return token;
};

// Products API
export const fetchProducts = async (params = {}) => {
  const response = await api.get('/products', { params });
  return response.data;
};

export const fetchProductDetail = async (idOrSlug) => {
  const response = await api.get(`/products/${idOrSlug}`);
  return response.data;
};

// Categories API
export const fetchCategories = async () => {
  const response = await api.get('/categories');
  return response.data;
};

// Cart API
export const fetchCart = async () => {
  const token = getCartSessionToken();
  const response = await api.get('/cart', {
    headers: { 'X-Cart-Session': token },
  });
  return response.data;
};

export const addToCart = async (productId, quantity = 1, variantId = null) => {
  const token = getCartSessionToken();
  const response = await api.post('/cart', {
    product_id: productId,
    quantity,
    variant_id: variantId,
    session_token: token,
  }, {
    headers: { 'X-Cart-Session': token },
  });
  return response.data;
};

export const updateCartItem = async (itemId, quantity) => {
  const response = await api.put(`/cart/${itemId}`, { quantity });
  return response.data;
};

export const removeCartItem = async (itemId) => {
  const response = await api.delete(`/cart/${itemId}`);
  return response.data;
};

export const clearCart = async () => {
  const token = getCartSessionToken();
  const response = await api.delete('/cart', {
    headers: { 'X-Cart-Session': token },
  });
  return response.data;
};

// Custom Furniture Inquiry API
export const submitInquiry = async (formData) => {
  const response = await api.post('/inquiries', formData);
  return response.data;
};

// Order Tracking API
export const trackOrder = async (trackingToken) => {
  const clean = encodeURIComponent(trackingToken.trim());
  const response = await api.get(`/public/orders/${clean}`);
  return response.data;
};

export default api;
