<?php
/**
 * Validator kecil: mengembalikan null jika valid, atau string pesan error.
 */

function v_required(mixed $value, string $label): ?string
{
    if ($value === null || trim((string) $value) === '') {
        return "$label wajib diisi.";
    }
    return null;
}

function v_email(mixed $value, string $label = 'Email'): ?string
{
    if ($value === null || trim((string) $value) === '') {
        return "$label wajib diisi.";
    }
    return filter_var((string) $value, FILTER_VALIDATE_EMAIL) ? null : "$label tidak valid.";
}

function v_date(mixed $value, string $label = 'Tanggal'): ?string
{
    if ($value === null || trim((string) $value) === '') {
        return "$label wajib diisi.";
    }
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$parsed || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return "$label tidak valid.";
    }
    return null;
}

function v_time(mixed $value, string $label = 'Jam'): ?string
{
    if ($value === null || trim((string) $value) === '') {
        return "$label wajib diisi.";
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string) $value)) {
        return "$label tidak valid (format HH:MM).";
    }
    return null;
}

function v_int(mixed $value, int $min, int $max, string $label): ?string
{
    if ($value === null || !is_numeric($value)) {
        return "$label wajib berupa angka.";
    }
    $number = (int) $value;
    if ($number < $min || $number > $max) {
        return "$label harus antara $min sampai $max.";
    }
    return null;
}

function v_min_len(mixed $value, int $min, string $label): ?string
{
    if ($value === null || strlen((string) $value) < $min) {
        return "$label minimal $min karakter.";
    }
    return null;
}

function v_in(mixed $value, array $allowed, string $label): ?string
{
    if (!in_array((string) $value, $allowed, true)) {
        return "Pilihan $label tidak valid.";
    }
    return null;
}

function firstError(array $errors): ?string
{
    foreach ($errors as $error) {
        if ($error !== null && $error !== '') {
            return $error;
        }
    }
    return null;
}
