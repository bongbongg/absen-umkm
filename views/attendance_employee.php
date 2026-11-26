<div class="page__header">
    <div>
        <h1 class="page__title">Absensi Saya</h1>
        <p class="page__subtitle">Check-in dan check-out berdasarkan jadwal kerja Anda.</p>
    </div>
    <div class="page__actions">
        <a class="btn btn--secondary" href="<?= e(url('history')) ?>">Riwayat absensi</a>
        <a class="btn btn--primary" href="<?= e(url('corrections', ['action' => 'create'])) ?>">Ajukan koreksi</a>
    </div>
</div>

<div class="card" data-fr="FR-06">
    <div class="card__header">
        <div>
            <div class="card__title">Jadwal 7 hari terakhir dan mendatang</div>
            <div class="card__subtitle">Tombol absensi hanya aktif pada tanggal jadwal berlaku</div>
        </div>
    </div>
    <div class="card__body">
        <?php if (!$schedules) : ?>
            <div class="empty"><div class="empty__icon">✓</div><div class="empty__title">Belum ada jadwal</div><div class="empty__text">Hubungi owner untuk membuat jadwal kerja Anda.</div></div>
        <?php else : ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Jam kerja</th><th>Check-in</th><th>Check-out</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($schedules as $s) : ?>
                        <?php $today = $s['work_date'] === date('Y-m-d'); ?>
                        <tr>
                            <td class="nowrap">
                                <strong><?= e(formatDate($s['work_date'])) ?></strong>
                                <?php if ($today) : ?><div><span class="badge badge--primary">Hari ini</span></div><?php endif; ?>
                            </td>
                            <td class="nowrap small"><?= e(timeOnly($s['check_in_time'])) ?> - <?= e(timeOnly($s['check_out_time'])) ?></td>
                            <td class="nowrap"><?= e(timeOnly($s['check_in'])) ?></td>
                            <td class="nowrap"><?= e(timeOnly($s['check_out'])) ?></td>
                            <td>
                                <?php if ($s['check_in']) : ?>
                                    <?= statusBadge($s['status']) ?>
                                    <?php if ((int) $s['late_minutes'] > 0) : ?><div class="small danger-text">Telat <?= (int) $s['late_minutes'] ?> menit</div><?php endif; ?>
                                <?php else : ?>
                                    <span class="badge badge--muted">Belum absen</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <?php if (!$today) : ?>
                                    <span class="small muted">-</span>
                                <?php elseif (!$s['check_in']) : ?>
                                    <form method="post" action="<?= e(url('attendance', ['action' => 'checkin'])) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="schedule_id" value="<?= (int) $s['id'] ?>">
                                        <button class="btn btn--primary btn--sm" type="submit" data-confirm="Catat check-in sekarang?">Check-in</button>
                                    </form>
                                <?php elseif (!$s['check_out']) : ?>
                                    <form method="post" action="<?= e(url('attendance', ['action' => 'checkout'])) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="schedule_id" value="<?= (int) $s['id'] ?>">
                                        <button class="btn btn--secondary btn--sm" type="submit" data-confirm="Catat check-out sekarang?">Check-out</button>
                                    </form>
                                <?php else : ?>
                                    <span class="badge badge--success">Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
