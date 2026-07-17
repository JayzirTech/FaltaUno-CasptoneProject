import { api } from './api.js';
import { $, esc, cop, initials, isToday, formatDate, formatTime } from './helpers.js';
import { state } from './state.js';

export function setFilter(btn) {
  document.querySelectorAll('#chips .chip').forEach((c) => c.classList.remove('on'));
  btn.classList.add('on');
  state.filter = btn.dataset.f;
  loadFeed();
}