<?php
$percent = (int) $task['progress_percent'];
$overdue = $task['status'] !== 'completed' && strtotime($task['deadline']) < time();
$canUpdate = !$owner && in_array($task['status'], ['todo', 'in_progress', 'revision'], true);
$canReview = $owner && $task['status'] === 'submitted';
?>
<div class="page__header">
    <div>
        <h1 class="page__title"><?= e($task['title']) ?></h1>
        <p class="page__subtitle">Ditugaskan kepada <strong><?= e($task['employee_name']) ?></strong> · Deadline <?= e(formatDate($task['deadline'], true)) ?></p>
    </div>
    <div class="page__actions"><a class="btn btn--secondary" href="<?= e(url('tasks')) ?>">← Kembali ke daftar</a></div>
</div>

<div class="grid grid--2">
    <div class="card">
        <div class="card__header">
            <div class="card__title">Informasi tugas</div>
            <?= statusBadge($task['status']) ?>
        </div>
        <div class="card__body stack">
            <div class="kv">
                <div class="kv__item"><span class="kv__label">ID tugas</span><span class="kv__value">#<?= (int) $task['id'] ?></span></div>
                <div class="kv__item"><span class="kv__label">Karyawan</span><span class="kv__value"><?= e($task['employee_name']) ?> (<?= e($task['employee_code']) ?>)</span></div>
                <div class="kv__item"><span class="kv__label">Dibuat oleh</span><span class="kv__value"><?= e($task['creator_name']) ?></span></div>
                <div class="kv__item"><span class="kv__label">Deadline</span><span class="kv__value"><?= e(formatDate($task['deadline'], true)) ?></span></div>
                <div class="kv__item"><span class="kv__label">Dibuat</span><span class="kv__value"><?= e(formatDate($task['created_at'], true)) ?></span></div>
                <div class="kv__item"><span class="kv__label">Disetujui</span><span class="kv__value"><?= e(formatDate($task['approved_at'], true)) ?></span></div>
            </div>
            <?php if ($overdue) : ?><div class="alert alert--danger">Tugas sudah melewati tenggat dan belum disetujui selesai.</div><?php endif; ?>
            <?php if ($task['description']) : ?>
                <div><div class="field__label">Deskripsi</div><p class="muted"><?= nl2br(e($task['description'])) ?></p></div>
            <?php endif; ?>
            <?php if ($task['progress_note']) : ?>
                <div class="alert alert--info"><strong>Catatan progres terakhir:</strong><br><?= nl2br(e($task['progress_note'])) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="stack">
        <div class="card" data-fr="FR-11">
            <div class="card__header"><div class="card__title">Progres pengerjaan</div></div>
            <div class="card__body stack">
                <div class="row">
                    <div class="stat stat--primary" style="flex:1">
                        <div class="stat__label">Persentase selesai</div>
                        <div class="stat__value"><?= $percent ?>%</div>
                        <div class="stat__hint"><?= $percent === 100 ? 'Siap diajukan selesai' : 'Masih dalam pengerjaan' ?></div>
                    </div>
                </div>
                <div class="progress"><div class="progress__bar" style="width:<?= $percent ?>%"></div></div>
            </div>
        </div>

        <?php if ($canUpdate) : ?>
            <div class="card" data-fr="FR-11">
                <div class="card__header"><div class="card__title">Perbarui progres</div></div>
                <div class="card__body">
                    <form method="post" action="<?= e(url('task', ['action' => 'progress'])) ?>" class="form stack">
                        <?= csrf_field() ?>
                        <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                        <div class="field">
                            <label class="field__label" for="progress_range">Persentase (0–100)</label>
                            <div class="row">
                                <input type="range" id="progress_range" min="0" max="100" step="5"
                                       value="<?= $percent ?>" data-range-sync="progress_number" style="flex:1;min-width:150px">
                                <input class="input" type="number" id="progress_number" name="progress_percent"
                                       min="0" max="100" value="<?= $percent ?>" data-range-target="progress_range" style="width:90px">
                            </div>
                        </div>
                        <div class="field">
                            <label class="field__label" for="status">Status</label>
                            <select class="select" id="status" name="status">
                                <option value="todo" <?= $task['status'] === 'todo' ? 'selected' : '' ?>>Belum dimulai</option>
                                <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>Berjalan</option>
                                <option value="revision" <?= $task['status'] === 'revision' ? 'selected' : '' ?>>Sedang revisi</option>
                            </select>
                        </div>
                        <div class="field">
                            <label class="field__label" for="note">Keterangan progres</label>
                            <textarea class="textarea" id="note" name="note" rows="2" placeholder="Apa yang sudah dikerjakan?"><?= e($task['progress_note']) ?></textarea>
                        </div>
                        <button class="btn btn--primary" type="submit">Simpan progres</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($canReview) : ?>
            <div class="card" data-fr="FR-13">
                <div class="card__header"><div class="card__title">Persetujuan tugas</div></div>
                <div class="card__body stack">
                    <p class="muted">Periksa bukti pekerjaan dan progres sebelum menyetujui atau mengembalikan tugas.</p>
                    <form method="post" action="<?= e(url('task', ['action' => 'approve'])) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                        <button class="btn btn--primary" type="submit" data-confirm="Setujui tugas ini sebagai selesai?">✓ Setujui selesai</button>
                    </form>
                    <form method="post" action="<?= e(url('task', ['action' => 'revision'])) ?>" class="form stack">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                        <div class="field">
                            <label class="field__label" for="review_note">Catatan revisi</label>
                            <textarea class="textarea" id="review_note" name="review_note" rows="2" placeholder="Bagian apa yang perlu diperbaiki?" required></textarea>
                        </div>
                        <button class="btn btn--secondary" type="submit" data-confirm="Kembalikan tugas untuk revisi?">↻ Kembalikan untuk revisi</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card" data-fr="FR-12">
    <div class="card__header">
        <div>
            <div class="card__title">Bukti pekerjaan</div>
            <div class="card__subtitle">JPG, PNG, PDF, DOC, DOCX · maksimal <?= e(formatBytes(uploadMaxBytes())) ?> per file</div>
        </div>
    </div>
    <div class="card__body stack">
        <?php if (!$evidences) : ?>
            <div class="empty"><div class="empty__icon">⇧</div><div class="empty__title">Belum ada bukti diunggah</div></div>
        <?php else : ?>
            <div class="table-wrap">
                <table class="table table--compact">
                    <thead><tr><th>Nama file</th><th>Ukuran</th><th>Diunggah oleh</th><th>Waktu</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($evidences as $ev) : ?>
                        <tr>
                            <td><?= e($ev['file_name']) ?></td>
                            <td class="small nowrap"><?= e(formatBytes((int) $ev['file_size'])) ?></td>
                            <td><?= e($ev['name']) ?></td>
                            <td class="small nowrap"><?= e(formatDate($ev['created_at'], true)) ?></td>
                            <td class="text-right"><a class="btn btn--ghost btn--sm" href="<?= e(baseUrl() . $ev['file_path']) ?>" target="_blank" rel="noopener">Buka file</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!$owner && $task['status'] !== 'completed') : ?>
            <div class="divider"></div>
            <div class="grid grid--2">
                <form method="post" action="<?= e(url('task', ['action' => 'evidence'])) ?>" enctype="multipart/form-data" class="form stack">
                    <?= csrf_field() ?>
                    <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                    <div class="field">
                        <label class="field__label" for="evidence">Unggah bukti baru</label>
                        <input class="input" id="evidence" name="evidence" type="file" data-file-label="file_label"
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
                        <div class="field__hint" id="file_label">Belum ada file dipilih</div>
                    </div>
                    <button class="btn btn--secondary" type="submit">Unggah bukti</button>
                </form>

                <?php if ($canUpdate && $percent === 100 && count($evidences) > 0) : ?>
                    <form method="post" action="<?= e(url('task', ['action' => 'submit'])) ?>" class="form stack">
                        <?= csrf_field() ?>
                        <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                        <div class="field">
                            <label class="field__label" for="completion_note">Catatan penyelesaian (opsional)</label>
                            <textarea class="textarea" id="completion_note" name="completion_note" rows="2" placeholder="Ringkasan hasil pekerjaan"></textarea>
                        </div>
                        <button class="btn btn--primary" type="submit" data-confirm="Ajukan tugas ini sebagai selesai?">Ajukan selesai</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card__header"><div class="card__title">Riwayat progres</div></div>
    <div class="card__body">
        <?php if (!$logs) : ?>
            <div class="empty"><div class="empty__icon">◷</div><div class="empty__title">Belum ada catatan progres</div></div>
        <?php else : ?>
            <div class="timeline">
                <?php foreach ($logs as $log) : ?>
                    <div class="timeline__item">
                        <div class="timeline__time"><?= e(formatDate($log['created_at'], true)) ?> · <?= e($log['name']) ?></div>
                        <div class="timeline__title"><?= (int) $log['progress_percent'] ?>% · <?= e(statusBadgeText($log['status'])) ?></div>
                        <div class="timeline__text"><?= e($log['note'] ?: '-') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
