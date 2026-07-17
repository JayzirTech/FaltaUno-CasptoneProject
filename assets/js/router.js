/* =====================================================
   FaltaUno — router.js
   Screen navigation
   ===================================================== */

import { $ } from './helpers.js';
import { state } from './state.js';
import { stopChatPoll } from './chat.js';
import { renderMyMatches } from './matches.js';
import { renderChats } from './chat.js';
import { renderProfile } from './profile.js';
import { initCreateMatch } from './create-match.js';

export function go(screen) {
  document.querySelectorAll('.screen').forEach((s) => s.classList.remove('active'));
  $('screen-' + screen).classList.add('active');
  const isTab = ['home', 'my-matches', 'chats', 'profile'].includes(screen);
  $('tabbar').style.display = isTab && state.user ? 'flex' : 'none';
  $('fab').style.display = screen === 'home' && state.user ? 'grid' : 'none';
  document.querySelectorAll('.tab, .snav-item').forEach((t) => t.classList.toggle('on', t.dataset.t === screen));

  if (screen !== 'chat') stopChatPoll();
  if (screen === 'my-matches') renderMyMatches();
  if (screen === 'chats') renderChats();
  if (screen === 'profile') renderProfile();
  if (screen === 'create') initCreateMatch();
}
