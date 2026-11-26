<?php
$editing = $employee !== null;
$value = static fn(string $key, string $default = '') => e(old($key, $employee[$key] ?? $default));
$action = $editing
    ? url('employees', ['action' => 'update', 'id' => $employee['id']])
    : url('employees', ['action' => 'store']);
?>
<div class="page__header">
    <div>
        <h1 class="page__title"><?= $editing ? 'Ubah Karyawan' : 'Tambah Karyawan' ?></h1>
        <p class="page__subtitle">Data akun dan identitas karyawan UMKM.</p>
    </div>
    <div class="page__actions"><a class="btn btn--secondary" href="<?= e(url('employees')) ?>">← Kembali</a></div>
</div>

<form method="post" action="<?= e($action) ?>" class="form card">
    <?= csrf_field() ?>
    <div class="card__header"><div class="card__title">Informasi akun</div></div>
    <div class="card__body">
        <div class="form-grid">
            <div class="field">
                <label class="field__label" for="employee_code">ID Karyawan</label>
                <input class="input" id="employee_code" name="employee_code" value="<?= $value('employee_code') ?>" placeholder="EMP-006" required>
            </div>
            <div class="field">
                <label class="field__label" for="name">Nama Lengkap</label>
                <input class="input" id="name" name="name" value="<?= $value('name') ?>" placeholder="Nama karyawan" required>
            </div>
            <div class="field">
                <label class="field__label" for="email">Email / Username</label>
                <input class="input" id="email" name="email" type="email" value="<?= $value('email') ?>" placeholder="nama@umkm.test" required>
            </div>
            <div class="field">
                <label class="field__label" for="position">Jabatan</label>
                <input class="input" id="position" name="position" value="<?= $value('position') ?>" placeholder="Mis. Kasir, Produksi">
            </div>
            <div class="field">
                <label class="field__label" for="role">Role</label>
                <select class="select" id="role" name="role" required>
                    <?php $currentRole = old('role', $employee['role'] ?? 'employee'); ?>
                    <option value="employee" <?= $currentRole === 'employee' ? 'selected' : '' ?>>Karyawan</option>
                    <option value="owner" <?= $currentRole === 'owner' ? 'selected' : '' ?>>Owner</option>
                </select>
            </div>
            <div class="field">
                <label class="field__label" for="password">Kata Sandi <?= $editing ? '(kosongkan jika tidak diubah)' : '' ?></label>
                <input class="input" id="password" name="password" type="password" minlength="6" placeholder="Minimal 6 karakter" <?= $editing ? '' : 'required' ?>>
            </div>
            <div class="field field--full">
                <label class="checkbox">
                    <input type="checkbox" name="is_active" value="1" <?= (int) old('is_active', $employee['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                    <span>Akun aktif (karyawan dapat login)</span>
                </label>
            </div>
        </div>
    </div>
    <div class="card__footer form-actions">
        <button class="btn btn--primary" type="submit"><?= $editing ? 'Simpan perubahan' : 'Tambah karyawan' ?></button>
        <a class="btn btn--ghost" href="<?= e(url('employees')) ?>">Batal</a>
    </div>
</form>
