/* =====================================================
   FaltaUno — main.js
   Entry point: wires up modules and exposes the handlers
   the HTML's inline onclick/onkeydown attributes call.
   ===================================================== */

import { api } from './api.js';
import { $ } from './helpers.js';
import { state } from './state.js';
import { go } from './router.js';
import { authMode, doLogin, doRegister, doLogout, enterApp } from './auth.js';
import { setFilter, loadFeed } from './home.js';
import { renderMyMatches, openDetail, joinMatch, leaveMatch, cancelMatch, shareMatch, goBack } from './matches.js';
import { renderChats, openChat, closeChat, sendMsg, openChatMatchDetail } from './chat.js';
import { pickFormat, createMatch } from './create-match.js';
import { openSheet, closeSheet, pickNeighborhood } from './neighborhoods.js';

// Expose to window so the existing inline onclick="" / onkeydown=""
// attributes in index.html can keep calling these directly.
Object.assign(window, {
  go, authMode, doLogin, doRegister, doLogout,
  setFilter, openSheet, closeSheet, pickNeighborhood,
  openDetail, shareMatch, joinMatch, leaveMatch, cancelMatch, goBack,
  openChat, closeChat, sendMsg, openChatMatchDetail,
  pickFormat, createMatch,
});

let searchTimer = null;
$('search-input').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadFeed, 350);
});

(async function boot() {
  try {
    const r = await api('auth.php?action=me');
    if (r.user) {
      state.user = r.user;
      enterApp();
      return;
    }
  } catch (e) { /* no active session */ }
  go('auth');
})();
