import api from '../../../services/api';

/**
 * Fetch public order tracking details by public token.
 *
 * @param {string} publicToken
 * @returns {Promise<object>}
 */
export async function getOrderTracking(publicToken) {
  const response = await api.get(`/public/orders/${publicToken}`);
  return response.data;
}
