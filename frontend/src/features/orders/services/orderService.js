import api from '../../../services/api';

/**
 * Fetch authenticated order detail.
 *
 * @param {string|number} orderId
 * @returns {Promise<any>}
 */
export async function getOrderDetail(orderId) {
  const response = await api.get(`/orders/${orderId}`);
  return response.data;
}

/**
 * Request WhatsApp share message and wa.me URL for the order.
 * Only available for OWNER and ADMIN roles.
 *
 * @param {string|number} orderId
 * @returns {Promise<any>}
 */
export async function getWhatsAppShareData(orderId) {
  const response = await api.get(`/orders/${orderId}/whatsapp`);
  return response.data;
}
