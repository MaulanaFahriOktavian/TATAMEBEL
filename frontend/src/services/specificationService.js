import api from './api';

/**
 * List all specification version history for an order item.
 *
 * @param {string|number} orderId
 * @param {string|number} itemId
 * @returns {Promise<any>}
 */
export async function listSpecificationsByItem(orderId, itemId) {
  const response = await api.get(`/orders/${orderId}/items/${itemId}/specifications`);
  return response.data;
}

/**
 * Get current operational specification for an order item.
 *
 * @param {string|number} orderId
 * @param {string|number} itemId
 * @returns {Promise<any>}
 */
export async function getCurrentSpecification(orderId, itemId) {
  const response = await api.get(`/orders/${orderId}/items/${itemId}/specifications/current`);
  return response.data;
}

/**
 * Create initial DRAFT specification for an order item.
 *
 * @param {string|number} orderId
 * @param {string|number} itemId
 * @param {object} data
 * @returns {Promise<any>}
 */
export async function createSpecification(orderId, itemId, data) {
  const response = await api.post(`/orders/${orderId}/items/${itemId}/specifications`, data);
  return response.data;
}

/**
 * Update specification in DRAFT status.
 *
 * @param {string|number} id
 * @param {object} data
 * @returns {Promise<any>}
 */
export async function updateSpecification(id, data) {
  const response = await api.patch(`/specifications/${id}`, data);
  return response.data;
}

/**
 * Lock a specification into immutable operational blueprint.
 *
 * @param {string|number} id
 * @returns {Promise<any>}
 */
export async function lockSpecification(id) {
  const response = await api.post(`/specifications/${id}/lock`);
  return response.data;
}

export default {
  listSpecificationsByItem,
  getCurrentSpecification,
  createSpecification,
  updateSpecification,
  lockSpecification,
};
