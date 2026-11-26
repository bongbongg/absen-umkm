<?php

final class EmployeeController
{
    public static function index(): void
    {
        requireRole('owner');

        $search = trim((string) query('q', ''));
        $role = (string) query('role', '');
        $params = [];
        $where = ['1 = 1'];

        if ($search !== '') {
            $where[] = '(name LIKE ? OR employee_code LIKE ? OR email LIKE ? OR position LIKE ?)';
            $like = "%$search%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        if (in_array($role, ['owner', 'employee'], true)) {
            $where[] = 'role = ?';
            $params[] = $role;
        }

        $users = db_all(
            "SELECT u.*,
                CASE WHEN u.status = 'active' THEN 1 ELSE 0 END AS is_active,
                (SELECT COUNT(*) FROM work_schedules s WHERE s.employee_id = u.id) AS schedule_count,
                (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = u.id) AS task_count
             FROM users u WHERE " . implode(' AND ', $where) . ' ORDER BY u.role DESC, u.employee_code ASC',
            $params
        );

        view('employees', [
            'title' => 'Data Karyawan',
            'activePage' => 'employees',
            'users' => $users,
            'search' => $search,
            'roleFilter' => $role,
        ]);
    }

    public static function create(): void
    {
        requireRole('owner');
        view('employee_form', [
            'title' => 'Tambah Karyawan',
            'activePage' => 'employees',
            'employee' => null,
        ]);
    }

    public static function store(): void
    {
        requireRole('owner');
        requirePost();

        $data = self::formData();
        $error = firstError([
            v_required($data['employee_code'], 'ID karyawan'),
            v_required($data['name'], 'Nama'),
            v_email($data['email'], 'Email/username'),
            v_min_len($data['password'], 6, 'Kata sandi'),
            v_in($data['role'], ['owner', 'employee'], 'Role'),
        ]);

        if ($error) {
            flash('error', $error);
            rememberInput($data);
            redirect('employees', ['action' => 'create']);
        }

        try {
            db_run(
                'INSERT INTO users (employee_code, name, email, password_hash, role, position, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    strtoupper(trim($data['employee_code'])),
                    trim($data['name']),
                    strtolower(trim($data['email'])),
                    password_hash($data['password'], PASSWORD_DEFAULT),
                    $data['role'],
                    trim((string) $data['position']),
                    $data['is_active'] ? 'active' : 'inactive',
                ]
            );
            flash('success', 'Karyawan berhasil ditambahkan.');
            redirect('employees');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
            rememberInput($data);
            redirect('employees', ['action' => 'create']);
        }
    }

    public static function edit(): void
    {
        requireRole('owner');
        $employee = self::findEmployee();
        view('employee_form', [
            'title' => 'Ubah Karyawan',
            'activePage' => 'employees',
            'employee' => $employee,
        ]);
    }

    public static function update(): void
    {
        requireRole('owner');
        requirePost();

        $employee = self::findEmployee();
        $data = self::formData();

        $errors = [
            v_required($data['employee_code'], 'ID karyawan'),
            v_required($data['name'], 'Nama'),
            v_email($data['email'], 'Email/username'),
            v_in($data['role'], ['owner', 'employee'], 'Role'),
        ];
        if ($data['password'] !== '') {
            $errors[] = v_min_len($data['password'], 6, 'Kata sandi');
        }
        if ((int) $employee['id'] === (int) currentUser()['id'] && (int) $data['is_active'] !== 1) {
            $errors[] = 'Anda tidak dapat menonaktifkan akun sendiri.';
        }
        if ($error = firstError($errors)) {
            flash('error', $error);
            rememberInput($data);
            redirect('employees', ['action' => 'edit', 'id' => $employee['id']]);
        }

        try {
            if ($data['password'] !== '') {
                db_run(
                    'UPDATE users SET employee_code = ?, name = ?, email = ?, password_hash = ?, role = ?, position = ?, status = ? WHERE id = ?',
                    [
                        strtoupper(trim($data['employee_code'])),
                        trim($data['name']),
                        strtolower(trim($data['email'])),
                        password_hash($data['password'], PASSWORD_DEFAULT),
                        $data['role'],
                        trim((string) $data['position']),
                        $data['is_active'] ? 'active' : 'inactive',
                        $employee['id'],
                    ]
                );
            } else {
                db_run(
                    'UPDATE users SET employee_code = ?, name = ?, email = ?, role = ?, position = ?, status = ? WHERE id = ?',
                    [
                        strtoupper(trim($data['employee_code'])),
                        trim($data['name']),
                        strtolower(trim($data['email'])),
                        $data['role'],
                        trim((string) $data['position']),
                        $data['is_active'] ? 'active' : 'inactive',
                        $employee['id'],
                    ]
                );
            }
            if ((int) $employee['id'] === (int) currentUser()['id']) {
                $_SESSION['user']['name'] = trim($data['name']);
                $_SESSION['user']['role'] = $data['role'];
            }
            flash('success', 'Data karyawan berhasil diperbarui.');
            redirect('employees');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
            rememberInput($data);
            redirect('employees', ['action' => 'edit', 'id' => $employee['id']]);
        }
    }

    public static function toggle(): void
    {
        requireRole('owner');
        requirePost();

        $employee = self::findEmployee();
        if ((int) $employee['id'] === (int) currentUser()['id']) {
            flash('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
            redirect('employees');
        }

        $newStatus = (int) $employee['is_active'] === 1 ? 'inactive' : 'active';
        db_run('UPDATE users SET status = ? WHERE id = ?', [$newStatus, $employee['id']]);
        flash('success', $newStatus === 'active' ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.');
        redirect('employees');
    }

    public static function delete(): void
    {
        requireRole('owner');
        requirePost();

        $employee = self::findEmployee();
        if ((int) $employee['id'] === (int) currentUser()['id']) {
            flash('error', 'Akun sendiri tidak dapat dihapus.');
            redirect('employees');
        }

        $related = (int) db_value(
            'SELECT
                (SELECT COUNT(*) FROM work_schedules WHERE employee_id = ?) +
                (SELECT COUNT(*) FROM attendances WHERE employee_id = ?) +
                (SELECT COUNT(*) FROM attendance_corrections WHERE employee_id = ?) +
                (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? OR created_by = ?) +
                (SELECT COUNT(*) FROM task_progress_logs WHERE updated_by = ?) +
                (SELECT COUNT(*) FROM task_evidences WHERE uploaded_by = ?) AS total',
            array_fill(0, 7, $employee['id'])
        );

        if ($related > 0) {
            flash('error', 'Karyawan ini masih memiliki data terkait. Gunakan tombol nonaktifkan agar riwayat tetap tersimpan.');
            redirect('employees');
        }

        db_run('DELETE FROM users WHERE id = ?', [$employee['id']]);
        flash('success', 'Data karyawan berhasil dihapus.');
        redirect('employees');
    }

    private static function formData(): array
    {
        return [
            'employee_code' => (string) post('employee_code', ''),
            'name' => (string) post('name', ''),
            'email' => (string) post('email', ''),
            'password' => (string) post('password', ''),
            'role' => (string) post('role', 'employee'),
            'position' => (string) post('position', ''),
            'is_active' => post('is_active') ? 1 : 0,
        ];
    }

    private static function findEmployee(): array
    {
        $id = (int) post('id', query('id', 0));
        $employee = db_one("SELECT *, CASE WHEN status = 'active' THEN 1 ELSE 0 END AS is_active FROM users WHERE id = ?", [$id]);
        if (!$employee) {
            flash('error', 'Data karyawan tidak ditemukan.');
            redirect('employees');
        }
        return $employee;
    }
}
