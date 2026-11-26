<div class="page__header">
    <div>
        <h1 class="page__title">Jadwal Kerja</h1>
        <p class="page__subtitle"><?= $owner ? 'Atur jadwal masuk dan pulang karyawan.' : 'Lihat jadwal kerja Anda dan lakukan absensi tepat waktu.' ?></p>
    </div>
    <?php if ($owner) : ?>
        <div class="page__actions"><a class="btn btn--primary" href="<?= e(url('schedules', ['action' => 'create'])) ?>">+ Tambah jadwal</a></div>
    <?php endif; ?>
</div>

<div class="card" data-fr="FR-05">
    <div class="card__header">
        <div class="card__title">Daftar jadwal</div>
        <div class="small muted">Total: <?= count($schedules) ?> jadwal</div>
    </div>
    <div class="card__body">
        <form method="get" action="<?= e(url('schedules')) ?>" class="filters">
            <input type="hidden" name="page" value="schedules">
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
                    <button class="btn btn--secondary" type="submit">Filter</button>
                    <a class="btn btn--ghost" href="<?= e(url('schedules')) ?>">Reset</a>
                </div>
            </div>
        </form>

        <?php if (!$schedules) : ?>
            <div class="empty"><div class="empty__icon">▦</div><div class="empty__title">Belum ada jadwal</div><div class="empty__text">Ubah filter atau tambahkan jadwal baru.</div></div>
        <?php else : ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Karyawan</th><th>Jam masuk</th><th>Jam pulang</th><th>Absensi</th><th>Catatan</th><?= $owner ? '<th class="text-right">Aksi</th>' : '' ?></tr></thead>
                    <tbody>
                    <?php foreach ($schedules as $s) : ?>
                        <tr>
                            <td class="nowrap"><strong><?= e(formatDate($s['work_date'])) ?></strong></td>
                            <td><?= e($s['name']) ?><div class="small muted"><?= e($s['employee_code']) ?></div></td>
                            <td class="nowrap"><?= e(timeOnly($s['check_in_time'])) ?></td>
                            <td class="nowrap"><?= e(timeOnly($s['check_out_time'])) ?></td>
                            <td>
                                <?php if ($s['check_in']) : ?>
                                    <?= statusBadge($s['status']) ?>
                                    <div class="small muted"><?= e(timeOnly($s['check_in'])) ?> s.d. <?= e(timeOnly($s['check_out'])) ?></div>
                                <?php else : ?>
                                    <span class="badge badge--muted">Belum absen</span>
                                <?php endif; ?>
                            </td>
                            <td class="small muted"><?= e(strLimit($s['note'], 40)) ?></td>
                            <?php if ($owner) : ?>
                                <td class="text-right">
                                    <div class="btn-group nowrap">
                                        <a class="btn btn--ghost btn--sm" href="<?= e(url('schedules', ['action' => 'edit', 'id' => $s['id']])) ?>">Ubah</a>
                                        <form method="post" action="<?= e(url('schedules', ['action' => 'delete'])) ?>" class="inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                            <button class="btn btn--danger btn--sm" type="submit" data-confirm="Hapus jadwal <?= e(formatDate($s['work_date'])) ?> untuk <?= e($s['name']) ?>?">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
