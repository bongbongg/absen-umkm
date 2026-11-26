(function () {
  'use strict';

  var PAGE = document.body.getAttribute('data-page') || 'dashboard';
  var user = null;

  try {
    user = JSON.parse(sessionStorage.getItem('demoUser') || 'null');
  } catch (err) {
    user = null;
  }

  if (!user) {
    window.location.replace('index.html');
    return;
  }

  var owner = user.role === 'owner';
  if (!owner && (PAGE === 'employees' || PAGE === 'reports')) {
    window.location.replace('dashboard.html');
    return;
  }

  /* Mode Lo-Fi bisa dinyalakan dari halaman wireframe atau tombol di header. */
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

  function esc(value) {
    return String(value)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function badge(text, type) {
    return '<span class="badge badge--' + (type || 'muted') + '">' + esc(text) + '</span>';
  }

  function stat(label, value, type) {
    return '<div class="stat stat--' + (type || 'primary') + '">'
      + '<div class="stat__label">' + esc(label) + '</div>'
      + '<div class="stat__value">' + esc(value) + '</div>'
      + '<div class="stat__hint">Data contoh</div>'
      + '</div>';
  }

  function header(title, subtitle, action) {
    return '<div class="page__header"><div><h1 class="page__title">' + esc(title)
      + '</h1><p class="page__subtitle">' + esc(subtitle) + '</p></div>'
      + (action || '') + '</div>';
  }

  function card(title, body) {
    return '<div class="card"><div class="card__header"><div class="card__title">'
      + esc(title) + '</div></div><div class="card__body">' + body + '</div></div>';
  }

  function table(headers, rows) {
    var html = '<div class="table-wrap"><table class="table table--compact"><thead><tr>';
    headers.forEach(function (h) { html += '<th>' + esc(h) + '</th>'; });
    html += '</tr></thead><tbody>';
    rows.forEach(function (row) {
      html += '<tr>';
      row.forEach(function (cell) { html += '<td>' + cell + '</td>'; });
      html += '</tr>';
    });
    return html + '</tbody></table></div>';
  }

  var NAV_OWNER = [
    ['dashboard', 'Dashboard', 'dashboard.html', '⌂'],
    ['employees', 'Data Karyawan', 'employees.html', '☺'],
    ['schedules', 'Jadwal Kerja', 'schedules.html', '▦'],
    ['attendance', 'Absensi', 'attendance.html', '✓'],
    ['corrections', 'Koreksi', 'corrections.html', '↻'],
    ['tasks', 'Tugas', 'tasks.html', '☑'],
    ['reports', 'Laporan', 'reports.html', '▤']
  ];
  var NAV_EMPLOYEE = [
    ['dashboard', 'Dashboard', 'dashboard.html', '⌂'],
    ['schedules', 'Jadwal Kerja', 'schedules.html', '▦'],
    ['attendance', 'Absensi Saya', 'attendance.html', '✓'],
    ['corrections', 'Koreksi', 'corrections.html', '↻'],
    ['tasks', 'Tugas Saya', 'tasks.html', '☑']
  ];

  var navItems = owner ? NAV_OWNER : NAV_EMPLOYEE;
  var navHtml = '<div class="nav__section">MENU UTAMA</div>';
  navItems.forEach(function (item) {
    navHtml += '<a class="nav__link' + (PAGE === item[0] ? ' is-active' : '') + '" href="'
      + item[2] + '"><span class="nav__icon" aria-hidden="true">' + item[3]
      + '</span><span>' + esc(item[1]) + '</span></a>';
  });
  navHtml += '<div class="nav__section">LAINNYA</div>'
    + '<a class="nav__link" href="wireframe.html"><span class="nav__icon" aria-hidden="true">▢</span>'
    + '<span>Wireframe</span></a>';

  var initial = esc((user.name || user.email || 'U').charAt(0).toUpperCase());

  var shell = ''
    + '<div class="app">'
    + '<aside class="sidebar" id="sidebar">'
    +   '<a class="sidebar__brand" href="dashboard.html">'
    +     '<span class="sidebar__brand-mark">UM</span><span>Monitoring Absensi UMKM</span></a>'
    +   '<nav class="sidebar__nav" aria-label="Navigasi utama">' + navHtml + '</nav>'
    +   '<div class="small muted" style="margin-top:auto;padding:10px 6px">Demo statis · tanpa database</div>'
    + '</aside>'
    + '<div class="sidebar-overlay" data-nav-close></div>'
    + '<div class="main">'
    +   '<header class="topbar">'
    +     '<button class="topbar__menu" type="button" aria-label="Buka menu">☰</button>'
    +     '<div><div class="topbar__title">' + esc(PAGE === 'dashboard' ? 'Ringkasan' : 'Monitoring Absensi')
    +       '</div><div class="topbar__subtitle">UMKM · mode demo</div></div>'
    +     '<div class="topbar__actions">'
    +       '<span class="badge badge--info">DEMO</span>'
    +       '<button class="btn btn--ghost btn--sm" id="lofiToggle" type="button">Lo-Fi: '
    +         (lofi ? 'ON' : 'OFF') + '</button>'
    +       '<div class="user-chip"><span class="user-chip__avatar">' + initial + '</span>'
    +         '<span class="user-chip__meta"><span class="user-chip__name">' + esc(user.name)
    +         '</span><span class="user-chip__role">' + (owner ? 'Owner' : 'Karyawan')
    +         '</span></span></div>'
    +       '<button type="button" class="btn btn--ghost btn--sm" id="logoutBtn">Keluar</button>'
    +     '</div>'
    +   '</header>'
    +   '<main class="content" id="pageContent"></main>'
    + '</div>'
    + '</div>';

  document.getElementById('app').innerHTML = shell;

  var content = '';

  if (PAGE === 'dashboard' && owner) {
    content = header('Dashboard Owner', 'Ringkasan absensi dan progres tugas hari ini.')
      + '<div class="stats">'
      + stat('Hadir', '18', 'success') + stat('Terlambat', '3', 'warning')
      + stat('Koreksi pending', '2', 'danger') + stat('Tugas aktif', '7', 'primary')
      + '</div>'
      + card('Aktivitas terbaru', table(['WAKTU', 'AKTIVITAS', 'STATUS'], [
        ['08:12', 'Budi Santoso check-in', badge('Hadir', 'success')],
        ['08:35', 'Pengajuan koreksi · Siti', badge('Pending', 'warning')],
        ['09:05', 'Tugas «Rekap stok» progres 60%', badge('Berjalan', 'primary')],
        ['10:20', 'Bukti pekerjaan diunggah · Rizky', badge('Menunggu', 'info')]
      ]))
      + '<div style="height:18px"></div>'
      + card('Progres tugas', table(['TUGAS', 'KARYAWAN', 'PROGRES', 'STATUS'], [
        ['Rekap penjualan', 'Budi', '60%', badge('Berjalan', 'primary')],
        ['Input stok gudang', 'Rizky', '100%', badge('Menunggu review', 'info')],
        ['Foto display toko', 'Siti', '0%', badge('Belum dimulai', 'muted')]
      ]));
  } else if (PAGE === 'dashboard') {
    content = header('Dashboard Karyawan', 'Halo ' + user.name + ', ini ringkasan aktivitas Anda.')
      + '<div class="stats">'
      + stat('Hadir bulan ini', '22', 'success') + stat('Terlambat', '1', 'warning')
      + stat('Tugas aktif', '2', 'primary') + stat('Koreksi disetujui', '1', 'info')
      + '</div>'
      + card('Jadwal hari ini', table(['TANGGAL', 'JAM MASUK', 'JAM PULANG', 'STATUS'], [
        ['26 Nov 2026', '08:00', '17:00', badge('Berlaku', 'success')]
      ]))
      + '<div style="height:18px"></div>'
      + card('Tugas terbaru', table(['TUGAS', 'PROGRES', 'STATUS', 'AKSI'], [
        ['Rekap stok & penjualan', '60%', badge('Berjalan', 'primary'), '<button class="btn btn--sm btn--secondary" type="button">Update progres</button>'],
        ['Foto display toko', '0%', badge('Belum dimulai', 'muted'), '<button class="btn btn--sm btn--secondary" type="button">Update progres</button>']
      ]));
  } else if (PAGE === 'employees') {
    content = header('Data Karyawan', 'Kelola akun dan identitas karyawan UMKM.')
      + card('Daftar karyawan', table(['ID', 'NAMA', 'EMAIL', 'JABATAN', 'STATUS'], [
        ['EMP-001', 'Budi Santoso', 'budi@umkm.local', 'Staf', badge('Aktif', 'success')],
        ['EMP-002', 'Siti Aminah', 'siti@umkm.local', 'Staf', badge('Aktif', 'success')],
        ['EMP-003', 'Rizky Pratama', 'rizky@umkm.local', 'Staf', badge('Aktif', 'success')]
      ]));
  } else if (PAGE === 'schedules') {
    content = header('Jadwal Kerja', owner ? 'Atur jadwal masuk dan pulang karyawan.' : 'Lihat jadwal kerja Anda.')
      + card('Daftar jadwal', table(['TANGGAL', 'KARYAWAN', 'MASUK', 'PULANG'], owner ? [
        ['26 Nov 2026', 'Budi Santoso', '08:00', '17:00'],
        ['26 Nov 2026', 'Siti Aminah', '09:00', '18:00'],
        ['26 Nov 2026', 'Rizky Pratama', '08:00', '16:00']
      ] : [
        ['26 Nov 2026', 'Siti Aminah', '09:00', '18:00'],
        ['25 Nov 2026', 'Siti Aminah', '09:00', '18:00']
      ]));
  } else if (PAGE === 'attendance') {
    content = header(owner ? 'Absensi Hari Ini' : 'Absensi Saya',
      owner ? 'Pantau kehadiran karyawan.' : 'Catat kehadiran dan lihat riwayat Anda.')
      + (owner ? '' : card('Absensi hari ini', '<div class="row" style="gap:8px;flex-wrap:wrap">'
        + '<button class="btn btn--primary btn--sm" type="button">Check-in</button>'
        + '<button class="btn btn--secondary btn--sm" type="button">Check-out</button>'
        + badge('Belum check-out', 'warning') + '</div>'))
      + (owner ? '' : '<div style="height:18px"></div>')
      + card('Riwayat absensi', table(['TANGGAL', 'KARYAWAN', 'MASUK', 'PULANG', 'STATUS'], owner ? [
        ['26 Nov 2026', 'Budi', '08:02', '17:05', badge('Terlambat', 'warning')],
        ['26 Nov 2026', 'Siti', '09:01', '18:00', badge('Hadir', 'success')],
        ['25 Nov 2026', 'Rizky', '08:00', '16:02', badge('Hadir', 'success')]
      ] : [
        ['26 Nov 2026', 'Siti', '09:01', '-', badge('Belum pulang', 'warning')],
        ['25 Nov 2026', 'Siti', '09:00', '18:00', badge('Hadir', 'success')]
      ]));
  } else if (PAGE === 'corrections') {
    content = header('Koreksi Absensi', owner ? 'Setujui atau tolak pengajuan koreksi.' : 'Ajukan koreksi data absensi.')
      + card(owner ? 'Pengajuan pending' : 'Pengajuan saya', table(
        ['TANGGAL', 'KARYAWAN', 'LAMA', 'DIAJUKAN', 'STATUS', 'AKSI'], owner ? [
          ['24 Nov 2026', 'Siti', '09:10', '09:00', badge('Pending', 'warning'),
            '<button class="btn btn--sm btn--primary" type="button">Setujui</button>'],
          ['23 Nov 2026', 'Rizky', '16:10', '16:00', badge('Pending', 'warning'),
            '<button class="btn btn--sm btn--primary" type="button">Setujui</button>']
        ] : [
          ['24 Nov 2026', 'Siti', '09:10', '09:00', badge('Pending', 'warning'), '-'],
          ['20 Nov 2026', 'Siti', '09:05', '09:00', badge('Disetujui', 'success'), '-']
        ]));
  } else if (PAGE === 'tasks') {
    content = header(owner ? 'Daftar Tugas' : 'Tugas Saya',
      owner ? 'Pantau progres dan persetujuan tugas.' : 'Perbarui progres dan unggah bukti kerja.')
      + card('Daftar tugas', table(['TUGAS', 'KARYAWAN', 'PROGRES', 'STATUS'], owner ? [
        ['Rekap penjualan', 'Budi', '60%', badge('Berjalan', 'primary')],
        ['Input stok gudang', 'Rizky', '100%', badge('Menunggu review', 'info')],
        ['Foto display toko', 'Siti', '0%', badge('Belum dimulai', 'muted')]
      ] : [
        ['Rekap stok & penjualan', 'Siti', '60%', badge('Berjalan', 'primary')],
        ['Foto display toko', 'Siti', '0%', badge('Belum dimulai', 'muted')]
      ]));
  } else if (PAGE === 'reports') {
    content = header('Laporan & Ekspor', 'Filter periode lalu unduh laporan.')
      + card('Filter laporan', '<div class="row" style="gap:10px;flex-wrap:wrap">'
        + '<input class="input" type="date" value="2026-11-01" style="max-width:180px">'
        + '<input class="input" type="date" value="2026-11-30" style="max-width:180px">'
        + '<button class="btn btn--primary btn--sm" type="button">Tampilkan</button></div>')
      + '<div style="height:18px"></div>'
      + card('Unduh laporan', '<div class="row" style="gap:8px;flex-wrap:wrap">'
        + '<button class="btn btn--secondary btn--sm" type="button">↓ Ekspor PDF</button>'
        + '<button class="btn btn--secondary btn--sm" type="button">↓ Ekspor Excel</button></div>');
  } else {
    content = header('Halaman tidak tersedia', 'Kembali ke dashboard.')
      + '<a class="btn btn--primary" href="dashboard.html">Ke dashboard</a>';
  }

  document.getElementById('pageContent').innerHTML = content;

  /* Tombol aksi hanya Memberi notifikasi sukses, tanpa mengubah data. */
  var toastTimer = null;

  function showToast(message) {
    var toast = document.getElementById('demoToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'demoToast';
      toast.className = 'demo-toast';
      document.body.appendChild(toast);
    }
    toast.innerHTML = '<div class="alert alert--success"><strong>Berhasil.</strong> '
      + esc(message) + '</div>';
    toast.classList.add('is-visible');
    if (toastTimer) {
      window.clearTimeout(toastTimer);
    }
    toastTimer = window.setTimeout(function () {
      toast.classList.remove('is-visible');
    }, 3200);
  }

  function successMessage(label) {
    var text = label.toLowerCase();
    if (text.indexOf('check-in') !== -1) return 'Check-in berhasil dicatat.';
    if (text.indexOf('check-out') !== -1) return 'Check-out berhasil dicatat.';
    if (text.indexOf('setujui') !== -1) return 'Pengajuan disetujui.';
    if (text.indexOf('ekspor') !== -1) return 'Laporan siap diunduh (simulasi).';
    if (text.indexOf('tampilkan') !== -1) return 'Filter berhasil diterapkan.';
    if (text.indexOf('progres') !== -1) return 'Progres berhasil diperbarui.';
    return 'Aksi berhasil dilakukan.';
  }

  var pageButtons = document.querySelectorAll('#pageContent button.btn');
  Array.prototype.forEach.call(pageButtons, function (button) {
    button.addEventListener('click', function () {
      showToast(successMessage(button.textContent || 'Aksi'));
    });
  });

  /* Lo-Fi toggle. */
  var toggle = document.getElementById('lofiToggle');
  if (toggle) {
    toggle.addEventListener('click', function (event) {
      event.preventDefault();
      var next = !lofi;
      try {
        window.localStorage.setItem('lofi', next ? '1' : '0');
      } catch (err) {
        /* Abaikan. */
      }
      window.location.reload();
    });
  }

  /* Logout demo: popup konfirmasi, lalu hapus sesi lokal. */
  var logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    var modal = document.createElement('div');
    modal.className = 'app-modal';
    modal.hidden = true;
    modal.innerHTML = ''
      + '<div class="app-modal__backdrop" data-app-modal-close></div>'
      + '<div class="app-modal__card" role="dialog" aria-modal="true">'
      +   '<div class="app-modal__icon" aria-hidden="true">?</div>'
      +   '<h2 class="app-modal__title">Konfirmasi</h2>'
      +   '<p class="app-modal__text">Yakin ingin keluar dari akun ini?</p>'
      +   '<div class="app-modal__actions">'
      +     '<button type="button" class="btn btn--secondary" data-app-modal-close>Batal</button>'
      +     '<button type="button" class="btn btn--primary" id="logoutYes">Ya, keluar</button>'
      +   '</div>'
      + '</div>';
    document.body.appendChild(modal);

    function closeModal() {
      modal.hidden = true;
      document.body.classList.remove('modal-open');
    }

    logoutBtn.addEventListener('click', function () {
      modal.hidden = false;
      document.body.classList.add('modal-open');
      var yes = document.getElementById('logoutYes');
      if (yes && yes.focus) {
        yes.focus();
      }
    });

    Array.prototype.forEach.call(modal.querySelectorAll('[data-app-modal-close]'), function (el) {
      el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
      var key = event && (event.key || event.keyCode);
      if ((key === 'Escape' || key === 'Esc' || key === 27) && !modal.hidden) {
        closeModal();
      }
    });

    var yesBtn = document.getElementById('logoutYes');
    if (yesBtn) {
      yesBtn.addEventListener('click', function () {
        closeModal();
        try {
          sessionStorage.removeItem('demoUser');
        } catch (err) {
          /* Abaikan. */
        }
        window.location.href = 'index.html';
      });
    }
  }
})();
