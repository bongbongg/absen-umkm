<div class="page__header">
    <div>
        <h1 class="page__title">Data Karyawan</h1>
        <p class="page__subtitle">Kelola akun, jabatan, peran, dan status aktif karyawan.</p>
    </div>
    <div class="page__actions">
        <a class="btn btn--primary" href="<?= e(url('employees', ['action' => 'create'])) ?>">+ Tambah karyawan</a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <div class="card__title">Daftar akun</div>
        <div class="small muted">Total: <?= count($users) ?> akun</div>
    </div>
    <div class="card__body">
        <form method="get" action="<?= e(url('employees')) ?>" class="filters">
            <input type="hidden" name="page" value="employees">
            <div class="filters__grid">
                <div class="field">
                    <label class="field__label" for="q">Cari</label>
                    <input class="input" id="q" name="q" value="<?= e($search) ?>" placeholder="Nama, ID, email, atau jabatan">
                </div>
                <div class="field">
                    <label class="field__label" for="role">Role</label>
                    <select class="select" id="role" name="role" data-auto-submit>
                        <option value="">Semua role</option>
                        <option value="owner" <?= $roleFilter === 'owner' ? 'selected' : '' ?>>Owner</option>
                        <option value="employee" <?= $roleFilter === 'employee' ? 'selected' : '' ?>>Karyawan</option>
                    </select>
                </div>
                <div class="filters__actions">
                    <button class="btn btn--secondary" type="submit">Terapkan</button>
                    <a class="btn btn--ghost" href="<?= e(url('employees')) ?>">Reset</a>
                </div>
            </div>
        </form>

        <?php if (!$users) : ?>
            <div class="empty"><div class="empty__icon">☰</div><div class="empty__title">Tidak ada data</div><div class="empty__text">Coba ubah kata kunci pencarian.</div></div>
        <?php else : ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr><th>ID</th><th>Nama</th><th>Email / Username</th><th>Jabatan</th><th>Role</th><th>Status</th><th>Data</th><th class="text-right">Aksi</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u) : ?>
                        <tr>
                            <td class="nowrap"><strong><?= e($u['employee_code']) ?></strong></td>
                            <td><?= e($u['name']) ?></td>
                            <td class="small"><?= e($u['email']) ?></td>
                            <td><?= e($u['position'] ?? '-') ?></td>
                            <td><?= roleBadge($u['role']) ?></td>
                            <td>
                                <span class="badge badge--<?= (int) $u['is_active'] === 1 ? 'success' : 'muted' ?>">
                                    <?= (int) $u['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="small muted nowrap"><?= (int) $u['schedule_count'] ?> jadwal · <?= (int) $u['task_count'] ?> tugas</td>
                            <td class="text-right">
                                <div class="btn-group nowrap">
                                    <a class="btn btn--ghost btn--sm" href="<?= e(url('employees', ['action' => 'edit', 'id' => $u['id']])) ?>">Ubah</a>
                                    <form method="post" action="<?= e(url('employees', ['action' => 'toggle'])) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button class="btn btn--ghost btn--sm" type="submit" data-confirm="<?= (int) $u['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?> akun <?= e($u['name']) ?>?">
                                            <?= (int) $u['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                    <form method="post" action="<?= e(url('employees', ['action' => 'delete'])) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button class="btn btn--danger btn--sm" type="submit" data-confirm="Hapus permanen akun <?= e($u['name']) ?>?">Hapus</button>
                                    </form>
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
