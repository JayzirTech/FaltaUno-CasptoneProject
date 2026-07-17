export const $ = (id) => document.getElementById(id);

export const esc = (s) =>
  String(s ?? '').replace(/[<>&"]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;' }[c]));

export const cop = (n) => (Number(n) === 0 ? 'Free field' : '$' + Number(n).toLocaleString('en-US'));

export const initials = (name) =>
  name.split(' ').map((w) => w[0]).join('').slice(0, 2).toUpperCase();

export const formatLabel = { F5: '5-a-side', F7: '7-a-side', F11: '11-a-side' };