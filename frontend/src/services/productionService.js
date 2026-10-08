import api from './api';

/**
 * Get complete production tracking overview for an order.
 *
 * @param {string|number} orderId
 * @returns {Promise<any>}
 */
export async function getProductionOverview(orderId) {
  const response = await api.get(`/orders/${orderId}/production`);
  return response.data;
}

/**
 * Update operational status of a production stage (PENDING, IN_PROGRESS, COMPLETED).
 *
 * @param {string|number} stageId
 * @param {string} status
 * @returns {Promise<any>}
 */
export async function updateStageStatus(stageId, status) {
  const response = await api.patch(`/production-stages/${stageId}`, { status });
  return response.data;
}

/**
 * Upload photo evidence attached to an order (CUSTOMER or INTERNAL visibility).
 *
 * @param {string|number} orderId
 * @param {FormData} formData
 * @returns {Promise<any>}
 */
export async function uploadOrderMedia(orderId, formData) {
  const response = await api.post(`/orders/${orderId}/media`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
  return response.data;
}

/**
 * List all media evidence for an order.
 *
 * @param {string|number} orderId
 * @returns {Promise<any>}
 */
export async function listOrderMedia(orderId) {
  const response = await api.get(`/orders/${orderId}/media`);
  return response.data;
}

export default {
  getProductionOverview,
  updateStageStatus,
  uploadOrderMedia,
  listOrderMedia,
};
