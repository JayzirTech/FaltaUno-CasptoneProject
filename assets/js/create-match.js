import { api } from './api.js';
import { $ } from './helpers.js';
import { toast } from './toast.js';
import { loadFeed } from './home.js';
import { openDetail } from './matches.js';

const SPOTS_BY_FORMAT = { F5: 10, F7: 14, F11: 22 };

export function initCreateMatch() {
  const today = new Date();
  $('c-date').min = today.toISOString().slice(0, 10);
  if (!$('c-date').value) $('c-date').value = today.toISOString().slice(0, 10);
}

export function pickFormat(btn) {
  document.querySelectorAll('#c-format .seg-btn').forEach((b) => b.classList.remove('on'));
  btn.classList.add('on');
  $('c-spots').value = SPOTS_BY_FORMAT[btn.dataset.v];
}

export async function createMatch() {
  const btn = $('btn-create');
  btn.classList.add('loading');
  try {
    const r = await api('partidos.php?action=crear', {
      cancha: $('c-court').value,
      direccion: $('c-address').value,
      barrio: $('c-neighborhood').value,
      formato: document.querySelector('#c-format .seg-btn.on').dataset.v,
      fecha: $('c-date').value,
      hora: $('c-time').value,
      precio: Number($('c-price').value || 0),
      cupos_total: Number($('c-spots').value || 10),
      nivel: $('c-level').value,
      tipo_cancha: $('c-court-type').value,
      duracion_min: Number($('c-duration').value),
    });
    toast('⚽ Match published!');
    ['c-court', 'c-address', 'c-neighborhood', 'c-price'].forEach((id) => ($(id).value = ''));
    loadFeed();
    openDetail(r.id, 'home');
  } catch (e) {
    toast('⚠️ ' + e.message);
  } finally {
    btn.classList.remove('loading');
  }
}