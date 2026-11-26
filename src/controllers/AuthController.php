<?php

final class AuthController
{
    public static function index(): void
    {
        view('login', ['title' => 'Login'], 'auth');
    }

    public static function login(): void
    {
        requirePost();

        $email = (string) post('email', '');
        $password = (string) post('password', '');

        $error = firstError([
            v_required($email, 'Email/username'),
            v_required($password, 'Kata sandi'),
        ]);

        if ($error) {
            flash('error', $error);
            rememberInput(['email' => $email]);
            redirect('login');
        }

        [$success, $message] = attemptLogin($email, $password);
        if (!$success) {
            flash('error', $message);
            rememberInput(['email' => $email]);
            redirect('login');
        }

        flash('success', $message);
        redirect('dashboard');
    }

    public static function logout(): void
    {
        requirePost();
        logoutUser();

        // Pesan sekali pakai: tampil sekali setelah logout, hilang saat refresh.
        // URL juga kembali ke ?page=login tanpa query status=logout.
        flash('info', 'Anda telah keluar dari akun. Sampai jumpa!');
        redirect('login');
    }
}
