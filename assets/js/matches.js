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