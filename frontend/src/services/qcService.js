import api from './api';

/**
 * List all QC inspections for an order.
 *
 * @param {string|number} orderId
 * @returns {Promise<any>}
 */
export async function listQcInspections(orderId) {
  const response = await api.get(`/orders/${orderId}/qc-inspections`);
  return response.data;
}

/**
 * Create a new QC inspection with default checklist template.
 *
 * @param {string|number} orderId
 * @param {string} [notes]
 * @returns {Promise<any>}
 */
export async function createQcInspection(orderId, notes = '') {
  const response = await api.post(`/orders/${orderId}/qc-inspections`, { notes });
  return response.data;
}

/**
 * Retrieve single QC inspection detail (includes items and defects).
 *
 * @param {string|number} id
 * @returns {Promise<any>}
 */
export async function getQcInspection(id) {
  const response = await api.get(`/qc-inspections/${id}`);
  return response.data;
}

/**
 * Evaluate batch of checklist items for a PENDING inspection.
 *
 * @param {string|number} id
 * @param {Array<{ id: number, status: string, notes?: string }>} items
 * @returns {Promise<any>}
 */
export async function evaluateQcItems(id, items) {
  const response = await api.post(`/qc-inspections/${id}/items`, { items });
  return response.data;
}

/**
 * Finalize QC inspection into PASSED, REWORK, or FAILED.
 *
 * @param {string|number} id
 * @param {string} status
 * @param {string} [notes]
 * @returns {Promise<any>}
 */
export async function finalizeQcInspection(id, status, notes = '') {
  const response = await api.post(`/qc-inspections/${id}/finalize`, { status, notes });
  return response.data;
}

/**
 * Log a new defect for a PENDING inspection.
 *
 * @param {string|number} inspectionId
 * @param {{ description: string, severity: string, qc_item_id?: number|null }} data
 * @returns {Promise<any>}
 */
export async function storeQcDefect(inspectionId, data) {
  const response = await api.post(`/qc-inspections/${inspectionId}/defects`, data);
  return response.data;
}

/**
 * Update defect lifecycle status (IN_REWORK, RESOLVED, or ACCEPTED).
 *
 * @param {string|number} defectId
 * @param {string} status
 * @param {string} [resolution]
 * @returns {Promise<any>}
 */
export async function updateQcDefectStatus(defectId, status, resolution = '') {
  const response = await api.patch(`/qc-defects/${defectId}/status`, { status, resolution });
  return response.data;
}

export default {
  listQcInspections,
  createQcInspection,
  getQcInspection,
  evaluateQcItems,
  finalizeQcInspection,
  storeQcDefect,
  updateQcDefectStatus,
};
