import { api } from './api.js';
import { $ } from './helpers.js';
import { toast } from './toast.js';
import { loadFeed } from './home.js';
import { openDetail } from './matches.js';

const SPOTS_BY_FORMAT = { F5: 10, F7: 14, F11: 22 };

export function initCreateMatch() {
  const today = new Date();
  $('c-date').min = today.toISOString().slice(0, 10);
  if (!$('c-date').value) $('c-date').value = today.toISOString().slice(0, 10);
}