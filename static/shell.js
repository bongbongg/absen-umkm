(function () {
  'use strict';

  var TODAY_LABEL = '26 Nov 2026';
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

  /* Mode Lo-Fi bisa dinyalakan dari header atau halaman wireframe. */
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

  /* --------------------------- helpers --------------------------- */

  function esc(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function badge(text, type) {
    return '<span class="badge badge--' + (type || 'muted') + '">' + esc(text) + '</span>';
  }

  var STATUS_LABELS = {
    present: 'Hadir', late: 'Terlambat', pending: 'Menunggu', approved: 'Disetujui',
    rejected: 'Ditolak', todo: 'Belum dimulai', in_progress: 'Berjalan',
    submitted: 'Menunggu persetujuan', revision: 'Perlu revisi', completed: 'Selesai'
  };
  var STATUS_TONES = {
    present: 'success', late: 'warning', pending: 'warning', approved: 'success',
    rejected: 'danger', todo: 'muted', in_progress: 'primary', submitted: 'info',
    revision: 'danger', completed: 'success'
  };

  function statusBadge(status) {
    return badge(STATUS_LABELS[status] || status, STATUS_TONES[status] || 'muted');
  }

  function roleBadge(role) {
    return role === 'owner' ? badge('Owner', 'primary') : badge('Karyawan', 'info');
  }

  function activeBadge(active) {
    return active ? badge('Aktif', 'success') : badge('Nonaktif', 'muted');
  }

  function stat(label, value, hint, tone) {
    return '<div class="stat' + (tone ? ' stat--' + tone : '') + '">'
      + '<div class="stat__label">' + esc(label) + '</div>'
      + '<div class="stat__value">' + esc(value) + '</div>'
      + '<div class="stat__hint">' + esc(hint) + '</div>'
      + '</div>';
  }

  function successMessage(label) {
    var text = String(label || '').toLowerCase();
    if (text.indexOf('check-in') !== -1) return 'Check-in berhasil dicatat.';
    if (text.indexOf('check-out') !== -1) return 'Check-out berhasil dicatat.';
    if (text.indexOf('setujui') !== -1) return 'Pengajuan disetujui.';
    if (text.indexOf('tolak') !== -1) return 'Pengajuan ditolak.';
    if (text.indexOf('unduh') !== -1 || text.indexOf('ekspor') !== -1) return 'Laporan siap diunduh.';
    if (text.indexOf('tampilkan') !== -1 || text.indexOf('terapkan') !== -1 || text.indexOf('filter') !== -1) return 'Filter berhasil diterapkan.';
    if (text.indexOf('progres') !== -1 || text.indexOf('simpan') !== -1) return 'Progres berhasil diperbarui.';
    if (text.indexOf('nonaktifkan') !== -1 || text.indexOf('aktifkan') !== -1) return 'Status akun berhasil diperbarui.';
    if (text.indexOf('hapus') !== -1) return 'Data berhasil dihapus.';
    if (text.indexOf('ubah') !== -1) return 'Perubahan berhasil disimpan.';
    if (text.indexOf('tambah') !== -1 || text.indexOf('buat') !== -1 || text.indexOf('ajukan') !== -1 || text.indexOf('kirim') !== -1) return 'Data berhasil disimpan.';
    return 'Aksi berhasil dilakukan.';
  }

  function btn(label, variant, opts) {
    opts = opts || {};
    var cls = 'btn' + (variant ? ' btn--' + variant : '') + (opts.small ? ' btn--sm' : '');
    var attrs = '';
    if (opts.href) {
      attrs += ' href="' + (opts.href === '#' ? '#' : esc(opts.href)) + '"';
      if (opts.href === '#') {
        attrs += ' data-demo="' + esc(opts.demo || successMessage(label)) + '"';
      }
      return '<a class="' + cls + '"' + attrs + '>' + esc(label) + '</a>';
    }
    attrs = ' type="button"';
    if (opts.confirm) {
      attrs += ' data-demo-confirm="' + esc(opts.confirm) + '"';
    } else if (opts.form) {
      attrs += ' data-demo-form="' + esc(opts.form) + '" data-demo-label="' + esc(label) + '"';
    } else if (opts.detail) {
      attrs += ' data-demo-detail="' + esc(opts.detail) + '" data-demo-label="' + esc(label) + '"';
    } else {
      attrs += ' data-demo="' + esc(opts.demo || successMessage(label)) + '"';
    }
    return '<button class="' + cls + '"' + attrs + '>' + esc(label) + '</button>';
  }

  function header(title, subtitle, actions) {
    return '<div class="page__header"><div><h1 class="page__title">' + esc(title)
      + '</h1><p class="page__subtitle">' + esc(subtitle) + '</p></div>'
      + (actions ? '<div class="page__actions">' + actions + '</div>' : '') + '</div>';
  }

  function card(title, body, opts) {
    opts = opts || {};
    var head = '<div class="card__header"><div><div class="card__title">' + esc(title) + '</div>'
      + (opts.subtitle ? '<div class="card__subtitle">' + esc(opts.subtitle) + '</div>' : '')
      + '</div>';
    if (opts.total !== undefined) {
      head += '<div class="small muted">Total: ' + esc(opts.total) + '</div>';
    }
    head += (opts.action || '') + '</div>';
    return '<div class="card">' + head + '<div class="card__body'
      + (opts.flush ? ' card--flush' : '') + '">' + body + '</div></div>';
  }

  function table(headers, rows, compact) {
    var html = '<div class="table-wrap"><table class="table' + (compact ? ' table--compact' : '') + '"><thead><tr>';
    headers.forEach(function (item) { html += '<th' + (item.right ? ' class="text-right"' : '') + '>' + esc(item.label || item) + '</th>'; });
    html += '</tr></thead><tbody>';
    rows.forEach(function (row) {
      html += '<tr>';
      row.forEach(function (cell) { html += '<td' + (cell && cell.right ? ' class="text-right"' : '') + '>' + (cell && cell.html !== undefined ? cell.html : cell) + '</td>'; });
      html += '</tr>';
    });
    return html + '</tbody></table></div>';
  }

  function field(label, control) {
    return '<div class="field"><label class="field__label">' + esc(label) + '</label>' + control + '</div>';
  }

  function input(name, type, value, placeholder) {
    return '<input class="input" name="' + esc(name) + '" type="' + esc(type)
      + '" value="' + esc(value === undefined ? '' : value) + '"'
      + (placeholder ? ' placeholder="' + esc(placeholder) + '"' : '') + '>';
  }

  function select(name, options, selected, auto) {
    var html = '<select class="select" name="' + esc(name) + '"' + (auto ? ' data-auto-submit' : '') + '>';
    options.forEach(function (item) {
      var val = item[0];
      var label = item[1];
      html += '<option value="' + esc(val) + '"'
        + (String(val) === String(selected === undefined ? '' : selected) ? ' selected' : '') + '>'
        + esc(label) + '</option>';
    });
    return html + '</select>';
  }

  function filters(fieldsHtml, actionsHtml) {
    return '<form method="get" class="filters" action="#"><input type="hidden" name="page" value="' + esc(PAGE) + '">'
      + '<div class="filters__grid">' + fieldsHtml
      + '<div class="filters__actions">' + (actionsHtml || '') + '</div></div></form>';
  }

  function filterActions(withTerapkan) {
    return (withTerapkan ? '<button class="btn btn--secondary" type="submit">Terapkan</button>' : '')
      + '<a href="#" class="btn btn--ghost" data-demo="Filter direset." data-reset-filter>Reset</a>';
  }

  function progress(pct) {
    return '<div class="progress"><div class="progress__bar" style="width:' + Number(pct) + '%"></div></div>'
      + '<span class="small muted">' + Number(pct) + '%</span>';
  }

  function timeline(items) {
    return '<div class="timeline">' + items.map(function (item) {
      return '<div class="timeline__item"><div class="timeline__time">' + esc(item.time)
        + ' · ' + esc(item.type) + '</div><div class="timeline__title">' + esc(item.title)
        + '</div><div class="timeline__text">' + esc(item.text) + '</div></div>';
    }).join('') + '</div>';
  }

  function rightCell(html) {
    return { html: html, right: true };
  }

  /* --------------------------- data contoh ----------------------- */

  var EMPLOYEES = [
    { code: 'EMP-001', name: 'Budi Santoso', email: 'budi@umkm.local', position: 'Staf', role: 'employee', active: true, schedules: 4, tasks: 2 },
    { code: 'EMP-002', name: 'Siti Aminah', email: 'siti@umkm.local', position: 'Staf', role: 'employee', active: true, schedules: 5, tasks: 3 },
    { code: 'EMP-003', name: 'Rizky Pratama', email: 'rizky@umkm.local', position: 'Staf', role: 'employee', active: true, schedules: 3, tasks: 1 },
    { code: 'EMP-000', name: 'Owner UMKM', email: 'owner@umkm.local', position: 'Owner', role: 'owner', active: true, schedules: 0, tasks: 0 }
  ];

  var SCHEDULES = [
    { date: TODAY_LABEL, name: 'Budi Santoso', code: 'EMP-001', from: '08:00', to: '17:00', checkIn: '08:02', checkOut: '17:05', status: 'late', note: 'Buka toko' },
    { date: TODAY_LABEL, name: 'Siti Aminah', code: 'EMP-002', from: '09:00', to: '18:00', checkIn: '09:01', checkOut: '18:00', status: 'present', note: 'Belum absen pulang' },
    { date: TODAY_LABEL, name: 'Rizky Pratama', code: 'EMP-003', from: '08:00', to: '16:00', checkIn: '', checkOut: '', status: '', note: 'Jadwal gudang' },
    { date: '25 Nov 2026', name: 'Siti Aminah', code: 'EMP-002', from: '09:00', to: '18:00', checkIn: '09:00', checkOut: '18:02', status: 'present', note: '-' }
  ];

  var ATTENDANCE = [
    { name: 'Budi Santoso', code: 'EMP-001', from: '08:00', to: '17:00', checkIn: '08:02', checkOut: '17:05', status: 'late', late: 2 },
    { name: 'Siti Aminah', code: 'EMP-002', from: '09:00', to: '18:00', checkIn: '09:01', checkOut: '18:00', status: 'present', late: 1 },
    { name: 'Rizky Pratama', code: 'EMP-003', from: '08:00', to: '16:00', checkIn: '08:00', checkOut: '16:02', status: 'present', late: 0 }
  ];

  var CORRECTIONS = [
    { date: '24 Nov 2026', name: 'Siti Aminah', code: 'EMP-002', requestedIn: '09:00', requestedOut: '18:00', actualIn: '09:10', actualOut: '17:58', reason: 'Lupa menandai pulang tepat waktu.', status: 'pending', review: '' },
    { date: '23 Nov 2026', name: 'Rizky Pratama', code: 'EMP-003', requestedIn: '08:00', requestedOut: '16:00', actualIn: '08:12', actualOut: '16:10', reason: 'HP lowbat saat check-in.', status: 'pending', review: '' },
    { date: '20 Nov 2026', name: 'Siti Aminah', code: 'EMP-002', requestedIn: '09:00', requestedOut: '18:00', actualIn: '09:05', actualOut: '18:00', reason: 'Jadwal berubah mendadak.', status: 'approved', review: 'Disetujui owner' }
  ];

  var TASKS = [
    { id: 1, title: 'Rekap penjualan bulan ini', desc: 'Rekap transaksi dan stok akhir bulan.', employee: 'Budi Santoso', code: 'EMP-001', deadline: '30 Nov 2026', progress: 60, status: 'in_progress', evidence: 1 },
    { id: 2, title: 'Input stok gudang', desc: 'Perbarui data stok di sistem.', employee: 'Rizky Pratama', code: 'EMP-003', deadline: '28 Nov 2026', progress: 100, status: 'submitted', evidence: 2 },
    { id: 3, title: 'Foto display toko', desc: 'Ambil foto display dan unggah bukti.', employee: 'Siti Aminah', code: 'EMP-002', deadline: '02 Des 2026', progress: 0, status: 'todo', evidence: 0 }
  ];

  /* --------------------------- shell ----------------------------- */

  var NAV_OWNER = [
    ['dashboard', 'Dashboard', 'dashboard.html', '⌂'],
    ['employees', 'Data Karyawan', 'employees.html', '☰'],
    ['schedules', 'Jadwal Kerja', 'schedules.html', '▦'],
    ['attendance', 'Absensi', 'attendance.html', '✓'],
    ['history', 'Riwayat Absensi', 'history.html', '◷'],
    ['corrections', 'Koreksi Absensi', 'corrections.html', '↻'],
    ['tasks', 'Tugas & Progres', 'tasks.html', '☑'],
    ['reports', 'Laporan', 'reports.html', '▤']
  ];
  var NAV_EMPLOYEE = [
    ['dashboard', 'Dashboard', 'dashboard.html', '⌂'],
    ['schedules', 'Jadwal Kerja', 'schedules.html', '▦'],
    ['attendance', 'Absensi', 'attendance.html', '✓'],
    ['history', 'Riwayat Absensi', 'history.html', '◷'],
    ['corrections', 'Koreksi Absensi', 'corrections.html', '↻'],
    ['tasks', 'Tugas Saya', 'tasks.html', '☑']
  ];

  var navItems = owner ? NAV_OWNER : NAV_EMPLOYEE;
  var navHtml = '<div class="nav__section">MENU UTAMA</div>';
  navItems.forEach(function (item) {
    navHtml += '<a class="nav__link' + (PAGE === item[0] ? ' is-active' : '') + '" href="' + item[2] + '">'
      + '<span class="nav__icon" aria-hidden="true">' + item[3] + '</span><span>' + esc(item[1]) + '</span></a>';
  });
  navHtml += '<div class="nav__section">LAINNYA</div>'
    + '<a class="nav__link" href="wireframe.html"><span class="nav__icon" aria-hidden="true">▧</span>'
    + '<span>Wireframe Preview</span></a>';

  var initial = esc((user.name || user.email || 'U').charAt(0).toUpperCase());

  var shell = ''
    + '<div class="app">'
    + '<aside class="sidebar" id="sidebar">'
    +   '<a class="sidebar__brand" href="dashboard.html">'
    +     '<span class="sidebar__brand-mark">UM</span><span>Monitoring Absensi UMKM</span></a>'
    +   '<nav class="sidebar__nav" aria-label="Navigasi utama">' + navHtml + '</nav>'
    +   '<div class="sidebar__footer"><div class="small">Login sebagai</div>'
    +     '<strong>' + esc(user.name) + '</strong>'
    +     '<div class="small muted">' + (owner ? 'Owner UMKM' : 'Karyawan') + '</div></div>'
    + '</aside>'
    + '<div class="sidebar-overlay" data-nav-close aria-hidden="true"></div>'
    + '<div class="main">'
    +   '<header class="topbar">'
    +     '<button class="topbar__menu" type="button" aria-label="Buka menu navigasi">☰</button>'
    +     '<div><div class="topbar__title">' + esc(PAGE === 'dashboard' ? 'Dashboard' : 'Monitoring Absensi') + '</div>'
    +       '<div class="topbar__subtitle">Monitoring Absensi UMKM</div></div>'
    +     '<div class="topbar__actions">'
    +       '<button class="btn btn--ghost btn--sm wf-toggle' + (lofi ? '' : ' wf-toggle--off') + '" id="lofiToggle" type="button">Lo-Fi: '
    +         (lofi ? 'ON' : 'OFF') + '</button>'
    +       '<div class="user-chip"><span class="user-chip__avatar">' + initial + '</span>'
    +         '<span class="user-chip__meta"><span class="user-chip__name">' + esc(user.name)
    +         '</span><span class="user-chip__role">' + (owner ? 'Owner' : 'Karyawan')
    +         '</span></span></div>'
    +       '<button type="button" class="btn btn--ghost btn--sm" id="logoutBtn">Keluar</button>'
    +     '</div>'
    +   '</header>'
    +   '<main class="content"><div id="pageContent"></div></main>'
    +   '<footer class="footer"><span>© 2026 Monitoring Absensi UMKM</span>'
    +     '<span>Monitoring kehadiran &amp; progres kerja</span></footer>'
    + '</div>'
    + '</div>';

  document.getElementById('app').innerHTML = shell;

  /* --------------------------- halaman --------------------------- */

  var content = '';

  if (PAGE === 'dashboard' && owner) {
    content = header('Selamat datang, ' + user.name + ' 👋', 'Ringkasan operasional tim hari ini, ' + TODAY_LABEL + '.',
        btn('Lihat laporan', 'secondary', { href: 'reports.html' })
      + btn('Buat tugas', 'primary', { form: 'task' }))
      + '<div class="stats">'
      + stat('Jadwal hari ini', 3, 'Total jadwal kerja', 'primary')
      + stat('Sudah hadir', 18, 'Check-in tercatat', 'success')
      + stat('Terlambat', 3, 'Perlu perhatian', 'warning')
      + stat('Koreksi menunggu', 2, 'Menunggu persetujuan', 'danger')
      + '</div><div class="stats">'
      + stat('Tugas berjalan', 4, 'Belum / sedang dikerjakan')
      + stat('Menunggu persetujuan', 2, 'Sudah diajukan selesai', 'primary')
      + stat('Tugas selesai', 7, 'Disetujui owner', 'success')
      + stat('Lewat tenggat', 1, 'Belum selesai', 'danger')
      + '</div>'
      + '<div class="grid grid--2">'
      + card('Absensi terbaru', table(['Karyawan', 'Masuk', 'Pulang', 'Status'], [
          ['<strong>Budi Santoso</strong><div class="small muted">EMP-001</div>', '08:02', '17:05', statusBadge('late')],
          ['<strong>Siti Aminah</strong><div class="small muted">EMP-002</div>', '09:01', '18:00', statusBadge('present')],
          ['<strong>Rizky Pratama</strong><div class="small muted">EMP-003</div>', '08:00', '16:02', statusBadge('present')]
        ], true), { subtitle: 'Catatan check-in dan check-out karyawan', flush: true,
          action: btn('Lihat semua', 'ghost btn--sm', { href: 'history.html' }) })
      + card('Aktivitas terbaru', timeline([
          { time: '10:20', type: 'tugas', title: 'Bukti pekerjaan diunggah', text: 'Rizky mengunggah 2 file bukti untuk Input stok gudang.' },
          { time: '09:05', type: 'tugas', title: 'Rekap penjualan 60%', text: 'Budi memperbarui progres tugas.' },
          { time: '08:35', type: 'koreksi', title: 'Pengajuan koreksi · Siti', text: 'Menunggu persetujuan owner.' },
          { time: '08:12', type: 'absensi', title: 'Budi check-in', text: 'Tercatat pukul 08:02 (telat 2 menit).' }
        ]), { subtitle: 'Absensi, koreksi, dan progres tugas' })
      + '</div>';
  } else if (PAGE === 'dashboard') {
    content = header('Halo, ' + user.name + ' 👋', 'Jadwal dan tugas Anda hari ini, ' + TODAY_LABEL + '.',
        btn('Absensi saya', 'secondary', { href: 'attendance.html' })
      + btn('Lihat tugas', 'primary', { href: 'tasks.html' }))
      + card('Jadwal kerja hari ini',
          '<div class="grid grid--3">'
        + '<div class="kv__item"><span class="kv__label">Jam masuk</span><span class="kv__value">09:00</span></div>'
        + '<div class="kv__item"><span class="kv__label">Jam pulang</span><span class="kv__value">18:00</span></div>'
        + '<div class="kv__item"><span class="kv__label">Check-in Anda</span><span class="kv__value">-</span></div></div>'
        + '<div class="divider"></div>'
        + btn('✓ Check-in sekarang', 'primary', { confirm: 'Catat waktu check-in sekarang?' }),
          { subtitle: 'Lakukan check-in dan check-out sesuai jadwal', action: badge('Berlaku', 'success') })
      + '<div style="height:18px"></div>'
      + '<div class="stats">'
      + stat('Tugas berjalan', 2, 'Sedang dikerjakan', 'primary')
      + stat('Menunggu review', 1, 'Diajukan selesai', 'warning')
      + stat('Tugas selesai', 1, 'Disetujui owner', 'success')
      + stat('Lewat tenggat', 0, 'Perlu diselesaikan', 'danger')
      + '</div>'
      + '<div class="grid grid--2">'
      + card('Tugas terdekat', table(['Tugas', 'Deadline', 'Progres', 'Status'], [
          ['<a href="#" data-demo-detail="task" data-demo-label="Rekap stok & penjualan"><strong>Rekap stok & penjualan</strong></a>', '30 Nov 2026', { html: progress(60) }, statusBadge('in_progress')],
          ['<a href="#" data-demo-detail="task" data-demo-label="Foto display toko"><strong>Foto display toko</strong></a>', '02 Des 2026', { html: progress(0) }, statusBadge('todo')]
        ], true), { subtitle: 'Perbarui progres dan unggah bukti', flush: true,
          action: btn('Semua tugas', 'ghost btn--sm', { href: 'tasks.html' }) })
      + card('Koreksi absensi', '<p class="muted">Jika waktu check-in/check-out Anda tercatat keliru, ajukan koreksi beserta alasannya.</p>'
          + '<div class="alert alert--warning">Ada 1 pengajuan koreksi yang masih menunggu persetujuan owner.</div>'
          + '<div class="row">' + btn('Riwayat pengajuan', 'secondary', { href: 'corrections.html' })
          + btn('Ajukan koreksi', 'primary', { form: 'correction' }) + '</div>',
          { subtitle: 'Pengajuan perbaikan data kehadiran' })
      + '</div>';
  } else if (PAGE === 'employees') {
    content = header('Data Karyawan', 'Kelola akun, jabatan, peran, dan status aktif karyawan.',
        btn('+ Tambah karyawan', 'primary', { form: 'employee' }))
      + card('Daftar akun',
          filters(field('Cari', input('q', 'text', '', 'Nama, ID, email, atau jabatan'))
            + field('Role', select('role', [['', 'Semua role'], ['owner', 'Owner'], ['employee', 'Karyawan']], '', true)),
            filterActions(true))
        + table(['ID', 'Nama', 'Email / Username', 'Jabatan', 'Role', 'Status', 'Data', { label: 'Aksi', right: true }],
          EMPLOYEES.map(function (emp) {
            return ['<strong>' + esc(emp.code) + '</strong>', esc(emp.name), '<span class="small">' + esc(emp.email) + '</span>',
              esc(emp.position), roleBadge(emp.role), activeBadge(emp.active),
              '<span class="small muted">' + emp.schedules + ' jadwal · ' + emp.tasks + ' tugas</span>',
              rightCell('<div class="btn-group nowrap">'
                + btn('Ubah', 'ghost btn--sm', { form: 'employee', demo: 'Ubah' })
                + btn(emp.active ? 'Nonaktifkan' : 'Aktifkan', 'ghost btn--sm', { confirm: (emp.active ? 'Nonaktifkan' : 'Aktifkan') + ' akun ' + emp.name + '?' })
                + btn('Hapus', 'danger btn--sm', { confirm: 'Hapus permanen akun ' + emp.name + '?' })
                + '</div>')];
          })), { total: EMPLOYEES.length + ' akun' });
  } else if (PAGE === 'schedules') {
    var empOptions = [['0', 'Semua karyawan']].concat(EMPLOYEES.filter(function (e) { return e.role === 'employee'; }).map(function (e) {
      return [e.code, e.name + ' (' + e.code + ')'];
    }));
    content = header('Jadwal Kerja', owner ? 'Atur jadwal masuk dan pulang karyawan.' : 'Lihat jadwal kerja Anda dan lakukan absensi tepat waktu.',
        owner ? btn('+ Tambah jadwal', 'primary', { form: 'schedule' }) : '')
      + card('Daftar jadwal',
          filters((owner ? field('Karyawan', select('employee_id', empOptions, '0', true)) : '')
            + field('Dari tanggal', input('from', 'date', '2026-11-01'))
            + field('Sampai tanggal', input('to', 'date', '2026-11-30')), filterActions(false))
        + table(['Tanggal', 'Karyawan', 'Jam masuk', 'Jam pulang', 'Absensi', 'Catatan'].concat(owner ? [{ label: 'Aksi', right: true }] : []),
          SCHEDULES.filter(function (s) { return owner || s.code === 'EMP-002'; }).map(function (s) {
            var row = ['<strong>' + esc(s.date) + '</strong>',
              esc(s.name) + '<div class="small muted">' + esc(s.code) + '</div>',
              esc(s.from), esc(s.to),
              s.checkIn ? statusBadge(s.status) + '<div class="small muted">' + esc(s.checkIn) + ' s.d. ' + esc(s.checkOut) + '</div>' : badge('Belum absen', 'muted'),
              '<span class="small muted">' + esc(s.note) + '</span>'];
            if (owner) {
              row.push(rightCell('<div class="btn-group nowrap">'
                + btn('Ubah', 'ghost btn--sm', { form: 'schedule' })
                + btn('Hapus', 'danger btn--sm', { confirm: 'Hapus jadwal ' + s.date + ' untuk ' + s.name + '?' })
                + '</div>'));
            }
            return row;
          })), { total: (owner ? SCHEDULES.length : 2) + ' jadwal' });
  } else if (PAGE === 'attendance' && owner) {
    content = header('Absensi Hari Ini', 'Pantau kehadiran seluruh karyawan pada ' + TODAY_LABEL + '.',
        btn('Lihat riwayat', 'secondary', { href: 'history.html' })
      + btn('Koreksi menunggu', 'primary', { href: 'corrections.html' }))
      + card('Catatan kehadiran', table(['Karyawan', 'Jadwal', 'Check-in', 'Check-out', 'Status', 'Keterangan'],
          ATTENDANCE.map(function (row) {
            return ['<strong>' + esc(row.name) + '</strong><div class="small muted">' + esc(row.code) + '</div>',
              '<span class="small">' + esc(row.from) + ' - ' + esc(row.to) + '</span>',
              esc(row.checkIn), esc(row.checkOut), statusBadge(row.status),
              row.late > 0 ? '<span class="danger-text">Terlambat ' + row.late + ' menit</span>' : '<span class="muted">Tepat waktu</span>'];
          })), { total: ATTENDANCE.length + ' catatan' });
  } else if (PAGE === 'attendance') {
    var mySchedules = [
      { date: TODAY_LABEL, from: '09:00', to: '18:00', checkIn: '', checkOut: '', status: '', today: true, done: false },
      { date: '25 Nov 2026', from: '09:00', to: '18:00', checkIn: '09:00', checkOut: '18:02', status: 'present', today: false, done: true },
      { date: '24 Nov 2026', from: '09:00', to: '18:00', checkIn: '09:05', checkOut: '18:00', status: 'late', today: false, done: true },
      { date: '27 Nov 2026', from: '09:00', to: '18:00', checkIn: '', checkOut: '', status: '', today: false, done: false }
    ];
    content = header('Absensi Saya', 'Check-in dan check-out berdasarkan jadwal kerja Anda.',
        btn('Riwayat absensi', 'secondary', { href: 'history.html' })
      + btn('Ajukan koreksi', 'primary', { form: 'correction' }))
      + card('Jadwal 7 hari terakhir dan mendatang', table(
          ['Tanggal', 'Jam kerja', 'Check-in', 'Check-out', 'Status', { label: 'Aksi', right: true }],
          mySchedules.map(function (s) {
            var aksi = '-';
            if (s.today) {
              aksi = btn('Check-in', 'primary btn--sm', { confirm: 'Catat check-in sekarang?' });
            } else if (s.done) {
              aksi = badge('Selesai', 'success');
            }
            return ['<strong>' + esc(s.date) + '</strong>' + (s.today ? '<div><span class="badge badge--primary">Hari ini</span></div>' : ''),
              '<span class="small">' + esc(s.from) + ' - ' + esc(s.to) + '</span>',
              esc(s.checkIn || '-'), esc(s.checkOut || '-'),
              s.checkIn ? statusBadge(s.status) : badge('Belum absen', 'muted'), rightCell(aksi)];
          })), { subtitle: 'Tombol absensi hanya aktif pada tanggal jadwal berlaku' });
  } else if (PAGE === 'history') {
    content = header('Riwayat Absensi', 'Lihat catatan kehadiran berdasarkan periode tertentu.',
        btn('↓ Ekspor CSV', 'secondary', { confirm: 'Unduh riwayat absensi periode ini?' }))
      + card('Riwayat kehadiran',
          filters(field('Karyawan', select('employee_id', [['0', 'Semua karyawan']].concat(EMPLOYEES.map(function (e) { return [e.code, e.name]; })), '0', true))
            + field('Dari tanggal', input('from', 'date', '2026-11-01'))
            + field('Sampai tanggal', input('to', 'date', '2026-11-30')), filterActions(true))
        + table(['ID', 'Tanggal', 'Karyawan', 'Jabatan', 'Masuk', 'Pulang', 'Status', 'Telat'],
          [1, 2, 3, 4, 5].map(function (id, index) {
            var row = ATTENDANCE[index % ATTENDANCE.length];
            return ['#' + id, TODAY_LABEL,
              esc(row.name) + '<div class="small muted">' + esc(row.code) + '</div>', 'Staf',
              esc(row.checkIn), esc(row.checkOut), statusBadge(row.status), '<span class="small">' + row.late + ' mnt</span>'];
          })), { total: '5 baris' });
  } else if (PAGE === 'corrections') {
    content = header('Koreksi Absensi', owner ? 'Setujui atau tolak pengajuan koreksi karyawan.' : 'Pantau status pengajuan koreksi absensi Anda.',
        owner ? '' : btn('+ Ajukan koreksi', 'primary', { form: 'correction' }))
      + card('Daftar pengajuan',
          filters(field('Status', select('status', [['', 'Semua status'], ['pending', 'Menunggu'], ['approved', 'Disetujui'], ['rejected', 'Ditolak']], '', true)), filterActions(false))
        + table(['Tanggal'].concat(owner ? ['Karyawan'] : []).concat(['Jam diajukan', 'Catatan asli', 'Alasan', 'Status', { label: 'Aksi', right: true }]),
          CORRECTIONS.map(function (c) {
            var row = [esc(c.date)];
            if (owner) row.push(esc(c.name) + '<div class="small muted">' + esc(c.code) + '</div>');
            row.push('<span class="small">Masuk: ' + esc(c.requestedIn) + '<br>Pulang: ' + esc(c.requestedOut) + '</span>',
              '<span class="small">' + esc(c.actualIn) + '<br>' + esc(c.actualOut) + '</span>',
              '<span class="small">' + esc(c.reason) + '</span>', statusBadge(c.status));
            if (owner && c.status === 'pending') {
              row.push(rightCell('<div class="stack"><input class="input" placeholder="Catatan (opsional)">'
                + btn('Setujui', 'primary btn--sm', { confirm: 'Setujui koreksi ini dan perbarui data absensi?' })
                + '<input class="input" placeholder="Alasan penolakan">'
                + btn('Tolak', 'danger btn--sm', { confirm: 'Tolak pengajuan koreksi ini?' })
                + '</div>'));
            } else {
              row.push(rightCell('<span class="small muted">' + esc(c.review || '-') + '</span>'));
            }
            return row;
          })), { total: CORRECTIONS.length + ' pengajuan' });
  } else if (PAGE === 'tasks') {
    content = header('Tugas & Progres', owner ? 'Bagi tugas dan pantau progres karyawan.' : 'Tugas yang ditugaskan kepada Anda beserta progresnya.',
        owner ? btn('+ Buat tugas', 'primary', { form: 'task' }) : '')
      + card('Daftar tugas',
          filters(field('Status', select('status', [['', 'Semua status'], ['todo', 'Belum dimulai'], ['in_progress', 'Berjalan'], ['submitted', 'Menunggu persetujuan'], ['revision', 'Perlu revisi'], ['completed', 'Selesai']], '', true)), filterActions(false))
        + table(['Tugas'].concat(owner ? ['Karyawan'] : []).concat(['Deadline', 'Progres', 'Status', 'Bukti', { label: 'Aksi', right: true }]),
          TASKS.filter(function (t) { return owner || t.employee === 'Siti Aminah'; }).map(function (t) {
            var row = ['<a href="#" data-demo-detail="task" data-demo-label="' + esc(t.title) + '"><strong>' + esc(t.title) + '</strong></a>'
              + '<div class="small muted">' + esc(t.desc) + '</div>'];
            if (owner) row.push(esc(t.employee) + '<div class="small muted">' + esc(t.code) + '</div>');
            row.push('<span class="small">' + esc(t.deadline) + '</span>', { html: progress(t.progress) },
              statusBadge(t.status), '<span class="small">' + t.evidence + ' file</span>');
            row.push(rightCell('<div class="btn-group nowrap">'
              + btn('Detail', 'ghost btn--sm', { detail: 'task', demoLabel: t.title })
              + (owner ? btn('Ubah', 'ghost btn--sm', { form: 'task' })
                + btn('Hapus', 'danger btn--sm', { confirm: 'Hapus tugas «' + t.title + '» beserta buktinya?' }) : '')
              + '</div>'));
            return row;
          })), { total: (owner ? TASKS.length : 2) + ' tugas' });
  } else if (PAGE === 'task') {
    var task = TASKS[0];
    content = header('Detail Tugas', 'Perbarui progres dan kelola bukti pekerjaan.',
        btn('Kembali ke tugas', 'secondary', { href: 'tasks.html' }))
      + card(task.title, '<div class="grid grid--2">'
          + '<div class="kv__item"><span class="kv__label">Karyawan</span><span class="kv__value">' + esc(task.employee) + '</span></div>'
          + '<div class="kv__item"><span class="kv__label">Deadline</span><span class="kv__value">' + esc(task.deadline) + '</span></div>'
          + '<div class="kv__item"><span class="kv__label">Status</span><span class="kv__value">' + statusBadge(task.status) + '</span></div>'
          + '<div class="kv__item"><span class="kv__label">Bukti</span><span class="kv__value">' + task.evidence + ' file</span></div></div>'
          + '<div class="divider"></div><p class="muted">' + esc(task.desc) + '</p>',
          { subtitle: 'Informasi tugas dan bukti pekerjaan' })
      + '<div style="height:18px"></div>'
      + card('Perbarui progres', field('Progres (%)', input('progress', 'number', task.progress))
          + '<div class="divider"></div>'
          + '<div class="row">' + btn('Simpan progres', 'primary', { demo: 'Progres berhasil diperbarui.' })
          + btn('Unggah bukti', 'secondary', { demo: 'Bukti berhasil diunggah.' }) + '</div>',
          { subtitle: 'Perubahan hanya sebagai contoh tampilan' })
      + (owner ? '<div style="height:18px"></div>' + card('Persetujuan owner',
          '<div class="row">' + btn('Setujui', 'primary', { confirm: 'Setujui tugas ini?' })
        + btn('Minta revisi', 'danger', { confirm: 'Minta revisi tugas ini?' }) + '</div>',
          { subtitle: 'Tinjau bukti yang sudah dikirim' }) : '');
  } else if (PAGE === 'reports') {
    content = header('Laporan & Ekspor', 'Unduh data absensi atau progres tugas dalam format PDF dan Excel (CSV).')
      + card('Filter laporan',
          filters(field('Jenis laporan', select('type', [['attendance', 'Absensi'], ['tasks', 'Tugas / Progres']], 'attendance', true))
            + field('Dari tanggal', input('from', 'date', '2026-11-01'))
            + field('Sampai tanggal', input('to', 'date', '2026-11-30')),
            '<button class="btn btn--secondary" type="submit">Tampilkan</button>')
          + '<div class="divider"></div>'
          + '<div class="row">' + field('Format unduhan', select('format', [['csv', 'Excel (CSV)'], ['pdf', 'PDF']], 'csv'))
          + '<div class="field" style="align-self:end">'
          + btn('↓ Unduh laporan', 'primary', { confirm: 'Unduh laporan absensi periode ini?' })
          + '</div></div>')
      + '<div style="height:18px"></div>'
      + card('Pratinjau data', table(['ID', 'Tanggal', 'Karyawan', 'Jabatan', 'Masuk', 'Pulang', 'Status', 'Telat'],
          ATTENDANCE.map(function (row, index) {
            return ['#' + (index + 1), TODAY_LABEL,
              esc(row.name) + '<div class="small muted">' + esc(row.code) + '</div>', 'Staf',
              esc(row.checkIn), esc(row.checkOut), statusBadge(row.status), '<span class="small">' + row.late + ' mnt</span>'];
          })), { subtitle: 'Periode 01 Nov 2026 s.d. 30 Nov 2026 · 3 baris' });
  } else {
    content = header('Halaman tidak tersedia', 'Kembali ke dashboard.')
      + btn('Ke dashboard', 'primary', { href: 'dashboard.html' });
  }

  document.getElementById('pageContent').innerHTML = content;

  /* --------------------- aksi pura-pura / demo --------------------- */

  var toastTimer = null;

  function showToast(message) {
    var toast = document.getElementById('demoToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'demoToast';
      toast.className = 'demo-toast';
      document.body.appendChild(toast);
    }
    toast.innerHTML = '<div class="alert alert--success"><strong>Berhasil.</strong> ' + esc(message) + '</div>';
    toast.classList.add('is-visible');
    if (toastTimer) {
      window.clearTimeout(toastTimer);
    }
    toastTimer = window.setTimeout(function () {
      toast.classList.remove('is-visible');
    }, 3200);
  }

  var activeModal = null;

  function closeModal() {
    if (activeModal) {
      activeModal.hidden = true;
      document.body.classList.remove('modal-open');
    }
  }

  function openModal(opts) {
    closeModal();
    var modal = document.createElement('div');
    modal.className = 'app-modal';
    modal.hidden = true;
    modal.innerHTML = ''
      + '<div class="app-modal__backdrop" data-demo-close></div>'
      + '<div class="app-modal__card" role="dialog" aria-modal="true">'
      +   '<div class="app-modal__icon" aria-hidden="true">?</div>'
      +   '<h2 class="app-modal__title"></h2>'
      +   '<div class="app-modal__text"></div>'
      +   '<div class="app-modal__body"></div>'
      +   '<div class="app-modal__actions">'
      +     '<button type="button" class="btn btn--secondary" data-demo-close>Batal</button>'
      +     '<button type="button" class="btn btn--primary" data-demo-yes>Ya, lanjutkan</button>'
      +   '</div>'
      + '</div>';
    document.body.appendChild(modal);

    modal.querySelector('.app-modal__title').textContent = opts.title || 'Konfirmasi';
    modal.querySelector('.app-modal__text').textContent = opts.text || '';
    modal.querySelector('.app-modal__body').innerHTML = opts.body || '';
    if (opts.yesLabel) {
      modal.querySelector('[data-demo-yes]').textContent = opts.yesLabel;
    }
    modal.querySelector('[data-demo-yes]').addEventListener('click', function () {
      var callback = opts.onYes;
      closeModal();
      if (typeof callback === 'function') {
        callback();
      }
    });
    Array.prototype.forEach.call(modal.querySelectorAll('[data-demo-close]'), function (el) {
      el.addEventListener('click', closeModal);
    });

    activeModal = modal;
    modal.hidden = false;
    document.body.classList.add('modal-open');
    var yes = modal.querySelector('[data-demo-yes]');
    if (yes && yes.focus) {
      yes.focus();
    }
  }

  document.addEventListener('keydown', function (event) {
    var key = event && (event.key || event.keyCode);
    if ((key === 'Escape' || key === 'Esc' || key === 27) && activeModal && !activeModal.hidden) {
      closeModal();
    }
  });

  function openConfirm(message, onYes, yesLabel) {
    openModal({
      title: 'Konfirmasi',
      text: message,
      yesLabel: yesLabel || 'Ya, lanjutkan',
      onYes: onYes
    });
  }

  function formFields(kind) {
    if (kind === 'employee') {
      return field('Nama', input('name', 'text', '', 'Nama karyawan'))
        + field('Email / Username', input('email', 'email', '', 'nama@umkm.local'))
        + field('Jabatan', input('position', 'text', '', 'Staf'))
        + field('Role', select('role', [['employee', 'Karyawan'], ['owner', 'Owner']], 'employee'))
        + field('Status', select('status', [['active', 'Aktif'], ['inactive', 'Nonaktif']], 'active'));
    }
    if (kind === 'schedule') {
      return field('Karyawan', select('employee', [['EMP-001', 'Budi Santoso'], ['EMP-002', 'Siti Aminah'], ['EMP-003', 'Rizky Pratama']], 'EMP-002'))
        + field('Tanggal', input('date', 'date', '2026-11-27'))
        + field('Jam masuk', input('from', 'time', '08:00'))
        + field('Jam pulang', input('to', 'time', '17:00'))
        + field('Catatan', input('note', 'text', '', 'Catatan opsional'));
    }
    if (kind === 'correction') {
      return field('Tanggal', input('date', 'date', '2026-11-24'))
        + (owner ? field('Karyawan', select('employee', [['EMP-002', 'Siti Aminah'], ['EMP-003', 'Rizky Pratama']], 'EMP-002')) : '')
        + field('Check-in diajukan', input('requested_in', 'time', '09:00'))
        + field('Check-out diajukan', input('requested_out', 'time', '18:00'))
        + field('Alasan', input('reason', 'text', '', 'Alasan koreksi'));
    }
    return field('Judul', input('title', 'text', '', 'Judul tugas'))
      + field('Deskripsi', input('description', 'text', '', 'Deskripsi singkat'))
      + field('Karyawan', select('employee', [['EMP-001', 'Budi Santoso'], ['EMP-002', 'Siti Aminah'], ['EMP-003', 'Rizky Pratama']], 'EMP-002'))
      + field('Deadline', input('deadline', 'date', '2026-11-30'));
  }

  function openFormModal(kind, label) {
    openModal({
      title: label,
      text: 'Isi data berikut untuk mencoba tampilan formulir.',
      body: formFields(kind),
      yesLabel: 'Simpan',
      onYes: function () {
        showToast(successMessage(label));
      }
    });
  }

  function openDetailModal(label) {
    openModal({
      title: label || 'Detail tugas',
      text: 'Rekap stok & penjualan · deadline 30 Nov 2026 · progres 60%.',
      body: field('Progres (%)', input('progress', 'number', 60))
        + field('Catatan', input('note', 'text', '', 'Tambahkan catatan progres')),
      yesLabel: 'Simpan progres',
      onYes: function () {
        showToast('Progres berhasil diperbarui.');
      }
    });
  }

  document.addEventListener('click', function (event) {
    var el = event.target && event.target.closest
      ? event.target.closest('[data-demo], [data-demo-confirm], [data-demo-form], [data-demo-detail]')
      : null;
    if (!el) {
      return;
    }

    /* Tombol submit pada filter ditangani lewat event submit di bawah. */
    if (el.tagName === 'BUTTON' && el.type === 'submit' && el.closest('.filters')) {
      return;
    }

    if (el.hasAttribute('data-demo-confirm')) {
      event.preventDefault();
      openConfirm(el.getAttribute('data-demo-confirm'), function () {
        showToast(successMessage(el.textContent));
      });
      return;
    }

    if (el.hasAttribute('data-demo-form')) {
      event.preventDefault();
      openFormModal(el.getAttribute('data-demo-form'), el.textContent);
      return;
    }

    if (el.hasAttribute('data-demo-detail')) {
      event.preventDefault();
      openDetailModal(el.getAttribute('data-demo-label') || el.textContent);
      return;
    }

    event.preventDefault();
    if (el.hasAttribute('data-reset-filter')) {
      var cardEl = el.closest('.card');
      var form = cardEl ? cardEl.querySelector('.filters') : null;
      if (form && form.reset) {
        form.reset();
      }
    }
    showToast(el.getAttribute('data-demo') || successMessage(el.textContent));
  });

  document.addEventListener('submit', function (event) {
    if (event.target.classList && event.target.classList.contains('filters')) {
      event.preventDefault();
      showToast('Filter berhasil diterapkan.');
    }
  });

  /* Lo-Fi toggle. */
  var toggle = document.getElementById('lofiToggle');
  if (toggle) {
    toggle.addEventListener('click', function () {
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
    logoutBtn.addEventListener('click', function () {
      openConfirm('Yakin ingin keluar dari akun ini?', function () {
        try {
          sessionStorage.removeItem('demoUser');
        } catch (err) {
          /* Abaikan. */
        }
        window.location.href = 'index.html';
      }, 'Ya, keluar');
    });
  }
})();
