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
        <h1 class="page__title">Halo, <?= e(currentUser()['name']) ?> 👋</h1>
        <p class="page__subtitle">Jadwal dan tugas Anda hari ini, <?= e(formatDate(date('Y-m-d'))) ?>.</p>
    </div>
    <div class="page__actions">
        <a class="btn btn--secondary" href="<?= e(url('attendance')) ?>">Absensi saya</a>
        <a class="btn btn--primary" href="<?= e(url('tasks')) ?>">Lihat tugas</a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <div>
            <div class="card__title">Jadwal kerja hari ini</div>
            <div class="card__subtitle">Lakukan check-in dan check-out sesuai jadwal</div>
        </div>
        <?php if ($schedule && $schedule['status']) : ?><?= statusBadge($schedule['status']) ?><?php endif; ?>
    </div>
    <div class="card__body">
        <?php if (!$schedule) : ?>
            <div class="empty">
                <div class="empty__icon">▦</div>
                <div class="empty__title">Belum ada jadwal hari ini</div>
                <div class="empty__text">Hubungi owner jika jadwal Anda belum dibuat.</div>
            </div>
        <?php else : ?>
            <div class="grid grid--3">
                <div class="kv__item"><span class="kv__label">Jam masuk</span><span class="kv__value"><?= e(timeOnly($schedule['check_in_time'])) ?></span></div>
                <div class="kv__item"><span class="kv__label">Jam pulang</span><span class="kv__value"><?= e(timeOnly($schedule['check_out_time'])) ?></span></div>
                <div class="kv__item"><span class="kv__label">Check-in Anda</span><span class="kv__value"><?= e(timeOnly($schedule['check_in'])) ?></span></div>
            </div>
            <?php if ($schedule['check_in'] === null) : ?>
                <div class="divider"></div>
                <form method="post" action="<?= e(url('attendance', ['action' => 'checkin'])) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="schedule_id" value="<?= (int) $schedule['id'] ?>">
                    <button class="btn btn--primary" type="submit" data-confirm="Catat waktu check-in sekarang?">✓ Check-in sekarang</button>
                </form>
            <?php elseif ($schedule['check_out'] === null) : ?>
                <div class="divider"></div>
                <div class="row">
                    <form method="post" action="<?= e(url('attendance', ['action' => 'checkout'])) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="schedule_id" value="<?= (int) $schedule['id'] ?>">
                        <button class="btn btn--secondary" type="submit" data-confirm="Catat waktu check-out sekarang?">✓ Check-out sekarang</button>
                    </form>
                    <span class="muted small">Check-in tercatat pukul <?= e(timeOnly($schedule['check_in'])) ?></span>
                </div>
            <?php else : ?>
                <div class="divider"></div>
                <div class="alert alert--success">Absensi hari ini sudah lengkap. Check-in <?= e(timeOnly($schedule['check_in'])) ?> · check-out <?= e(timeOnly($schedule['check_out'])) ?>.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="stats">
    <?= $stat('Tugas berjalan', $taskCounts['in_progress'], 'Sedang dikerjakan', 'primary') ?>
    <?= $stat('Menunggu review', $taskCounts['submitted'], 'Diajukan selesai', 'warning') ?>
    <?= $stat('Tugas selesai', $taskCounts['completed'], 'Disetujui owner', 'success') ?>
    <?= $stat('Lewat tenggat', $taskCounts['overdue'], 'Perlu diselesaikan', 'danger') ?>
</div>

<div class="grid grid--2">
    <div class="card">
        <div class="card__header">
            <div>
                <div class="card__title">Tugas terdekat</div>
                <div class="card__subtitle">Perbarui progres dan unggah bukti</div>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('tasks')) ?>">Semua tugas</a>
        </div>
        <div class="card__body card--flush">
            <?php if (!$tasks) : ?>
                <div class="empty"><div class="empty__icon">☑</div><div class="empty__title">Belum ada tugas</div></div>
            <?php else : ?>
                <div class="table-wrap">
                    <table class="table table--compact">
                        <thead><tr><th>Tugas</th><th>Deadline</th><th>Progres</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($tasks as $task) : ?>
                            <tr>
                                <td><a href="<?= e(url('task', ['id' => $task['id']])) ?>"><?= e(strLimit($task['title'], 42)) ?></a></td>
                                <td class="nowrap small"><?= e(formatDate($task['deadline'], true)) ?></td>
                                <td style="min-width:110px">
                                    <div class="progress"><div class="progress__bar" style="width:<?= (int) $task['progress_percent'] ?>%"></div></div>
                                    <span class="small muted"><?= (int) $task['progress_percent'] ?>%</span>
                                </td>
                                <td><?= statusBadge($task['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card__header"><div class="card__title">Koreksi absensi</div></div>
        <div class="card__body stack">
            <p class="muted">Jika waktu check-in/check-out Anda tercatat keliru, ajukan koreksi beserta alasannya.</p>
            <?php if ($pendingCorrections > 0) : ?>
                <div class="alert alert--warning">Ada <?= (int) $pendingCorrections ?> pengajuan koreksi yang masih menunggu persetujuan owner.</div>
            <?php endif; ?>
            <div class="row">
                <a class="btn btn--secondary" href="<?= e(url('corrections')) ?>">Riwayat pengajuan</a>
                <a class="btn btn--primary" href="<?= e(url('corrections', ['action' => 'create'])) ?>">Ajukan koreksi</a>
            </div>
        </div>
    </div>
</div>
