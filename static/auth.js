(function () {
  'use strict';

  var ACCOUNTS = {
    'owner@umkm.local': { email: 'owner@umkm.local', name: 'Owner UMKM', role: 'owner' },
    'siti@umkm.local': { email: 'siti@umkm.local', name: 'Siti Aminah', role: 'employee' }
  };

  var form = document.getElementById('loginForm');
  var alertBox = document.getElementById('loginAlert');

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
