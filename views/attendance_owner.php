<div class="page__header">
    <div>
        <h1 class="page__title">Absensi Hari Ini</h1>
        <p class="page__subtitle">Pantau kehadiran seluruh karyawan pada <?= e(formatDate(date('Y-m-d'))) ?>.</p>
    </div>
    <div class="page__actions">
        <a class="btn btn--secondary" href="<?= e(url('history')) ?>">Lihat riwayat</a>
        <a class="btn btn--primary" href="<?= e(url('corrections')) ?>">Koreksi menunggu</a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <div class="card__title">Catatan kehadiran</div>
        <div class="small muted">Total: <?= count($rows) ?> catatan</div>
    </div>
    <div class="card__body">
        <?php if (!$rows) : ?>
            <div class="empty"><div class="empty__icon">✓</div><div class="empty__title">Belum ada absensi hari ini</div><div class="empty__text">Data akan muncul setelah karyawan melakukan check-in.</div></div>
        <?php else : ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Karyawan</th><th>Jadwal</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Keterangan</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><strong><?= e($row['name']) ?></strong><div class="small muted"><?= e($row['employee_code']) ?></div></td>
                            <td class="nowrap small"><?= e(timeOnly($row['check_in_time'])) ?> - <?= e(timeOnly($row['check_out_time'])) ?></td>
                            <td class="nowrap"><?= e(timeOnly($row['check_in'])) ?></td>
                            <td class="nowrap"><?= e(timeOnly($row['check_out'])) ?></td>
                            <td><?= statusBadge($row['status']) ?></td>
                            <td class="small">
                                <?php if ((int) $row['late_minutes'] > 0) : ?><span class="danger-text">Terlambat <?= (int) $row['late_minutes'] ?> menit</span><?php else : ?><span class="muted">Tepat waktu</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
