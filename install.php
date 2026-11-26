<?php
/**
 * Installer skema + seed. Akses: /absen/install.php
 * Hapus file ini setelah instalasi selesai.
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/src/Support.php';

date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Jakarta'));

/**
 * Pisahkan statement SQL dengan memperhatikan quote dan komentar.
 */
function splitSql(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $quote = null;
    $lineComment = false;
    $blockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        if ($lineComment) {
            if ($char === "\n") {
                $lineComment = false;
                $buffer .= "\n";
            }
            continue;
        }
        if ($blockComment) {
            if ($char === '*' && $next === '/') {
                $blockComment = false;
                $i++;
            }
            continue;
        }
        if ($quote !== null) {
            $buffer .= $char;
            if ($char === '\\' && $next !== '') {
                $buffer .= $next;
                $i++;
            } elseif ($char === $quote) {
                $quote = null;
            }
            continue;
        }
        if ($char === '-' && $next === '-') {
            $lineComment = true;
            $i++;
            continue;
        }
        if ($char === '#') {
            $lineComment = true;
            continue;
        }
        if ($char === '/' && $next === '*') {
            $blockComment = true;
            $i++;
            continue;
        }
        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $buffer .= $char;
            continue;
        }
        if ($char === ';') {
            if (trim($buffer) !== '') {
                $statements[] = trim($buffer);
            }
            $buffer = '';
            continue;
        }
        $buffer .= $char;
    }
    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }
    return $statements;
}

$results = [];
$error = null;
$done = false;

if (isPostRequest()) {
    try {
        $host = (string) env('DB_HOST', '127.0.0.1');
        $port = (string) env('DB_PORT', '3306');
        $database = (string) env('DB_DATABASE', 'umkm_monitoring_absensi');
        $user = (string) env('DB_USERNAME', 'root');
        $password = (string) env('DB_PASSWORD', '');

        $bootstrap = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $bootstrap->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // 1) Buat tabel jika belum ada. Tidak pernah menghapus/mengubah tabel lama.
        $path = __DIR__ . '/sql/schema.sql';
        if (!is_file($path)) {
            throw new RuntimeException('File sql/schema.sql tidak ditemukan.');
        }
        $statements = splitSql((string) file_get_contents($path));
        $count = 0;
        foreach ($statements as $statement) {
            $pdo->exec($statement);
            $count++;
        }
        $results[] = "sql/schema.sql: $count statement berhasil dijalankan (tabel yang sudah ada dibiarkan).";

        // 2) Isi data contoh HANYA jika tabel users masih kosong.
        //    Ini mencegah penimpaan data asli milik user.
        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($userCount > 0) {
            $results[] = "sql/seed.sql: DILEWATI karena tabel users sudah berisi $userCount akun. Data asli Anda tidak diubah.";
        } else {
            $path = __DIR__ . '/sql/seed.sql';
            if (!is_file($path)) {
                throw new RuntimeException('File sql/seed.sql tidak ditemukan.');
            }
            $statements = splitSql((string) file_get_contents($path));
            $count = 0;
            foreach ($statements as $statement) {
                $pdo->exec($statement);
                $count++;
            }
            $results[] = "sql/seed.sql: $count statement berhasil dijalankan (data contoh ditambahkan).";
        }
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

function isPostRequest(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalasi Database · Monitoring Absensi UMKM</title>
    <link rel="stylesheet" href="public/assets/css/app.css">
</head>
<body class="auth-page">
<main class="auth">
    <div class="auth__card">
        <div class="auth__brand"><span class="sidebar__brand-mark">UM</span><span>Instalasi database</span></div>
        <h1 class="auth__title">Siapkan tabel &amp; data awal</h1>
        <p class="auth__subtitle">
            Database: <code><?= e(env('DB_DATABASE', 'umkm_monitoring_absensi')) ?></code> di
            <code><?= e(env('DB_HOST', '127.0.0.1')) ?>:<?= e(env('DB_PORT', '3306')) ?></code>
        </p>
        <div class="alert alert--info">
            Halaman ini hanya untuk <strong>pemasangan awal</strong>. Tombol di bawah akan membuat tabel
            jika belum ada, lalu mengisi data contoh <strong>hanya jika belum ada akun sama sekali</strong>.
            Tabel dan data yang sudah ada tidak akan ditimpa.
        </div>

        <?php if ($error) : ?>
            <div class="alert alert--error"><?= e($error) ?></div>
            <p class="muted small">Pastikan MySQL berjalan dan kredensial di <code>.env</code> sudah benar.</p>
        <?php endif; ?>

        <?php if ($done) : ?>
            <?php foreach ($results as $result) : ?><div class="alert alert--success"><?= e($result) ?></div><?php endforeach; ?>
            <div class="alert alert--warning">Instalasi selesai. Hapus file <code>install.php</code> demi keamanan.</div>
            <a class="btn btn--primary btn--block" href="index.php?page=login">Lanjut ke halaman login</a>
        <?php else : ?>
            <form method="post" class="form stack">
                <div class="alert alert--warning">Jalankan sekali saja. Jika database Anda sudah berisi akun dari MySQL Workbench, tidak perlu menekan tombol ini — cukup perbaiki file <code>.env</code> lalu login dengan akun Anda sendiri.</div>
                <button class="btn btn--primary btn--block" type="submit">Jalankan instalasi</button>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
