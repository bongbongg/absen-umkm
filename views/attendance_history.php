<div class="page__header">
    <div>
        <h1 class="page__title">Riwayat Absensi</h1>
        <p class="page__subtitle">Filter berdasarkan rentang tanggal<?= $owner ? ' dan karyawan' : '' ?>.</p>
    </div>
    <div class="page__actions">
        <a class="btn btn--secondary" href="<?= e(url('reports')) ?>">Buat laporan</a>
    </div>
</div>

<div class="card" data-fr="FR-07">
    <div class="card__header"><div class="card__title">Data absensi</div><div class="small muted">Total: <?= count($rows) ?> catatan</div></div>
    <div class="card__body">
        <form method="get" action="<?= e(url('history')) ?>" class="filters">
            <input type="hidden" name="page" value="history">
            <div class="filters__grid">
                <?php if ($owner) : ?>
                    <div class="field">
                        <label class="field__label" for="employee_id">Karyawan</label>
                        <select class="select" id="employee_id" name="employee_id" data-auto-submit>
                            <option value="0">Semua karyawan</option>
                            <?php foreach ($employees as $emp) : ?>
                                <option value="<?= (int) $emp['id'] ?>" <?= (int) $employeeId === (int) $emp['id'] ? 'selected' : '' ?>>
                                    <?= e($emp['name']) ?> (<?= e($emp['employee_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="field">
                    <label class="field__label" for="from">Dari tanggal</label>
                    <input class="input" id="from" name="from" type="date" value="<?= e($from) ?>">
                </div>
                <div class="field">
                    <label class="field__label" for="to">Sampai tanggal</label>
                    <input class="input" id="to" name="to" type="date" value="<?= e($to) ?>">
                </div>
                <div class="filters__actions">
                    <button class="btn btn--secondary" type="submit">Terapkan filter</button>
                </div>
            </div>
        </form>

        <?php if (!$rows) : ?>
            <div class="empty"><div class="empty__icon">◷</div><div class="empty__title">Tidak ada data pada periode ini</div><div class="empty__text">Coba ubah rentang tanggal atau pilihan karyawan.</div></div>
        <?php else : ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Tanggal</th><?= $owner ? '<th>Karyawan</th>' : '' ?><th>Jadwal</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Terlambat</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td class="nowrap"><?= e(formatDate($row['work_date'])) ?></td>
                            <?php if ($owner) : ?><td><?= e($row['name']) ?><div class="small muted"><?= e($row['employee_code']) ?></div></td><?php endif; ?>
                            <td class="nowrap small"><?= e(timeOnly($row['check_in_time'])) ?> - <?= e(timeOnly($row['check_out_time'])) ?></td>
                            <td class="nowrap"><?= e(timeOnly($row['check_in'])) ?></td>
                            <td class="nowrap"><?= e(timeOnly($row['check_out'])) ?></td>
                            <td><?= statusBadge($row['status']) ?></td>
                            <td class="small"><?= (int) $row['late_minutes'] ?> menit</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
