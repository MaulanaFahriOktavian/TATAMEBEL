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
 * Fetch paginated orders listing for the current workshop.
 *
 * @param {number} [page=1]
 * @returns {Promise<any>}
 */
export async function listOrders(page = 1) {
  const response = await api.get(`/orders?page=${page}`);
  return response.data;
}

/**
 * Create a new order with items.
 *
 * @param {object} data
 * @returns {Promise<any>}
 */
export async function createOrder(data) {
  const response = await api.post('/orders', data);
  return response.data;
}

/**
 * Update order status following the authoritative backend state machine.
 *
 * @param {string|number} orderId
 * @param {string} status
 * @returns {Promise<any>}
 */
export async function changeOrderStatus(orderId, status) {
  const response = await api.patch(`/orders/${orderId}/status`, { status });
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

export default {
  getOrderDetail,
  listOrders,
  createOrder,
  changeOrderStatus,
  getWhatsAppShareData,
};
