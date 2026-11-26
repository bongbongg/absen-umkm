<?php
/**
 * Bootstrap aplikasi: env, timezone, session, helper, dan controller.
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/database.php';

date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Jakarta'));

$secure = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';

// Di IIS lokal, use_strict_mode=1 membuat session id selalu berganti
// (session tidak tersimpan), sehingga login langsung kembali ke halaman login.
// Login sudah memakai session_regenerate_id(), jadi aman menonaktifkannya.
ini_set('session.use_strict_mode', '0');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_name('absen_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => rtrim((string) env('APP_BASE_URL', '/absen/'), '/') . '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (isset($_GET['wireframe'])) {
    $_SESSION['wireframe'] = (string) $_GET['wireframe'] === '1';
}

require_once __DIR__ . '/../src/Db.php';
require_once __DIR__ . '/../src/Errors.php';
require_once __DIR__ . '/../src/Validation.php';
require_once __DIR__ . '/../src/Support.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Pdf.php';

foreach (glob(__DIR__ . '/../src/controllers/*.php') as $file) {
    require_once $file;
}

loadAuthenticatedUser();
