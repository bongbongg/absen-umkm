<?php
/**
 * Autentikasi berbasis session: hash password, akun aktif, dan throttle login.
 */

function loadAuthenticatedUser(): void
{
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $user = db_one('SELECT id, employee_code, name, email, role, position, status FROM users WHERE id = ?', [$_SESSION['user_id']]);
    if (!$user || $user['status'] !== 'active') {
        unset($_SESSION['user_id'], $_SESSION['user']);
        flash('warning', 'Akun Anda tidak aktif. Hubungi owner untuk mengaktifkan kembali.');
        return;
    }

    $_SESSION['user'] = $user;
}

function loginThrottleRemaining(): int
{
    // Mode lokal dipakai untuk development: jangan mengunci akun setelah
    // beberapa kali salah, agar tidak perlu menunggu 5 menit saat mencoba.
    if (env('APP_ENV', 'local') === 'local') {
        unset($_SESSION['login_attempts']);
        return 5;
    }

    $attempt = $_SESSION['login_attempts'] ?? null;
    if (!$attempt || ($attempt['expires'] ?? 0) < time()) {
        unset($_SESSION['login_attempts']);
        return 5;
    }
    return max(0, 5 - (int) ($attempt['count'] ?? 0));
}

function registerFailedLogin(): void
{
    if (env('APP_ENV', 'local') === 'local') {
        return;
    }

    $attempt = $_SESSION['login_attempts'] ?? ['count' => 0, 'expires' => time() + 300];
    if (($attempt['expires'] ?? 0) < time()) {
        $attempt = ['count' => 0, 'expires' => time() + 300];
    }
    $attempt['count'] = (int) $attempt['count'] + 1;
    $_SESSION['login_attempts'] = $attempt;
}

function clearLoginAttempts(): void
{
    unset($_SESSION['login_attempts']);
}

function attemptLogin(string $email, string $password): array
{
    if (loginThrottleRemaining() === 0) {
        return [false, 'Terlalu banyak percobaan login. Tunggu beberapa menit sebelum mencoba lagi.'];
    }

    $user = db_one(
        'SELECT id, employee_code, name, email, password_hash, role, position, status FROM users WHERE email = ? LIMIT 1',
        [trim($email)]
    );

    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
        registerFailedLogin();
        $remaining = loginThrottleRemaining();
        return [false, $remaining > 0 ? "Email atau kata sandi salah. Sisa percobaan: $remaining." : 'Terlalu banyak percobaan login. Tunggu beberapa menit.'];
    }

    clearLoginAttempts();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'employee_code' => $user['employee_code'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'position' => $user['position'],
    ];

    return [true, 'Selamat datang, ' . $user['name'] . '.'];
}

function logoutUser(): void
{
    // Hapus semua data login, lalu ganti id session. Session sengaja tidak
    // di-destroy supaya pesan "telah keluar" bisa disimpan sekali pakai
    // dan halaman login kembali bersih setelah di-refresh.
    $_SESSION = [];
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }
}
