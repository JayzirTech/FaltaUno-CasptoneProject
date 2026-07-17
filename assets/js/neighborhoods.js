/* =====================================================
   FaltaUno — neighborhoods.js
   Neighborhood filter bottom sheet
   ===================================================== */

import { api } from './api.js';
import { $, esc } from './helpers.js';
import { state } from './state.js';
import { loadFeed } from './home.js';

export async function openSheet() {
  const list = $('neighborhood-list');
  list.innerHTML = '<div class="barrio-row">Loading…</div>';
  $('sheet-back').classList.add('open');
  $('sheet').classList.add('open');
  try {
    const r = await api('partidos.php?action=barrios');
    const total = r.barrios.reduce((a, b) => a + Number(b.n), 0);
    const rows = [{ barrio: '', n: total, label: 'All neighborhoods' }]
      .concat(r.barrios.map((b) => ({ barrio: b.barrio, n: b.n, label: b.barrio })));
    list.innerHTML = rows.map((b) => `
      <div class="barrio-row ${state.neighborhood === b.barrio ? 'sel' : ''}" onclick="pickNeighborhood('${esc(b.barrio)}')">
        <span>${state.neighborhood === b.barrio ? '✓ ' : ''}${esc(b.label)}</span>
        <span class="n">${b.n} match${b.n != 1 ? 'es' : ''}</span>
      </div>`).join('');
  } catch (e) {
    list.innerHTML = `<div class="barrio-row">${esc(e.message)}</div>`;
  }
}
