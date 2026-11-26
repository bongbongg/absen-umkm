<?php
$isTasks = $type === 'tasks';
?>
<div class="page__header">
    <div>
        <h1 class="page__title">Laporan &amp; Ekspor</h1>
        <p class="page__subtitle">Unduh data absensi atau progres tugas dalam format PDF dan Excel (CSV).</p>
    </div>
</div>

<div class="card" data-fr="FR-14">
    <div class="card__header"><div class="card__title">Filter laporan</div></div>
    <div class="card__body">
        <form method="get" action="<?= e(url('reports')) ?>" class="filters">
            <input type="hidden" name="page" value="reports">
            <div class="filters__grid">
                <div class="field">
                    <label class="field__label" for="type">Jenis laporan</label>
                    <select class="select" id="type" name="type" data-auto-submit>
                        <option value="attendance" <?= !$isTasks ? 'selected' : '' ?>>Absensi</option>
                        <option value="tasks" <?= $isTasks ? 'selected' : '' ?>>Tugas / Progres</option>
                    </select>
                </div>
                <div class="field">
                    <label class="field__label" for="from">Dari tanggal</label>
                    <input class="input" id="from" name="from" type="date" value="<?= e($from) ?>">
                </div>
                <div class="field">
                    <label class="field__label" for="to">Sampai tanggal</label>
                    <input class="input" id="to" name="to" type="date" value="<?= e($to) ?>">
                </div>
                <div class="filters__actions"><button class="btn btn--secondary" type="submit">Tampilkan</button></div>
            </div>
        </form>

        <div class="divider"></div>

        <form method="post" action="<?= e(url('reports', ['action' => 'export'])) ?>" class="row">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <input type="hidden" name="from" value="<?= e($from) ?>">
            <input type="hidden" name="to" value="<?= e($to) ?>">
            <div class="field" style="min-width:150px">
                <label class="field__label" for="format">Format unduhan</label>
                <select class="select" id="format" name="format">
                    <option value="csv">Excel (CSV)</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>
            <div class="field" style="align-self:end">
                <button class="btn btn--primary" type="submit" data-confirm="Unduh laporan <?= $isTasks ? 'tugas' : 'absensi' ?> periode ini?">↓ Unduh laporan</button>
            </div>
        </form>
    </div>
</div>

<div class="card" data-fr="FR-15">
    <div class="card__header">
        <div>
            <div class="card__title">Pratinjau data</div>
            <div class="card__subtitle">Periode <?= e(formatDate($from)) ?> s.d. <?= e(formatDate($to)) ?> · <?= count($rows) ?> baris</div>
        </div>
    </div>
    <div class="card__body">
        <?php if (!$rows) : ?>
            <div class="empty"><div class="empty__icon">▤</div><div class="empty__title">Tidak ada data pada periode ini</div><div class="empty__text">Ubah filter tanggal untuk melihat data lain.</div></div>
        <?php elseif ($isTasks) : ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>ID</th><th>Judul</th><th>Karyawan</th><th>Deadline</th><th>Status</th><th>Progres</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td>#<?= (int) $row['id'] ?></td>
                            <td><a href="<?= e(url('task', ['id' => $row['id']])) ?>"><?= e(strLimit($row['title'], 45)) ?></a></td>
                            <td><?= e($row['name']) ?><div class="small muted"><?= e($row['employee_code']) ?></div></td>
                            <td class="nowrap small"><?= e(formatDate($row['deadline'], true)) ?></td>
                            <td><?= statusBadge($row['status']) ?></td>
                            <td class="nowrap"><?= (int) $row['progress_percent'] ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>ID</th><th>Tanggal</th><th>Karyawan</th><th>Jabatan</th><th>Masuk</th><th>Pulang</th><th>Status</th><th>Telat</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td>#<?= (int) $row['id'] ?></td>
                            <td class="nowrap"><?= e(formatDate($row['work_date'])) ?></td>
                            <td><?= e($row['name']) ?><div class="small muted"><?= e($row['employee_code']) ?></div></td>
                            <td><?= e($row['position'] ?? '-') ?></td>
                            <td class="nowrap"><?= e(timeOnly($row['check_in'])) ?></td>
                            <td class="nowrap"><?= e(timeOnly($row['check_out'])) ?></td>
                            <td><?= statusBadge($row['status']) ?></td>
                            <td class="small"><?= (int) $row['late_minutes'] ?> mnt</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
