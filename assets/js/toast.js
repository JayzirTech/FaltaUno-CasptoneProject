/* =====================================================
   FaltaUno — toast.js
   Small bottom notification
   ===================================================== */

import { $ } from './helpers.js';

let toastTimer;

export function toast(msg) {
  const t = $('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
}
