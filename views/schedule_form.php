<?php
$editing = $schedule !== null;
$value = static fn(string $key, string $default = '') => e(old($key, $schedule[$key] ?? $default));
$action = $editing ? url('schedules', ['action' => 'update', 'id' => $schedule['id']]) : url('schedules', ['action' => 'store']);
?>
<div class="page__header">
    <div>
        <h1 class="page__title"><?= $editing ? 'Ubah Jadwal Kerja' : 'Tambah Jadwal Kerja' ?></h1>
        <p class="page__subtitle">Tentukan karyawan, tanggal, serta jam masuk dan pulang.</p>
    </div>
    <div class="page__actions"><a class="btn btn--secondary" href="<?= e(url('schedules')) ?>">← Kembali</a></div>
</div>

<form method="post" action="<?= e($action) ?>" class="form card">
    <?= csrf_field() ?>
    <div class="card__header"><div class="card__title">Detail jadwal</div></div>
    <div class="card__body">
        <div class="form-grid">
            <div class="field">
                <label class="field__label" for="user_id">Karyawan</label>
                <select class="select" id="user_id" name="user_id" required>
                    <option value="">-- Pilih karyawan --</option>
                    <?php foreach ($employees as $emp) : ?>
                        <?php $selected = (string) old('user_id', $schedule['user_id'] ?? '') === (string) $emp['id']; ?>
                        <option value="<?= (int) $emp['id'] ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= e($emp['name']) ?> (<?= e($emp['employee_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="field__label" for="work_date">Tanggal kerja</label>
                <input class="input" id="work_date" name="work_date" type="date" value="<?= $value('work_date', date('Y-m-d')) ?>" required>
            </div>
            <div class="field">
                <label class="field__label" for="check_in_time">Jam masuk</label>
                <input class="input" id="check_in_time" name="check_in_time" type="time" value="<?= $value('check_in_time', '08:00') ?>" required>
            </div>
            <div class="field">
                <label class="field__label" for="check_out_time">Jam pulang</label>
                <input class="input" id="check_out_time" name="check_out_time" type="time" value="<?= $value('check_out_time', '17:00') ?>" required>
                <div class="field__hint">Jam pulang harus lebih akhir daripada jam masuk.</div>
            </div>
            <div class="field field--full">
                <label class="field__label" for="note">Catatan (opsional)</label>
                <textarea class="textarea" id="note" name="note" rows="2" placeholder="Mis. shift pagi, lembur, dll."><?= $value('note') ?></textarea>
            </div>
        </div>
    </div>
    <div class="card__footer form-actions">
        <button class="btn btn--primary" type="submit"><?= $editing ? 'Simpan perubahan' : 'Tambah jadwal' ?></button>
        <a class="btn btn--ghost" href="<?= e(url('schedules')) ?>">Batal</a>
    </div>
</form>
