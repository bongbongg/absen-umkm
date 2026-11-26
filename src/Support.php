<?php
/**
 * Fungsi bantu tampilan, routing, session, dan keamanan dasar.
 */

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function baseUrl(): string
{
    return rtrim((string) env('APP_BASE_URL', '/absen/'), '/') . '/';
}

function url(string $page = 'dashboard', array $params = []): string
{
    return baseUrl() . 'index.php?' . http_build_query(array_merge(['page' => $page], $params));
}

function asset(string $path): string
{
    return baseUrl() . 'public/assets/' . ltrim($path, '/');
}

function redirect(string $page = 'dashboard', array $params = []): never
{
    header('Location: ' . url($page, $params));
    exit;
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function requirePost(): void
{
    if (!isPost()) {
        http_response_code(405);
        redirect('dashboard');
    }
}

function post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

function query(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

/** Mode wireframe disimpan di session agar tetap aktif setelah submit form. */
function wireframeEnabled(): bool
{
    return ($_SESSION['wireframe'] ?? false) === true;
}

/** Halaman preview wireframe selalu memakai tampilan lo-fi. */
function wireframeMode(): bool
{
    return wireframeEnabled() || (string) query('page', '') === 'wireframe';
}

/** URL untuk menyalakan/memadamkan mode wireframe di halaman saat ini. */
function wireframeToggleUrl(): string
{
    $params = $_GET;
    unset($params['wireframe']);
    $page = (string) ($params['page'] ?? 'dashboard');
    unset($params['page']);
    $params['wireframe'] = wireframeEnabled() ? '0' : '1';
    return url($page, $params);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verifyCsrf(): void
{
    $token = (string) post('csrf_token', '');
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    if ($sessionToken === '' || $token === '' || !hash_equals($sessionToken, $token)) {
        flash('error', 'Sesi keamanan berakhir. Silakan coba lagi.');
        http_response_code(419);
        redirect(currentUser() ? 'dashboard' : 'login');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consumeFlashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function rememberInput(array $data): void
{
    $_SESSION['old'] = $data;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old'][$key] ?? $default;
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isOwner(): bool
{
    return currentUser()['role'] === 'owner';
}

function isEmployee(): bool
{
    return currentUser()['role'] === 'employee';
}

function requireLogin(): void
{
    if (!currentUser()) {
        flash('warning', 'Silakan login terlebih dahulu.');
        redirect('login');
    }
}

function requireRole(string ...$roles): void
{
    requireLogin();
    if (!in_array(currentUser()['role'], $roles, true)) {
        http_response_code(403);
        view('errors/403', ['title' => 'Akses ditolak']);
        exit;
    }
}

function formatDate(mixed $value, bool $withTime = false): string
{
    if (!$value) {
        return '-';
    }
    try {
        $date = new DateTimeImmutable((string) $value);
        return $date->format($withTime ? 'd M Y, H:i' : 'd M Y');
    } catch (Exception) {
        return '-';
    }
}

function timeOnly(mixed $value): string
{
    if (!$value) {
        return '-';
    }
    $text = (string) $value;
    if (str_contains($text, ' ')) {
        return substr($text, strpos($text, ' ') + 1, 5);
    }
    return substr($text, 0, 5);
}

function strLimit(mixed $value, int $length = 60): string
{
    $text = trim((string) ($value ?? ''));
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
}

function statusBadge(string $status): string
{
    $map = [
        'hadir' => ['success', 'Hadir'],
        'terlambat' => ['warning', 'Terlambat'],
        'alpha' => ['danger', 'Alpha'],
        'pending' => ['warning', 'Menunggu'],
        'approved' => ['success', 'Disetujui'],
        'rejected' => ['danger', 'Ditolak'],
        'todo' => ['muted', 'Belum dimulai'],
        'assigned' => ['muted', 'Belum dimulai'],
        'in_progress' => ['info', 'Berjalan'],
        'submitted' => ['primary', 'Menunggu persetujuan'],
        'revision' => ['warning', 'Perlu revisi'],
        'completed' => ['success', 'Selesai'],
    ];
    [$class, $label] = $map[$status] ?? ['muted', ucfirst(str_replace('_', ' ', $status))];
    return '<span class="badge badge--' . $class . '">' . e($label) . '</span>';
}

function roleBadge(string $role): string
{
    $label = $role === 'owner' ? 'Owner' : 'Karyawan';
    $class = $role === 'owner' ? 'primary' : 'muted';
    return '<span class="badge badge--' . $class . '">' . e($label) . '</span>';
}

function view(string $name, array $data = [], string $layout = 'layout'): void
{
    $title = $data['title'] ?? env('APP_NAME', 'Monitoring Absensi UMKM');
    $activePage = $data['activePage'] ?? '';
    $flashes = consumeFlashes();

    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/../views/' . $name . '.php';
    $content = ob_get_clean();

    if ($layout === 'auth') {
        require __DIR__ . '/../views/auth_layout.php';
    } elseif ($layout === 'wireframe') {
        require __DIR__ . '/../views/wireframe_layout.php';
    } else {
        require __DIR__ . '/../views/layout.php';
    }
    unset($_SESSION['old']);
}

function uploadMaxBytes(): int
{
    return (int) env('UPLOAD_MAX_BYTES', 5242880);
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}
