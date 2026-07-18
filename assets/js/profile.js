/* =====================================================
   FaltaUno — profile.js
   User profile screen
   ===================================================== */

import { api } from './api.js';
import { $, initials } from './helpers.js';
import { state } from './state.js';

export async function renderProfile() {
  const u = state.user;
  $('profile-avatar').textContent = initials(u.nombre);
  $('profile-avatar').style.background = u.avatar_color;
  $('profile-name').textContent = u.nombre;
  $('profile-sub').textContent = [u.barrio || u.ciudad, u.posicion].filter(Boolean).join(' · ');
  try {
    const r = await api('auth.php?action=me');
    $('st-joined').textContent = r.stats.inscritos;
    $('st-played').textContent = r.stats.jugados;
    $('st-created').textContent = r.stats.creados;
  } catch (e) {}
}
