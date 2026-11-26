<?php
/**
 * Terjemahkan error MySQL menjadi pesan yang ramah pengguna.
 */

function friendlyError(Throwable $e): string
{
    if ($e instanceof PDOException) {
        $driverCode = $e->errorInfo[1] ?? null;
        $sqlState = $e->getCode();
        $message = $e->getMessage();

        if ($driverCode === 1062 || ($sqlState === '23000' && stripos($message, 'duplicate') !== false)) {
            if (str_contains($message, 'uq_attendance_employee_schedule')) {
                return 'Karyawan ini sudah tercatat absen pada jadwal yang sama.';
            }
            if (str_contains($message, 'uq_schedule_user_date') || str_contains($message, 'uq_employee_work_date')) {
                return 'Jadwal kerja untuk karyawan dan tanggal tersebut sudah ada.';
            }
            if (str_contains($message, "'email'") || str_contains($message, 'users.email')) {
                return 'Email/username tersebut sudah digunakan akun lain.';
            }
            if (str_contains($message, "'employee_code'") || str_contains($message, 'users.employee_code')) {
                return 'ID karyawan tersebut sudah digunakan.';
            }
            return 'Data dengan nilai yang sama sudah ada, sehingga tidak dapat disimpan ulang.';
        }

        if (in_array($driverCode, [3819, 4025], true) || stripos($message, 'check constraint') !== false) {
            if (stripos($message, 'progress') !== false || stripos($message, 'chk_task_progress') !== false) {
                return 'Persentase progres harus berada di rentang 0 sampai 100.';
            }
            if (stripos($message, 'schedule') !== false || stripos($message, 'chk_schedule_time') !== false) {
                return 'Jam pulang harus lebih akhir daripada jam masuk.';
            }
            return 'Data yang dimasukkan tidak memenuhi aturan yang berlaku (periksa jam, tanggal, dan nilai progres).';
        }

        if ($driverCode === 1451) {
            return 'Data ini masih digunakan oleh data lain, sehingga tidak dapat dihapus. Nonaktifkan saja jika perlu.';
        }
        if ($driverCode === 1452) {
            return 'Data terkait tidak ditemukan. Periksa kembali pilihan karyawan atau jadwal.';
        }
        if (in_array($driverCode, [1045, 1049, 2002, 2003], true)) {
            return 'Koneksi database gagal. Periksa konfigurasi MySQL di file .env.';
        }
    }

    if (env('APP_DEBUG', 'false') === 'true') {
        return 'Terjadi kesalahan: ' . $e->getMessage();
    }

    error_log('[absen] ' . $e->getMessage());
    return 'Terjadi kesalahan saat memproses data. Silakan coba lagi.';
}

function reportError(Throwable $e): void
{
    error_log('[absen] ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
}
