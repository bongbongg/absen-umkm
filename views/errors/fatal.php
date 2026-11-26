<div class="card">
    <div class="card__body stack">
        <h1 class="page__title">Terjadi kesalahan</h1>
        <div class="alert alert--error"><?= e($message ?? 'Terjadi kesalahan yang tidak terduga.') ?></div>
        <p class="muted small">Periksa konfigurasi database di file <code>.env</code> dan pastikan skema serta seed sudah dijalankan.</p>
        <div><a class="btn btn--secondary" href="<?= e(url('dashboard')) ?>">Coba lagi</a></div>
    </div>
</div>
