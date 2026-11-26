<?php
/**
 * Pembaca file .env sederhana (tanpa dependency composer).
 */

function loadEnv(string $path): array
{
    $vars = [];
    if (!is_file($path)) {
        return $vars;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        $first = $value[0] ?? '';
        $last = substr($value, -1);
        if (strlen($value) >= 2 && (($first === '"' && $last === '"') || ($first === "'" && $last === "'"))) {
            $value = substr($value, 1, -1);
        }

        $vars[$key] = $value;
    }

    return $vars;
}

function env(string $key, mixed $default = null): mixed
{
    static $vars = null;
    if ($vars === null) {
        $vars = loadEnv(dirname(__DIR__) . '/.env');
    }

    return array_key_exists($key, $vars) ? $vars[$key] : $default;
}
