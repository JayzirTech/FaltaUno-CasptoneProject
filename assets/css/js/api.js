/* =====================================================
   FaltaUno — api.js
   Frontend connected to the PHP API (api/)
   NOTE: endpoint paths and JSON field names below match
   the existing PHP backend contract and are intentionally
   left unchanged so this frontend keeps working with it.
   ===================================================== */

const API = 'api';

export async function api(path, data = null, method = null) {
  const opts = {
    method: method || (data ? 'POST' : 'GET'),
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
  };
  if (data) opts.body = JSON.stringify(data);
  const res = await fetch(`${API}/${path}`, opts);
  const json = await res.json().catch(() => ({ ok: false, error: 'Invalid server response' }));
  if (!res.ok || json.ok === false) throw new Error(json.error || 'Something went wrong. Please try again.');
  return json;
}
