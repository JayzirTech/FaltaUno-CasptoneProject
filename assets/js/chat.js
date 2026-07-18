/* =====================================================
   FaltaUno — chat.js
   Match group chat: list, open, poll, send messages
   ===================================================== */

import { api } from './api.js';
import { $, esc, formatDate, formatTime } from './helpers.js';
import { chatState } from './state.js';
import { go } from './router.js';
import { toast } from './toast.js';
import { emptyHTML } from './home.js';
import { openDetail } from './matches.js';

export function openChatMatchDetail() {
  openDetail(chatState.matchId, 'chats');
}

export async function renderChats() {
  const el = $('chats-list');
  el.innerHTML = '<div class="skel" style="margin:0 var(--gx);height:70px"></div>';
  try {
    const r = await api('chat.php?action=resumen');
    if (!r.chats.length) {
      el.innerHTML = emptyHTML('💬', 'No chats yet', "When you join a match, its group chat will show up here");
      return;
    }
    el.innerHTML = r.chats.map((c) => {
      const preview = !c.ultimo_texto
        ? 'New group'
        : c.ultimo_tipo === 'sys'
          ? esc(c.ultimo_texto)
          : `<b>${esc((c.ultimo_autor || '').split(' ')[0])}:</b> ${esc(c.ultimo_texto)}`;
      return `
      <div class="chat-item" onclick="openChat(${c.id})">
        <div class="chat-ava">⚽</div>
        <div class="ci-mid">
          <div class="ci-top"><b>${esc(c.cancha)} · ${formatDate(c.fecha)} ${formatTime(c.hora)}</b></div>
          <div class="ci-preview">${preview}</div>
        </div>
      </div>`;
    }).join('');
  } catch (e) {
    el.innerHTML = emptyHTML('📡', 'Error loading', e.message);
  }
}

export async function openChat(id) {
  chatState.matchId = id;
  chatState.lastId = 0;
  $('chat-body').innerHTML = '<div class="day-chip">Loading…</div>';
  $('chat-title').textContent = '';
  $('chat-members').textContent = '';
  go('chat');

  try {
    const d = await api('partidos.php?id=' + id);
    const p = d.partido;
    $('chat-title').textContent = `${p.cancha} · ${formatDate(p.fecha)} ${formatTime(p.hora)}`;
    $('chat-members').textContent = p.jugadores.map((j) => j.nombre.split(' ')[0] + (j.es_admin ? ' (admin)' : '')).join(', ');
    $('chat-body').innerHTML = '';
    await pollChat(true);
    startChatPoll();
  } catch (e) {
    $('chat-body').innerHTML = `<div class="sys-msg">${esc(e.message)}</div>`;
  }
}

export function closeChat() { stopChatPoll(); go('chats'); }
export function startChatPoll() { stopChatPoll(); chatState.poll = setInterval(() => pollChat(false), 3500); }
export function stopChatPoll() { if (chatState.poll) { clearInterval(chatState.poll); chatState.poll = null; } }

async function pollChat(scroll) {
  if (!chatState.matchId) return;
  try {
    const r = await api(`chat.php?partido_id=${chatState.matchId}&desde=${chatState.lastId}`);
    if (!r.mensajes.length) return;
    const body = $('chat-body');
    const nearBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 120;
    r.mensajes.forEach((m) => {
      chatState.lastId = Math.max(chatState.lastId, m.id);
      body.insertAdjacentHTML('beforeend', msgHTML(m));
    });
    if (scroll || nearBottom) body.scrollTop = body.scrollHeight;
  } catch (e) { /* silent during polling */ }
}

function msgHTML(m) {
  if (m.tipo === 'sys') return `<div class="sys-msg">${esc(m.texto)}</div>`;
  if (m.mio) {
    return `<div class="msg me"><div class="bubble">${esc(m.texto)}<div class="time">${m.hora} ✓✓</div></div></div>`;
  }
  return `<div class="msg"><div class="bubble"><div class="sender" style="color:${m.avatar_color || 'var(--green-ink)'}">${esc(m.nombre || 'Player')}</div>${esc(m.texto)}<div class="time">${m.hora}</div></div></div>`;
}

export async function sendMsg() {
  const input = $('chat-msg');
  const texto = input.value.trim();
  if (!texto || !chatState.matchId) return;
  input.value = '';
  try {
    await api('chat.php', { partido_id: chatState.matchId, texto });
    await pollChat(true);
  } catch (e) {
    toast('⚠️ ' + e.message);
    input.value = texto;
  }
}
