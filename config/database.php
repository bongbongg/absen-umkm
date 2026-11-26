<?php
/**
 * Koneksi PDO MySQL. Konfigurasi dibaca dari file .env.
 */

function database(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        env('DB_HOST', '127.0.0.1'),
        env('DB_PORT', '3306'),
        env('DB_DATABASE', 'umkm_monitoring_absensi')
    );

    try {
        $pdo = new PDO($dsn, (string) env('DB_USERNAME', 'root'), (string) env('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException(
            'Tidak dapat terhubung ke MySQL. Periksa DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, dan DB_PASSWORD di file .env. Detail: ' . $e->getMessage(),
            0,
            $e
        );
    }

    return $pdo;
}
