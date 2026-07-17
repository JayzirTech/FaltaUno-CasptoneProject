import { api } from './api.js';
import { $, esc, initials } from './helpers.js';
import { state } from './state.js';
import { go } from './router.js';
import { toast } from './toast.js';
import { loadFeed } from './home.js';

export function authMode(mode) {
  $('tab-login').classList.toggle('on', mode === 'login');
  $('tab-register').classList.toggle('on', mode === 'register');
  $('form-login').style.display = mode === 'login' ? 'block' : 'none';
  $('form-register').style.display = mode === 'register' ? 'block' : 'none';
  $('auth-err').textContent = '';
}


export async function doLogin() {
  const btn = $('btn-login');
  btn.classList.add('loading');
  $('auth-err').textContent = '';
  try {
    const r = await api('auth.php?action=login', {
      email: $('login-email').value,
      password: $('login-pass').value,
    });
    state.user = r.user;
    enterApp();
  } catch (e) {
    $('auth-err').textContent = e.message;
  } finally {
    btn.classList.remove('loading');
  }


  export async function doRegister() {
  const btn = $('btn-register');
  btn.classList.add('loading');
  $('auth-err').textContent = '';
  try {
    const r = await api('auth.php?action=register', {
      nombre: $('reg-name').value,
      email: $('reg-email').value,
      password: $('reg-pass').value,
      posicion: $('reg-position').value,
      barrio: $('reg-neighborhood').value,
      telefono: $('reg-phone').value,
    });
    state.user = r.user;
    toast('🎉 Welcome to FaltaUno!');
    enterApp();
  } catch (e) {
    $('auth-err').textContent = e.message;
  } finally {
    btn.classList.remove('loading');
  }
}