import { api } from './api.js';
import { $, esc, cop, initials, isToday, formatDate, formatTime } from './helpers.js';
import { state } from './state.js';

export function setFilter(btn) {
  document.querySelectorAll('#chips .chip').forEach((c) => c.classList.remove('on'));
  btn.classList.add('on');
  state.filter = btn.dataset.f;
  loadFeed();
}

function filterQuery() {
  const p = new URLSearchParams();
  if (state.neighborhood) p.set('barrio', state.neighborhood);
  if (['F5', 'F7', 'F11'].includes(state.filter)) p.set('formato', state.filter);
  if (state.filter === 'today') p.set('dia', 'hoy');
  if (state.filter === 'free') p.set('gratis', '1');
  if (state.filter === 'needs1') p.set('falta1', '1');
  const q = $('search-input').value.trim();
  if (q) p.set('q', q);
  return p.toString();
} 

export async function loadFeed() {
  const feed = $('feed');
  feed.innerHTML = '<div class="skel"></div><div class="skel"></div><div class="skel"></div>';
  try {
    const r = await api('partidos.php?' + filterQuery());
    state.matches = r.partidos;
    renderFeed();
  } catch (e) {
    feed.innerHTML = emptyHTML('📡', "We couldn't load the matches", e.message);
  }
}