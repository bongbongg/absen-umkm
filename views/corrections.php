<div class="page__header">
    <div>
        <h1 class="page__title">Koreksi Absensi</h1>
        <p class="page__subtitle"><?= $owner ? 'Setujui atau tolak pengajuan koreksi karyawan.' : 'Pantau status pengajuan koreksi absensi Anda.' ?></p>
    </div>
    <?php if (!$owner) : ?>
        <div class="page__actions"><a class="btn btn--primary" href="<?= e(url('corrections', ['action' => 'create'])) ?>">+ Ajukan koreksi</a></div>
    <?php endif; ?>
</div>

<div class="card" data-fr="FR-08 / FR-09">
    <div class="card__header">
        <div class="card__title">Daftar pengajuan</div>
        <div class="small muted">Total: <?= count($corrections) ?> pengajuan</div>
    </div>
    <div class="card__body">
        <form method="get" action="<?= e(url('corrections')) ?>" class="filters">
            <input type="hidden" name="page" value="corrections">
            <div class="filters__grid">
                <div class="field">
                    <label class="field__label" for="status">Status</label>
                    <select class="select" id="status" name="status" data-auto-submit>
                        <option value="">Semua status</option>
                        <?php foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $key => $label) : ?>
                            <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filters__actions"><a class="btn btn--ghost" href="<?= e(url('corrections')) ?>">Reset</a></div>
            </div>
        </form>

        <?php if (!$corrections) : ?>
            <div class="empty"><div class="empty__icon">↻</div><div class="empty__title">Belum ada pengajuan</div><div class="empty__text">Pengajuan koreksi akan muncul di sini.</div></div>
        <?php else : ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Tanggal</th>
                        <?php if ($owner) : ?><th>Karyawan</th><?php endif; ?>
                        <th>Jam diajukan</th><th>Catatan asli</th><th>Alasan</th><th>Status</th><th class="text-right">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($corrections as $c) : ?>
                        <tr>
                            <td class="nowrap"><?= e(formatDate($c['correction_date'])) ?></td>
                            <?php if ($owner) : ?><td><?= e($c['name']) ?><div class="small muted"><?= e($c['employee_code']) ?></div></td><?php endif; ?>
                            <td class="nowrap small">
                                Masuk: <?= e(timeOnly($c['requested_check_in'])) ?><br>
                                Pulang: <?= e(timeOnly($c['requested_check_out'])) ?>
                            </td>
                            <td class="nowrap small">
                                <?= e(timeOnly($c['actual_check_in'])) ?><br><?= e(timeOnly($c['actual_check_out'])) ?>
                            </td>
                            <td class="small"><?= e(strLimit($c['reason'], 70)) ?></td>
                            <td><?= statusBadge($c['status']) ?></td>
                            <td class="text-right">
                                <?php if ($owner && $c['status'] === 'pending') : ?>
                                    <div class="stack">
                                        <form method="post" action="<?= e(url('corrections', ['action' => 'approve'])) ?>" class="inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                            <input class="input" name="review_note" placeholder="Catatan (opsional)" aria-label="Catatan persetujuan">
                                            <button class="btn btn--primary btn--sm" type="submit" data-confirm="Setujui koreksi ini dan perbarui data absensi?">Setujui</button>
                                        </form>
                                        <form method="post" action="<?= e(url('corrections', ['action' => 'reject'])) ?>" class="inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                            <input class="input" name="review_note" placeholder="Alasan penolakan" aria-label="Alasan penolakan">
                                            <button class="btn btn--danger btn--sm" type="submit" data-confirm="Tolak pengajuan koreksi ini?">Tolak</button>
                                        </form>
                                    </div>
                                <?php else : ?>
                                    <span class="small muted"><?= e(strLimit($c['review_note'] ?? '-', 40)) ?></span>
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
