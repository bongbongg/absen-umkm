<div class="page__header">
    <div>
        <h1 class="page__title">Ajukan Koreksi Absensi</h1>
        <p class="page__subtitle">Perbaiki waktu check-in/check-out yang tercatat keliru.</p>
    </div>
    <div class="page__actions"><a class="btn btn--secondary" href="<?= e(url('corrections')) ?>">← Kembali</a></div>
</div>

<form method="post" action="<?= e(url('corrections', ['action' => 'store'])) ?>" class="form card" data-fr="FR-08">
    <?= csrf_field() ?>
    <div class="card__header">
        <div class="card__title">Detail pengajuan</div>
        <div class="card__subtitle">Data absensi baru akan berlaku setelah disetujui owner.</div>
    </div>
    <div class="card__body">
        <div class="form-grid">
            <div class="field">
                <label class="field__label" for="correction_date">Tanggal yang dikoreksi</label>
                <input class="input" id="correction_date" name="correction_date" type="date"
                       value="<?= e(old('correction_date', date('Y-m-d'))) ?>" max="<?= date('Y-m-d') ?>" required>
                <div class="field__hint">Pastikan ada jadwal kerja pada tanggal ini.</div>
            </div>
            <div class="field">
                <label class="field__label" for="requested_check_in">Jam masuk yang diajukan</label>
                <input class="input" id="requested_check_in" name="requested_check_in" type="time"
                       value="<?= e(old('requested_check_in')) ?>">
            </div>
            <div class="field">
                <label class="field__label" for="requested_check_out">Jam pulang yang diajukan</label>
                <input class="input" id="requested_check_out" name="requested_check_out" type="time"
                       value="<?= e(old('requested_check_out')) ?>">
            </div>
            <div class="field field--full">
                <label class="field__label" for="reason">Alasan koreksi</label>
                <textarea class="textarea" id="reason" name="reason" rows="4"
                          placeholder="Contoh: lupa menekan tombol check-in karena sedang menangani pelanggan" required><?= e(old('reason')) ?></textarea>
                <div class="field__hint">Minimal satu jam harus diisi dan sertakan alasan yang jelas.</div>
            </div>
        </div>
    </div>
    <div class="card__footer form-actions">
        <button class="btn btn--primary" type="submit">Kirim pengajuan</button>
        <a class="btn btn--ghost" href="<?= e(url('corrections')) ?>">Batal</a>
    </div>
</form>
