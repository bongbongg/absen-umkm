<?php
$editing = $task !== null;
$value = static fn(string $key, string $default = '') => e(old($key, $task[$key] ?? $default));
$action = $editing ? url('tasks', ['action' => 'update', 'id' => $task['id']]) : url('tasks', ['action' => 'store']);
$deadlineValue = old('deadline', $task ? date('Y-m-d\TH:i', strtotime($task['deadline'])) : date('Y-m-d\TH:i', strtotime('+3 days')));
?>
<div class="page__header">
    <div>
        <h1 class="page__title"><?= $editing ? 'Ubah Tugas' : 'Buat Tugas Baru' ?></h1>
        <p class="page__subtitle">Tugas akan langsung terlihat oleh karyawan yang ditugaskan.</p>
    </div>
    <div class="page__actions"><a class="btn btn--secondary" href="<?= e(url('tasks')) ?>">← Kembali</a></div>
</div>

<form method="post" action="<?= e($action) ?>" class="form card">
    <?= csrf_field() ?>
    <div class="card__header"><div class="card__title">Detail tugas</div></div>
    <div class="card__body">
        <div class="form-grid">
            <div class="field field--full">
                <label class="field__label" for="title">Judul tugas</label>
                <input class="input" id="title" name="title" value="<?= $value('title') ?>" placeholder="Contoh: Rekap penjualan mingguan" required>
            </div>
            <div class="field">
                <label class="field__label" for="assigned_to">Karyawan penerima</label>
                <select class="select" id="assigned_to" name="assigned_to" required>
                    <option value="">-- Pilih karyawan --</option>
                    <?php foreach ($employees as $emp) : ?>
                        <?php $selected = (string) old('assigned_to', $task['assigned_to'] ?? '') === (string) $emp['id']; ?>
                        <option value="<?= (int) $emp['id'] ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= e($emp['name']) ?> (<?= e($emp['employee_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="field__label" for="deadline">Tenggat waktu</label>
                <input class="input" id="deadline" name="deadline" type="datetime-local" value="<?= e($deadlineValue) ?>" required>
            </div>
            <div class="field field--full">
                <label class="field__label" for="description">Deskripsi</label>
                <textarea class="textarea" id="description" name="description" rows="5" placeholder="Jelaskan hasil yang diharapkan, langkah kerja, atau kriteria selesai..."><?= $value('description') ?></textarea>
            </div>
        </div>
    </div>
    <div class="card__footer form-actions">
        <button class="btn btn--primary" type="submit"><?= $editing ? 'Simpan perubahan' : 'Buat dan bagikan tugas' ?></button>
        <a class="btn btn--ghost" href="<?= e(url('tasks')) ?>">Batal</a>
    </div>
</form>
