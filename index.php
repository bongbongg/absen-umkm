<?php
/**
 * Front controller. Semua halaman diakses lewat index.php?page=...
 * Menghindari kebutuhan rewrite URL di IIS/XAMPP.
 */

require_once __DIR__ . '/config/bootstrap.php';

$routes = [
    'login' => ['controller' => 'AuthController', 'guest' => true, 'actions' => ['index', 'login', 'logout']],
    'wireframe' => ['controller' => 'WireframeController', 'public' => true, 'actions' => ['index']],
    'dashboard' => ['controller' => 'DashboardController', 'actions' => ['index']],
    'employees' => ['controller' => 'EmployeeController', 'roles' => ['owner'], 'actions' => ['index', 'create', 'store', 'edit', 'update', 'toggle', 'delete']],
    'schedules' => ['controller' => 'ScheduleController', 'actions' => ['index', 'create', 'store', 'edit', 'update', 'delete']],
    'attendance' => ['controller' => 'AttendanceController', 'actions' => ['index', 'checkin', 'checkout']],
    'history' => ['controller' => 'AttendanceController', 'default' => 'history', 'actions' => ['index', 'history']],
    'corrections' => ['controller' => 'CorrectionController', 'actions' => ['index', 'create', 'store', 'approve', 'reject']],
    'tasks' => ['controller' => 'TaskController', 'actions' => ['index', 'create', 'store', 'edit', 'update', 'delete']],
    'task' => ['controller' => 'TaskController', 'default' => 'show', 'actions' => ['show', 'progress', 'evidence', 'submit', 'approve', 'revision']],
    'reports' => ['controller' => 'ReportController', 'roles' => ['owner'], 'actions' => ['index', 'export']],
];

$page = (string) query('page', 'login');

if (!isset($routes[$page])) {
    http_response_code(404);
    view('errors/404', ['title' => 'Halaman tidak ditemukan']);
    exit;
}

$route = $routes[$page];
$action = (string) (post('action', query('action', $route['default'] ?? 'index')) ?? 'index');

if (!empty($route['public'])) {
    // Halaman dokumentasi wireframe boleh dibuka tanpa login.
} elseif (!empty($route['guest'])) {
    // Aksi logout harus tetap bisa dipanggil walau user sedang login.
    if (currentUser() && $action !== 'logout') {
        redirect('dashboard');
    }
} else {
    requireLogin();
    if (isset($route['roles']) && !in_array(currentUser()['role'], $route['roles'], true)) {
        http_response_code(403);
        view('errors/403', ['title' => 'Akses ditolak']);
        exit;
    }
}

if (!in_array($action, $route['actions'], true) || !method_exists($route['controller'], $action)) {
    http_response_code(404);
    view('errors/404', ['title' => 'Aksi tidak ditemukan']);
    exit;
}

if (isPost()) {
    verifyCsrf();
}

try {
    $route['controller']::$action();
} catch (Throwable $e) {
    reportError($e);
    $message = friendlyError($e);
    if (isPost()) {
        flash('error', $message);
        redirect($page === 'task' ? 'tasks' : $page);
    }
    http_response_code(500);
    view('errors/fatal', ['title' => 'Terjadi kesalahan', 'message' => $message]);
}
