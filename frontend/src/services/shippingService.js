import api from './api';

/**
 * Retrieve shipping information for the specified order.
 *
 * @param {string|number} orderId
 * @returns {Promise<any>}
 */
export async function getShipping(orderId) {
  const response = await api.get(`/orders/${orderId}/shipping`);
  return response.data;
}

/**
 * Create shipping record for the specified order.
 *
 * @param {string|number} orderId
 * @param {object} data
 * @returns {Promise<any>}
 */
export async function createShipping(orderId, data) {
  const response = await api.post(`/orders/${orderId}/shipping`, data);
  return response.data;
}

/**
 * Update shipping details or status for the specified order.
 *
 * @param {string|number} orderId
 * @param {object} data
 * @returns {Promise<any>}
 */
export async function updateShipping(orderId, data) {
  const response = await api.patch(`/orders/${orderId}/shipping`, data);
  return response.data;
}

export default {
  getShipping,
  createShipping,
  updateShipping,
};
