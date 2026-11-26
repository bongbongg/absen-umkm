<?php $wf = wireframeMode(); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(env('APP_NAME', 'Monitoring Absensi UMKM')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <?php if ($wf) : ?><link rel="stylesheet" href="<?= e(asset('css/wireframe.css')) ?>"><?php endif; ?>
</head>
<body class="auth-page<?= $wf ? ' wireframe' : '' ?>">
<main class="auth">
    <div class="auth__card">
        <?= $content ?>
    </div>
    <div class="auth__footer">
        <div class="row" style="justify-content:center;gap:10px">
            <a class="btn btn--ghost btn--sm wf-toggle <?= wireframeEnabled() ? '' : 'wf-toggle--off' ?>" href="<?= e(wireframeToggleUrl()) ?>">
                Lo-Fi: <?= wireframeEnabled() ? 'ON' : 'OFF' ?>
            </a>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('wireframe')) ?>">Wireframe Preview</a>
        </div>
        <div style="margin-top:8px">© <?= date('Y') ?> <?= e(env('APP_NAME', 'Monitoring Absensi UMKM')) ?></div>
    </div>
</main>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
