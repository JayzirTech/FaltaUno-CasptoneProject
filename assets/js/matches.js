import { api } from './api.js';
import { $, esc, cop, initials, formatDate, formatTime, formatLabel } from './helpers.js';
import { state, nav } from './state.js';
import { go } from './router.js';
import { toast } from './toast.js';
import { loadFeed } from './home.js';
import { cardHTML, emptyHTML, missingChip } from './home.js';
import { openChat } from './chat.js';

export async function renderMyMatches() {
  const el = $('my-matches-feed');
  el.innerHTML = '<div class="skel"></div><div class="skel"></div>';
  try {
    const r = await api('partidos.php?mios=1');
    el.innerHTML = r.partidos.length
      ? r.partidos.map((p) => cardHTML(p, 'my-matches')).join('')
      : emptyHTML('⚽', "You don't have any matches yet", 'Join one from Home and it will show up here');
  } catch (e) {
    el.innerHTML = emptyHTML('📡', 'Error loading', e.message);
  }
}


export async function openDetail(id, from) {
  if (window.event) window.event.stopPropagation();
  nav.detailId = id;
  nav.backTarget = from || 'home';
  $('detail-title').innerHTML = '';
  $('detail-body').innerHTML = '<div class="skel"></div>';
  $('detail-cta').innerHTML = '';
  go('detail');

  try {
    const r = await api('partidos.php?id=' + id);
    renderDetail(r.partido);
  } catch (e) {
    $('detail-body').innerHTML = emptyHTML('📡', 'Error', e.message);
  }
}

function renderDetail(p) {
  $('detail-title').innerHTML = `
    <span class="fmt-big">${formatLabel[p.formato]}</span>
    <h2>${esc(p.cancha)}</h2>
    <p>📍 ${esc(p.barrio)}, ${esc(p.ciudad)} · ${esc(p.direccion)}</p>`;

  const isAdmin = state.user && p.creador_id == state.user.id;

  const roster = p.jugadores.map((j) => `
    <div class="player-row">
      <div class="pav" style="background:${j.avatar_color};width:38px;height:38px;font-size:13px;margin:0;border:0">${initials(j.nombre)}</div>
      <div class="pinfo">
        <div class="pname">${esc(j.nombre)}${j.id == state.user.id ? ' (you)' : ''} ${j.es_admin ? '<span class="admin-star">👑 Admin</span>' : ''}</div>
        <div class="prole">${j.es_admin ? 'Created the match' : 'Confirmed player'}</div>
      </div>
      <span class="pos-tag">${esc(j.posicion)}</span>
    </div>`).join('');

  $('detail-body').innerHTML = `
    <div class="info-grid">
      <div class="info-cell"><div class="lbl">Date</div><div class="val">${formatDate(p.fecha)}</div></div>
      <div class="info-cell"><div class="lbl">Time</div><div class="val">${formatTime(p.hora)}</div></div>
      <div class="info-cell"><div class="lbl">Price</div><div class="val">${cop(p.precio)}</div></div>
      <div class="info-cell"><div class="lbl">Level</div><div class="val">${p.nivel}</div></div>
    </div>
    <div class="cupos-card">
      <div class="cupos-top"><b>Match spots</b><span>${p.inscritos}/${p.cupos_total} players</span></div>
      <div class="bar"><i style="width:${(p.inscritos / p.cupos_total) * 100}%"></i></div>
      <div style="margin-top:10px">${missingChip(p)}</div>
    </div>
    <div class="map-box">
      <svg width="100%" height="100%" viewBox="0 0 400 120" preserveAspectRatio="xMidYMid slice">
        <rect width="400" height="120" fill="#DCE7DA"/>
        <path d="M0 40 Q120 20 200 46 T400 34" stroke="#fff" stroke-width="9" fill="none"/>
        <path d="M60 120 Q90 60 150 40" stroke="#fff" stroke-width="6" fill="none"/>
        <path d="M260 120 Q270 70 330 55 T400 80" stroke="#fff" stroke-width="6" fill="none"/>
      </svg>
      <span class="pin">📍</span>
      <a class="addr" href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(p.direccion + ', ' + p.barrio + ', ' + p.ciudad)}" target="_blank" rel="noopener" onclick="event.stopPropagation()">${esc(p.direccion)} · View on Maps ↗</a>
    </div>
    <div class="roster">
      <h3>Confirmed players</h3>
      ${roster}
      ${p.faltan > 0 ? `<div class="player-row" style="border-style:dashed;justify-content:center;color:var(--muted);font-size:13px;font-weight:600">${p.faltan} spot${p.faltan > 1 ? 's' : ''} available — invite your crew</div>` : ''}
    </div>`;

  const dock = $('detail-cta');
  if (p.unido) {
    dock.innerHTML = `
      ${isAdmin
        ? `<button class="btn btn-ghost" onclick="cancelMatch(${p.id})">Cancel</button>`
        : `<button class="btn btn-ghost" onclick="leaveMatch(${p.id})">Leave</button>`}
      <button class="btn btn-primary" onclick="openChat(${p.id})">💬 Open match chat</button>`;
  } else if (p.faltan <= 0) {
    dock.innerHTML = '<button class="btn btn-primary" disabled>Match full</button>';
  } else {
    dock.innerHTML = `<button class="btn btn-primary" onclick="joinMatch(${p.id})">Join · ${cop(p.precio)}${p.precio > 0 ? ' /player' : ''}</button>`;
  }
}


export async function joinMatch(id) {
  try {
    await api('partidos.php?action=unirme', { id });
    toast('✅ You joined! The group chat is now open');
    loadFeed();
    openDetail(id, nav.backTarget);
  } catch (e) { toast('⚠️ ' + e.message); }
}

export async function leaveMatch(id) {
  if (!confirm('Are you sure you want to leave the match?')) return;
  try {
    await api('partidos.php?action=salir', { id });
    toast('You left the match');
    loadFeed();
    openDetail(id, nav.backTarget);
  } catch (e) { toast('⚠️ ' + e.message); }
}