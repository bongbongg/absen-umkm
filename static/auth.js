(function () {
  'use strict';

  var ACCOUNTS = {
    'owner@umkm.local': { email: 'owner@umkm.local', name: 'Owner UMKM', role: 'owner' },
    'siti@umkm.local': { email: 'siti@umkm.local', name: 'Siti Aminah', role: 'employee' }
  };

  var form = document.getElementById('loginForm');
  var alertBox = document.getElementById('loginAlert');

  /* Mode Lo-Fi juga tersedia di halaman login. */
  var lofi = false;
  try {
    lofi = window.localStorage.getItem('lofi') === '1';
  } catch (err) {
    lofi = false;
  }
  if (lofi) {
    document.body.classList.add('wireframe');
    var wfCss = document.createElement('link');
    wfCss.rel = 'stylesheet';
    wfCss.href = 'public/assets/css/wireframe.css';
    document.head.appendChild(wfCss);
  }
  var lofiToggle = document.getElementById('lofiToggleLogin');
  if (lofiToggle) {
    lofiToggle.textContent = 'Lo-Fi: ' + (lofi ? 'ON' : 'OFF');
    lofiToggle.addEventListener('click', function () {
      try {
        window.localStorage.setItem('lofi', lofi ? '0' : '1');
      } catch (err) {
        /* Abaikan. */
      }
      window.location.reload();
    });
  }

  if (!form) {
    return;
  }

  /* Kalau sudah login, langsung masuk dashboard. */
  try {
    if (sessionStorage.getItem('demoUser')) {
      window.location.replace('dashboard.html');
      return;
    }
  } catch (err) {
    /* sessionStorage bisa diblokir; abaikan. */
  }

  function showError(message) {
    if (!alertBox) {
      return;
    }
    alertBox.innerHTML = '<div class="alert alert--error">' + message + '</div>';
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    var email = (document.getElementById('email').value || '').trim().toLowerCase();
    var password = document.getElementById('password').value || '';
    var user = ACCOUNTS[email];

    if (!user || password !== 'password123') {
      showError('Email atau kata sandi salah. Coba akun demo yang tersedia.');
      return;
    }

    try {
      sessionStorage.setItem('demoUser', JSON.stringify(user));
    } catch (err) {
      showError('Browser memblokir penyimpanan sesi. Aktifkan cookies/site data.');
      return;
    }

    window.location.href = 'dashboard.html';
  });
})();
