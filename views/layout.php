<?php
$user = currentUser();
$role = $user['role'] ?? '';
$initial = mb_strtoupper(mb_substr((string) ($user['name'] ?? 'U'), 0, 1));
$wf = wireframeMode();
$navItems = [
    ['dashboard', 'Dashboard', '⌂', ['owner', 'employee']],
    ['employees', 'Data Karyawan', '☰', ['owner']],
    ['schedules', 'Jadwal Kerja', '▦', ['owner', 'employee']],
    ['attendance', 'Absensi', '✓', ['owner', 'employee']],
    ['history', 'Riwayat Absensi', '◷', ['owner', 'employee']],
    ['corrections', 'Koreksi Absensi', '↻', ['owner', 'employee']],
    ['tasks', 'Tugas & Progres', '☑', ['owner', 'employee']],
    ['reports', 'Laporan', '▤', ['owner']],
    ['wireframe', 'Wireframe Preview', '▧', ['owner', 'employee']],
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistem monitoring absensi dan tugas karyawan UMKM">
    <title><?= e($title) ?> · <?= e(env('APP_NAME', 'Monitoring Absensi UMKM')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <?php if ($wf) : ?><link rel="stylesheet" href="<?= e(asset('css/wireframe.css')) ?>"><?php endif; ?>
</head>
<body<?= $wf ? ' class="wireframe"' : '' ?>>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a class="sidebar__brand" href="<?= e(url('dashboard')) ?>">
            <span class="sidebar__brand-mark">UM</span>
            <span><?= e(env('APP_NAME', 'Monitoring Absensi UMKM')) ?></span>
        </a>
        <nav class="sidebar__nav" aria-label="Navigasi utama">
            <div class="nav__section">MENU UTAMA</div>
            <?php foreach ($navItems as [$page, $label, $icon, $allowedRoles]) : ?>
                <?php if (!in_array($role, $allowedRoles, true)) { continue; } ?>
                <a class="nav__link <?= $activePage === $page ? 'is-active' : '' ?>"
                   href="<?= e(url($page)) ?>"
                   <?= $activePage === $page ? 'aria-current="page"' : '' ?>>
                    <span class="nav__icon" aria-hidden="true"><?= $icon ?></span>
                    <span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar__footer">
            <div class="small">Login sebagai</div>
            <strong><?= e($user['name'] ?? '-') ?></strong>
            <div class="small muted"><?= e($role === 'owner' ? 'Owner UMKM' : 'Karyawan') ?></div>
        </div>
    </aside>
    <div class="sidebar-overlay" data-nav-close aria-hidden="true"></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="topbar__menu" aria-label="Buka menu navigasi" aria-expanded="false">☰</button>
            <div>
                <div class="topbar__title"><?= e($title) ?></div>
                <div class="topbar__subtitle"><?= e(env('APP_NAME', 'Monitoring Absensi UMKM')) ?></div>
            </div>
            <div class="topbar__actions">
                <a class="btn btn--ghost btn--sm wf-toggle <?= wireframeEnabled() ? '' : 'wf-toggle--off' ?>"
                   href="<?= e(wireframeToggleUrl()) ?>"
                   title="Alihkan tampilan Lo-Fi Wireframe">
                    Lo-Fi: <?= wireframeEnabled() ? 'ON' : 'OFF' ?>
                </a>
                <div class="user-chip">
                    <span class="user-chip__avatar"><?= e($initial) ?></span>
                    <span class="user-chip__meta">
                        <span class="user-chip__name"><?= e($user['name'] ?? '-') ?></span>
                        <span class="user-chip__role"><?= e($role === 'owner' ? 'Owner' : 'Karyawan') ?></span>
                    </span>
                </div>
                <?php if ($user) : ?>
                    <form method="post" action="<?= e(url('login', ['action' => 'logout'])) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Yakin ingin keluar dari akun ini?">Keluar</button>
                    </form>
                <?php endif; ?>
            </div>
        </header>

        <main class="content">
            <?php foreach ($flashes as $flash) : ?>
                <div class="alert alert--<?= e($flash['type']) ?>" data-autodismiss role="status">
                    <?= e($flash['message']) ?>
                </div>
            <?php endforeach; ?>
            <?= $content ?>
        </main>

        <footer class="footer">
            <span>© <?= date('Y') ?> <?= e(env('APP_NAME', 'Monitoring Absensi UMKM')) ?></span>
            <span>Monitoring kehadiran &amp; progres kerja</span>
        </footer>
    </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
