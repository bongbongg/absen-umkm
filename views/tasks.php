<div class="page__header">
    <div>
        <h1 class="page__title">Tugas &amp; Progres</h1>
        <p class="page__subtitle"><?= $owner ? 'Bagi tugas dan pantau progres karyawan.' : 'Tugas yang ditugaskan kepada Anda beserta progresnya.' ?></p>
    </div>
    <?php if ($owner) : ?>
        <div class="page__actions"><a class="btn btn--primary" href="<?= e(url('tasks', ['action' => 'create'])) ?>">+ Buat tugas</a></div>
    <?php endif; ?>
</div>

<div class="card" data-fr="FR-10">
    <div class="card__header">
        <div class="card__title">Daftar tugas</div>
        <div class="small muted">Total: <?= count($tasks) ?> tugas</div>
    </div>
    <div class="card__body">
        <form method="get" action="<?= e(url('tasks')) ?>" class="filters">
            <input type="hidden" name="page" value="tasks">
            <div class="filters__grid">
                <div class="field">
                    <label class="field__label" for="status">Status</label>
                    <select class="select" id="status" name="status" data-auto-submit>
                        <option value="">Semua status</option>
                        <?php
                        $labels = [
                            'todo' => 'Belum dimulai', 'in_progress' => 'Berjalan',
                            'submitted' => 'Menunggu persetujuan', 'revision' => 'Perlu revisi',
                            'completed' => 'Selesai',
                        ];
                        foreach ($labels as $key => $label) : ?>
                            <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filters__actions"><a class="btn btn--ghost" href="<?= e(url('tasks')) ?>">Reset</a></div>
            </div>
        </form>

        <?php if (!$tasks) : ?>
            <div class="empty"><div class="empty__icon">☑</div><div class="empty__title">Belum ada tugas</div><div class="empty__text">Tugas yang dibagikan akan muncul di sini.</div></div>
        <?php else : ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Tugas</th><?= $owner ? '<th>Karyawan</th>' : '' ?><th>Deadline</th><th>Progres</th><th>Status</th><th>Bukti</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($tasks as $t) : ?>
                        <?php $overdue = $t['status'] !== 'completed' && strtotime($t['deadline']) < time(); ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('task', ['id' => $t['id']])) ?>"><strong><?= e(strLimit($t['title'], 50)) ?></strong></a>
                                <div class="small muted"><?= e(strLimit($t['description'], 55)) ?></div>
                            </td>
                            <?php if ($owner) : ?><td><?= e($t['employee_name']) ?><div class="small muted"><?= e($t['employee_code']) ?></div></td><?php endif; ?>
                            <td class="nowrap small">
                                <?= e(formatDate($t['deadline'], true)) ?>
                                <?php if ($overdue) : ?><div class="danger-text">Lewat tenggat</div><?php endif; ?>
                            </td>
                            <td style="min-width:120px">
                                <div class="progress"><div class="progress__bar" style="width:<?= (int) $t['progress_percent'] ?>%"></div></div>
                                <span class="small muted"><?= (int) $t['progress_percent'] ?>%</span>
                            </td>
                            <td><?= statusBadge($t['status']) ?></td>
                            <td class="small"><?= (int) $t['evidence_count'] ?> file</td>
                            <td class="text-right">
                                <div class="btn-group nowrap">
                                    <a class="btn btn--ghost btn--sm" href="<?= e(url('task', ['id' => $t['id']])) ?>">Detail</a>
                                    <?php if ($owner) : ?>
                                        <a class="btn btn--ghost btn--sm" href="<?= e(url('tasks', ['action' => 'edit', 'id' => $t['id']])) ?>">Ubah</a>
                                        <form method="post" action="<?= e(url('tasks', ['action' => 'delete'])) ?>" class="inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                            <button class="btn btn--danger btn--sm" type="submit" data-confirm="Hapus tugas «<?= e($t['title']) ?>» beserta buktinya?">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
