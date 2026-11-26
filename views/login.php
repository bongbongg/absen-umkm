<div class="auth__brand">
    <span class="sidebar__brand-mark">UM</span>
    <span><?= e(env('APP_NAME', 'Monitoring Absensi UMKM')) ?></span>
</div>
<h1 class="auth__title">Masuk ke sistem</h1>
<p class="auth__subtitle">Pantau absensi dan progres pekerjaan karyawan dalam satu tempat.</p>

<?php foreach ($flashes as $flash) : ?>
    <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endforeach; ?>

<form method="post" action="<?= e(url('login', ['action' => 'login'])) ?>" class="form stack" data-fr="FR-01 / FR-02">
    <?= csrf_field() ?>
    <div class="field">
        <label class="field__label" for="email">Email / Username</label>
        <input class="input" id="email" name="email" type="email" autocomplete="username"
               value="<?= e(old('email')) ?>" placeholder="nama@umkm.local" required autofocus>
    </div>
    <div class="field">
        <label class="field__label" for="password">Kata Sandi</label>
        <input class="input" id="password" name="password" type="password" autocomplete="current-password"
               placeholder="Masukkan kata sandi" required>
    </div>
    <button class="btn btn--primary btn--block" type="submit">Masuk</button>
</form>

<div class="divider"></div>
<p class="auth__hint">Gunakan email dan kata sandi akun yang sudah terdaftar di database Anda.</p>
