const BASE_URL = import.meta.env.VITE_API_URL || '/api';

function buildQueryString(params) {
  if (!params) return '';
  const filtered = Object.entries(params).filter(
    ([, v]) => v !== undefined && v !== null && v !== ''
  );
  if (!filtered.length) return '';
  return '?' + new URLSearchParams(filtered).toString();
}

async function request(method, url, data) {
  const config = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  if (data && method !== 'GET') {
    config.body = JSON.stringify(data);
  }
  const res = await fetch(`${BASE_URL}${url}`, config);
  if (!res.ok) {
    const err = await res.json().catch(() => ({ message: res.statusText }));
    throw new Error(err.error || err.message || `خطأ ${res.status}`);
  }
  if (res.status === 204) return null;
  const json = await res.json();
  return json.data !== undefined ? json : json;
}

export const api = {
  get: (url, params) => request('GET', url + buildQueryString(params)),
  post: (url, data) => request('POST', url, data),
  put: (url, data) => request('PUT', url, data),
  patch: (url, data) => request('PATCH', url, data),
  delete: (url) => request('DELETE', url),
};

// ─── Cases ────────────────────────────────────────────
export async function fetchCases(filters = {}, page = 1, pageSize = 20) {
  const result = await api.get('/cases', { ...filters, page, pageSize });
  return result;
}

export async function fetchCase(id) {
  const result = await api.get(`/cases/${id}`);
  return result.data || result;
}

export function createCase(data) {
  return api.post('/cases', data);
}

export function updateCase(id, data) {
  return api.put(`/cases/${id}`, data);
}

export function deleteCase(id) {
  return api.delete(`/cases/${id}`);
}

// ─── Actions ──────────────────────────────────────────
export async function fetchActions(filters = {}, page = 1, pageSize = 20) {
  return api.get('/actions', { ...filters, page, pageSize });
}

export function createAction(data) {
  return api.post('/actions', data);
}

export function updateAction(id, data) {
  return api.put(`/actions/${id}`, data);
}

export function updateActionStatus(id, status) {
  return api.patch(`/actions/${id}/status`, { request_status: status });
}

export function deleteAction(id) {
  return api.delete(`/actions/${id}`);
}

// ─── Lookups ──────────────────────────────────────────
export async function fetchLookups() {
  const [courts, lawyers, caseTypes, actionCatalog, informAddresses] = await Promise.all([
    api.get('/lookups/courts'),
    api.get('/lookups/lawyers'),
    api.get('/lookups/case-types'),
    api.get('/lookups/actions-catalog'),
    api.get('/lookups/inform-addresses'),
  ]);
  return {
    courts: courts.data || courts || [],
    lawyers: lawyers.data || lawyers || [],
    caseTypes: caseTypes.data || caseTypes || [],
    actionCatalog: actionCatalog.data || actionCatalog || [],
    informAddresses: informAddresses.data || informAddresses || [],
  };
}

export async function fetchUsers() {
  const result = await api.get('/lookups/users');
  return result.data || result || [];
}

// ─── Stats ────────────────────────────────────────────
export async function fetchStats() {
  const result = await api.get('/stats');
  const d = result.data || result;
  return {
    totalCases: d.total_cases || 0,
    totalActions: d.total_actions || 0,
    pendingRequests: d.pending_requests || 0,
    persistence: d.persistence || { total: 0, red: 0, orange: 0, green: 0 },
    legalContracts: d.legal_contracts || 0,
    collection: d.collection || { count: 0, available_amount: 0 },
    overdueDeadlines: 0,
  };
}

// ─── Persistence ──────────────────────────────────────
export async function fetchPersistence(filters = {}) {
  return api.get('/persistence', filters);
}

export function refreshPersistence() {
  return api.post('/persistence/refresh');
}

// ─── Legal ────────────────────────────────────────────
export async function fetchLegal(filters = {}, page = 1, pageSize = 20) {
  return api.get('/legal', { ...filters, page, pageSize });
}

// ─── Collection ───────────────────────────────────────
export async function fetchCollection(filters = {}, page = 1, pageSize = 20) {
  return api.get('/collection', { ...filters, page, pageSize });
}

export function createCollection(data) {
  return api.post('/collection', data);
}

export function updateCollection(id, data) {
  return api.put(`/collection/${id}`, data);
}

export function deleteCollection(id) {
  return api.delete(`/collection/${id}`);
}

// ─── Timeline ─────────────────────────────────────────
export async function fetchTimeline(caseId) {
  const result = await api.get(`/timeline/case/${caseId}`);
  return result.data || result || [];
}

// ─── Deadlines ────────────────────────────────────────
export async function fetchDeadlines(caseId, filters = {}) {
  if (caseId) {
    const result = await api.get(`/deadlines/case/${caseId}`);
    return result.data || result || [];
  }
  const result = await api.get('/deadlines', filters);
  return result;
}

export async function fetchDeadlineStats() {
  const result = await api.get('/deadlines/stats');
  return result.data || result;
}

export async function syncDeadlines() {
  return api.post('/deadlines/sync');
}

export async function createDeadline(data) {
  return api.post('/deadlines', data);
}

export async function updateDeadline(id, data) {
  return api.put(`/deadlines/${id}`, data);
}

export async function completeDeadline(id) {
  return api.patch(`/deadlines/${id}/complete`);
}

export async function deleteDeadline(id) {
  return api.delete(`/deadlines/${id}`);
}

// ─── Correspondence ───────────────────────────────────
export async function fetchCorrespondence(caseId) {
  const result = await api.get(`/correspondence/case/${caseId}`);
  return result.data || result || [];
}

// ─── Assets ───────────────────────────────────────────
export async function fetchAssets(caseId) {
  const result = await api.get(`/assets/case/${caseId}`);
  return result.data || result || [];
}

// ─── Export ───────────────────────────────────────────
export async function exportExcel(type, filters = {}) {
  const qs = buildQueryString(filters);
  const res = await fetch(`${BASE_URL}/exports/excel/${type}${qs}`);
  if (!res.ok) throw new Error('فشل تصدير الملف');
  return res.blob();
}

export async function exportPdf(type, filters = {}) {
  const qs = buildQueryString(filters);
  const res = await fetch(`${BASE_URL}/exports/pdf/${type}${qs}`);
  if (!res.ok) throw new Error('فشل تصدير الملف');
  return res.blob();
}

// ─── Batch ────────────────────────────────────────────
export function batchCreateCases(data) {
  return api.post('/batch/cases', data);
}

export function batchCreateActions(data) {
  return api.post('/batch/actions', data);
}

export function parseCaseNumbers(text) {
  return api.post('/batch/parse-case-numbers', { text });
}
