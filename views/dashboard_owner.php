<?php
$stat = static function (string $label, mixed $value, string $hint, string $tone = ''): string {
    return '<div class="stat' . ($tone !== '' ? ' stat--' . $tone : '') . '">'
        . '<div class="stat__label">' . e($label) . '</div>'
        . '<div class="stat__value">' . e((string) $value) . '</div>'
        . '<div class="stat__hint">' . e($hint) . '</div>'
        . '</div>';
};
?>
<div class="page__header">
    <div>
        <h1 class="page__title">Selamat datang, <?= e(currentUser()['name']) ?> 👋</h1>
        <p class="page__subtitle">Ringkasan operasional tim hari ini, <?= e(formatDate(date('Y-m-d'))) ?>.</p>
    </div>
    <div class="page__actions">
        <a class="btn btn--secondary" href="<?= e(url('reports')) ?>">Lihat laporan</a>
        <a class="btn btn--primary" href="<?= e(url('tasks', ['action' => 'create'])) ?>">Buat tugas</a>
    </div>
</div>

<div class="stats" data-fr="FR-03">
    <?= $stat('Jadwal hari ini', $metrics['scheduled'], 'Total jadwal kerja', 'primary') ?>
    <?= $stat('Sudah hadir', $metrics['present'], 'Check-in tercatat', 'success') ?>
    <?= $stat('Terlambat', $metrics['late'], 'Perlu perhatian', 'warning') ?>
    <?= $stat('Koreksi menunggu', $metrics['pending_corrections'], 'Menunggu persetujuan', 'danger') ?>
</div>
<div class="stats" data-fr="FR-04">
    <?= $stat('Tugas berjalan', $metrics['task_running'], 'Belum / sedang dikerjakan') ?>
    <?= $stat('Menunggu persetujuan', $metrics['task_waiting'], 'Sudah diajukan selesai', 'primary') ?>
    <?= $stat('Tugas selesai', $metrics['task_done'], 'Disetujui owner', 'success') ?>
    <?= $stat('Lewat tenggat', $metrics['task_overdue'], 'Belum selesai', 'danger') ?>
</div>

<div class="grid grid--2">
    <div class="card">
        <div class="card__header">
            <div>
                <div class="card__title">Absensi terbaru</div>
                <div class="card__subtitle">Catatan check-in dan check-out karyawan</div>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('history')) ?>">Lihat semua</a>
        </div>
        <div class="card__body card--flush">
            <?php if (!$todayAttendance) : ?>
                <div class="empty"><div class="empty__icon">◷</div><div class="empty__title">Belum ada absensi</div><div class="empty__text">Data check-in akan muncul di sini.</div></div>
            <?php else : ?>
                <div class="table-wrap">
                    <table class="table table--compact">
                        <thead><tr><th>Karyawan</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($todayAttendance as $row) : ?>
                            <tr>
                                <td><strong><?= e($row['name']) ?></strong><div class="small muted"><?= e($row['employee_code']) ?></div></td>
                                <td class="nowrap"><?= e(timeOnly($row['check_in'])) ?></td>
                                <td class="nowrap"><?= e(timeOnly($row['check_out'])) ?></td>
                                <td><?= statusBadge($row['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <div>
                <div class="card__title">Aktivitas terbaru</div>
                <div class="card__subtitle">Absensi, koreksi, dan progres tugas</div>
            </div>
        </div>
        <div class="card__body">
            <?php if (!$activities) : ?>
                <div class="empty"><div class="empty__icon">✦</div><div class="empty__title">Belum ada aktivitas</div></div>
            <?php else : ?>
                <div class="timeline">
                    <?php foreach ($activities as $item) : ?>
                        <div class="timeline__item">
                            <div class="timeline__time"><?= e(formatDate($item['at'], true)) ?> · <?= e($item['type']) ?></div>
                            <div class="timeline__title"><a href="<?= e($item['link']) ?>"><?= e($item['title']) ?></a></div>
                            <div class="timeline__text"><?= e($item['text']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
