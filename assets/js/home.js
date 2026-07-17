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

export function renderFeed() {
  const list = state.matches;
  $('sec-count').textContent = list.length + (list.length === 1 ? ' match' : ' matches');
  $('sec-title').textContent = state.neighborhood ? 'Matches in ' + state.neighborhood : 'Matches near you';
  const todayN = list.filter((p) => isToday(p.fecha)).length;
  $('head-sub').textContent = list.length
    ? `${list.length} match${list.length !== 1 ? 'es' : ''} looking for players${todayN ? ` · ${todayN} today` : ''}`
    : 'Create the first match in your neighborhood';
  $('feed').innerHTML = list.length
    ? list.map((p) => cardHTML(p, 'home')).join('')
    : emptyHTML('🥅', 'No matches around here', 'Try another neighborhood or create your own with the + button');
}

export function emptyHTML(icon, title, sub) {
  return `<div class="empty"><span class="ball">${icon}</span><h3>${esc(title)}</h3><p>${esc(sub)}</p></div>`;
}

export function missingChip(p) {
  if (p.faltan <= 0) return '<span class="missing-chip full">Full ✓</span>';
  if (p.faltan === 1) return '<span class="missing-chip urgent">⚡ Need 1 more!</span>';
  return `<span class="missing-chip">${p.faltan} spots left</span>`;
}

export function cardHTML(p, ctx) {
  const avatars =
    `<div class="pav" style="background:${p.creador_color}">${initials(p.creador_nombre)}</div>` +
    (p.inscritos > 1 ? `<div class="pav more">+${p.inscritos - 1}</div>` : '');
  return `
  <article class="mcard" onclick="openDetail(${p.id},'${ctx}')">
    <div class="mcard-top">
      <svg class="mini-lines" viewBox="0 0 400 60" preserveAspectRatio="xMidYMid slice"><g fill="none" stroke="#fff" stroke-width="1.6"><circle cx="200" cy="30" r="26"/><line x1="0" y1="30" x2="400" y2="30"/></g></svg>
      <span class="fmt">${p.formato}</span>
      <span class="when"><span class="status-dot ${isToday(p.fecha) ? 'today' : ''}"></span>${formatDate(p.fecha)} · ${formatTime(p.hora)}</span>
    </div>
    <div class="mcard-body">
      <h3>${esc(p.cancha)}</h3>
      <div class="meta-row">
        <span>📍 ${esc(p.barrio)}</span><span class="dot"></span>
        <span>${esc(p.tipo_cancha)}</span><span class="dot"></span>
        <span>${p.duracion_min} min</span>
      </div>
      <div class="mcard-foot">
        <div class="players">${avatars}</div>
        ${missingChip(p)}
      </div>
      <div class="price-lvl">
        <span class="pill price">${cop(p.precio)}${p.precio > 0 ? ' /player' : ''}</span>
        <span class="pill">Level ${p.nivel}</span>
        ${p.unido ? '<span class="joined-tag">✓ Joined</span>' : ''}
      </div>
    </div>
  </article>`;
}
