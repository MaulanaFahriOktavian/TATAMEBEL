import api from './api';

/**
 * Fetch paginated customers listing.
 *
 * @param {number} [page=1]
 * @returns {Promise<any>}
 */
export async function listCustomers(page = 1) {
  const response = await api.get(`/customers?page=${page}`);
  return response.data;
}

/**
 * Create a new customer in the current workshop.
 *
 * @param {object} data
 * @returns {Promise<any>}
 */
export async function createCustomer(data) {
  const response = await api.post('/customers', data);
  return response.data;
}

/**
 * Get customer details by ID.
 *
 * @param {string|number} id
 * @returns {Promise<any>}
 */
export async function getCustomer(id) {
  const response = await api.get(`/customers/${id}`);
  return response.data;
}

export default {
  listCustomers,
  createCustomer,
  getCustomer,
};
