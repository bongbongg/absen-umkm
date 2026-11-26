<?php

final class TaskController
{
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    public static function index(): void
    {
        $owner = isOwner();
        $status = (string) query('status', '');
        $where = [];
        $params = [];

        if (!$owner) {
            $where[] = 't.assigned_to = ?';
            $params[] = currentUser()['id'];
        }
        if (in_array($status, ['todo', 'in_progress', 'submitted', 'revision', 'completed'], true)) {
            $where[] = 't.status = ?';
            $params[] = $status === 'todo' ? 'assigned' : $status;
        }

        $tasks = db_all(
            "SELECT t.*, CASE WHEN t.status = 'assigned' THEN 'todo' ELSE t.status END AS status,
                    (SELECT l.notes FROM task_progress_logs l WHERE l.task_id = t.id ORDER BY l.created_at DESC LIMIT 1) AS progress_note,
                    (SELECT MIN(l.created_at) FROM task_progress_logs l WHERE l.task_id = t.id AND l.status_snapshot = 'submitted') AS submitted_at,
                    (SELECT MIN(l.created_at) FROM task_progress_logs l WHERE l.task_id = t.id AND l.status_snapshot = 'completed') AS approved_at,
                    NULL AS approved_by,
                    u.name AS employee_name, u.employee_code,
                    (SELECT COUNT(*) FROM task_evidences e WHERE e.task_id = t.id) AS evidence_count
             FROM tasks t JOIN users u ON u.id = t.assigned_to
             WHERE " . ($where ? implode(' AND ', $where) : '1 = 1') . "
             ORDER BY (t.deadline < NOW() AND t.status <> 'completed') DESC, t.deadline ASC",
            $params
        );

        view('tasks', [
            'title' => 'Daftar Tugas',
            'activePage' => 'tasks',
            'tasks' => $tasks,
            'status' => $status,
            'owner' => $owner,
        ]);
    }

    public static function create(): void
    {
        requireRole('owner');
        view('task_form', [
            'title' => 'Buat Tugas Baru',
            'activePage' => 'tasks',
            'task' => null,
            'employees' => self::activeEmployees(),
        ]);
    }

    public static function store(): void
    {
        requireRole('owner');
        requirePost();

        $data = self::formData();
        $error = firstError([
            v_required($data['title'], 'Judul tugas'),
            v_required($data['deadline'], 'Tenggat waktu'),
            v_int($data['assigned_to'], 1, PHP_INT_MAX, 'Penerima tugas'),
            self::validateDeadline($data['deadline']),
        ]);

        if ($error) {
            flash('error', $error);
            rememberInput($data);
            redirect('tasks', ['action' => 'create']);
        }

        try {
            db_run(
                "INSERT INTO tasks (title, description, deadline, assigned_to, created_by, status)
                 VALUES (?, ?, ?, ?, ?, 'assigned')",
                [
                    trim($data['title']), $data['description'] ?: null,
                    (new DateTimeImmutable($data['deadline']))->format('Y-m-d H:i:s'),
                    $data['assigned_to'], currentUser()['id'],
                ]
            );
            flash('success', 'Tugas berhasil dibuat dan dibagikan.');
            redirect('tasks');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
            rememberInput($data);
            redirect('tasks', ['action' => 'create']);
        }
    }

    public static function edit(): void
    {
        requireRole('owner');
        $task = self::findTask();
        view('task_form', [
            'title' => 'Ubah Tugas',
            'activePage' => 'tasks',
            'task' => $task,
            'employees' => self::activeEmployees(),
        ]);
    }

    public static function update(): void
    {
        requireRole('owner');
        requirePost();

        $task = self::findTask();
        $data = self::formData();
        $error = firstError([
            v_required($data['title'], 'Judul tugas'),
            v_required($data['deadline'], 'Tenggat waktu'),
            v_int($data['assigned_to'], 1, PHP_INT_MAX, 'Penerima tugas'),
            self::validateDeadline($data['deadline']),
        ]);

        if ($error) {
            flash('error', $error);
            rememberInput($data);
            redirect('tasks', ['action' => 'edit', 'id' => $task['id']]);
        }

        db_run(
            'UPDATE tasks SET title = ?, description = ?, deadline = ?, assigned_to = ? WHERE id = ?',
            [
                trim($data['title']), $data['description'] ?: null,
                (new DateTimeImmutable($data['deadline']))->format('Y-m-d H:i:s'),
                $data['assigned_to'], $task['id'],
            ]
        );
        flash('success', 'Tugas berhasil diperbarui.');
        redirect('tasks');
    }

    public static function delete(): void
    {
        requireRole('owner');
        requirePost();

        $task = self::findTask();
        try {
            db_transaction(static function (PDO $pdo) use ($task): void {
                $stmt = $pdo->prepare('SELECT file_path FROM task_evidences WHERE task_id = ?');
                $stmt->execute([$task['id']]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $path) {
                    $full = dirname(__DIR__, 2) . '/' . $path;
                    if (is_file($full)) {
                        @unlink($full);
                    }
                }
                $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
                $stmt->execute([$task['id']]);
            });
            flash('success', 'Tugas berhasil dihapus.');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
        }
        redirect('tasks');
    }

    public static function show(): void
    {
        $taskId = (int) query('id', 0);
        $task = db_one(
            "SELECT t.*, CASE WHEN t.status = 'assigned' THEN 'todo' ELSE t.status END AS status,
                    (SELECT l.notes FROM task_progress_logs l WHERE l.task_id = t.id ORDER BY l.created_at DESC LIMIT 1) AS progress_note,
                    (SELECT MIN(l.created_at) FROM task_progress_logs l WHERE l.task_id = t.id AND l.status_snapshot = 'submitted') AS submitted_at,
                    (SELECT MIN(l.created_at) FROM task_progress_logs l WHERE l.task_id = t.id AND l.status_snapshot = 'completed') AS approved_at,
                    NULL AS approved_by,
                    u.name AS employee_name, u.employee_code, c.name AS creator_name
             FROM tasks t JOIN users u ON u.id = t.assigned_to JOIN users c ON c.id = t.created_by
             WHERE t.id = ?",
            [$taskId]
        );
        if (!$task) {
            flash('error', 'Tugas tidak ditemukan.');
            redirect('tasks');
        }
        if (isEmployee() && (int) $task['assigned_to'] !== (int) currentUser()['id']) {
            http_response_code(403);
            view('errors/403', ['title' => 'Akses ditolak']);
            exit;
        }

        view('task_detail', [
            'title' => 'Detail Tugas',
            'activePage' => 'tasks',
            'task' => $task,
            'logs' => db_all(
                "SELECT l.*, l.updated_by AS user_id,
                        CASE WHEN l.status_snapshot = 'assigned' THEN 'todo' ELSE l.status_snapshot END AS status,
                        l.notes AS note, u.name
                 FROM task_progress_logs l JOIN users u ON u.id = l.updated_by
                 WHERE l.task_id = ? ORDER BY l.created_at DESC",
                [$taskId]
            ),
            'evidences' => db_all(
                'SELECT e.*, e.file_size_bytes AS file_size, e.uploaded_at AS created_at, u.name
                 FROM task_evidences e JOIN users u ON u.id = e.uploaded_by
                 WHERE e.task_id = ? ORDER BY e.uploaded_at DESC',
                [$taskId]
            ),
            'owner' => isOwner(),
        ]);
    }

    public static function progress(): void
    {
        requirePost();
        requireRole('employee');

        $task = self::assignedTask();
        $percent = (string) post('progress_percent', '');
        $status = (string) post('status', 'in_progress');
        $note = trim((string) post('note', ''));

        $error = firstError([
            v_int($percent, 0, 100, 'Persentase progres'),
            v_in($status, ['todo', 'in_progress', 'revision'], 'Status'),
        ]);
        if ($error) {
            flash('error', $error);
            redirect('task', ['id' => $task['id']]);
        }
        $dbStatus = $status === 'todo' ? 'assigned' : $status;
        try {
            db_transaction(static function (PDO $pdo) use ($task, $percent, $dbStatus, $note): void {
                $stmt = $pdo->prepare('UPDATE tasks SET progress_percent = ?, status = ? WHERE id = ?');
                $stmt->execute([(int) $percent, $dbStatus, $task['id']]);

                $stmt = $pdo->prepare(
                    'INSERT INTO task_progress_logs (task_id, updated_by, progress_percent, status_snapshot, notes)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$task['id'], currentUser()['id'], (int) $percent, $dbStatus, $note]);
            });
            flash('success', 'Progres tugas berhasil disimpan.');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
        }
        redirect('task', ['id' => $task['id']]);
    }

    public static function evidence(): void
    {
        requirePost();
        requireRole('employee');

        $task = self::assignedTask();
        $file = $_FILES['evidence'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Pilih berkas bukti pekerjaan terlebih dahulu.');
            redirect('task', ['id' => $task['id']]);
        }
        if ((int) ($file['error'] ?? 0) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
            flash('error', 'Upload gagal. Coba ulangi dengan ukuran berkas lebih kecil.');
            redirect('task', ['id' => $task['id']]);
        }
        if ((int) $file['size'] > uploadMaxBytes()) {
            flash('error', 'Ukuran berkas melebihi batas ' . formatBytes(uploadMaxBytes()) . '.');
            redirect('task', ['id' => $task['id']]);

        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        if (!isset(self::ALLOWED_MIME[$mime])) {
            flash('error', 'Tipe berkas tidak diizinkan. Gunakan JPG, PNG, PDF, DOC, atau DOCX.');
            redirect('task', ['id' => $task['id']]);
        }

        $directory = dirname(__DIR__, 2) . '/uploads/evidence';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            flash('error', 'Folder upload belum siap. Hubungi administrator.');
            redirect('task', ['id' => $task['id']]);
        }

        $fileName = 'task' . $task['id'] . '-' . bin2hex(random_bytes(8)) . '.' . self::ALLOWED_MIME[$mime];
        $relativePath = 'uploads/evidence/' . $fileName;
        if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $fileName)) {
            flash('error', 'Gagal menyimpan berkas. Coba lagi.');
            redirect('task', ['id' => $task['id']]);
        }

        try {
            db_run(
                'INSERT INTO task_evidences (task_id, uploaded_by, file_name, file_path, mime_type, file_size_bytes)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $task['id'], currentUser()['id'],
                    mb_substr((string) $file['name'], 0, 255), $relativePath, $mime, (int) $file['size'],
                ]
            );
            flash('success', 'Bukti pekerjaan berhasil diunggah.');
        } catch (Throwable $e) {
            @unlink($directory . '/' . $fileName);
            flash('error', friendlyError($e));
        }
        redirect('task', ['id' => $task['id']]);
    }

    public static function submit(): void
    {
        requirePost();
        requireRole('employee');

        $task = self::assignedTask();
        if ((int) $task['progress_percent'] < 100) {
            flash('error', 'Progres harus mencapai 100% sebelum tugas diajukan selesai.');
            redirect('task', ['id' => $task['id']]);
        }
        if ((int) db_value('SELECT COUNT(*) FROM task_evidences WHERE task_id = ?', [$task['id']]) === 0) {
            flash('error', 'Unggah minimal satu bukti pekerjaan sebelum mengajukan tugas selesai.');
            redirect('task', ['id' => $task['id']]);
        }

        db_run("UPDATE tasks SET status = 'submitted' WHERE id = ?", [$task['id']]);
        $completionNote = trim((string) post('completion_note', ''));
        if ($completionNote !== '') {
            db_run(
                'INSERT INTO task_progress_logs (task_id, updated_by, progress_percent, status_snapshot, notes) VALUES (?, ?, ?, ?, ?)',
                [$task['id'], currentUser()['id'], (int) $task['progress_percent'], 'submitted', $completionNote]
            );
        }
        flash('success', 'Tugas diajukan selesai. Menunggu persetujuan owner.');
        redirect('task', ['id' => $task['id']]);
    }

    public static function approve(): void
    {
        requireRole('owner');
        requirePost();

        $task = self::findTask();
        $updated = db_run(
            "UPDATE tasks SET status = 'completed', progress_percent = 100 WHERE id = ? AND status = 'submitted'",
            [$task['id']]
        );
        if ($updated > 0) {
            db_run(
                'INSERT INTO task_progress_logs (task_id, updated_by, progress_percent, status_snapshot, notes) VALUES (?, ?, ?, ?, ?)',
                [$task['id'], currentUser()['id'], 100, 'completed', 'Disetujui owner']
            );
        }
        flash($updated > 0 ? 'success' : 'error', $updated > 0 ? 'Tugas disetujui dan ditandai selesai.' : 'Tidak ada pengajuan selesai pada tugas ini.');
        redirect('task', ['id' => $task['id']]);
    }

    public static function revision(): void
    {
        requireRole('owner');
        requirePost();

        $task = self::findTask();
        $note = trim((string) post('review_note', ''));
        if ($note === '') {
            flash('error', 'Berikan catatan revisi agar karyawan tahu apa yang harus diperbaiki.');
            redirect('task', ['id' => $task['id']]);
        }

        $updated = db_run(
            "UPDATE tasks SET status = 'revision' WHERE id = ? AND status = 'submitted'",
            [$task['id']]
        );
        if ($updated > 0) {
            db_run(
                'INSERT INTO task_progress_logs (task_id, updated_by, progress_percent, status_snapshot, notes) VALUES (?, ?, ?, ?, ?)',
                [$task['id'], currentUser()['id'], (int) $task['progress_percent'], 'revision', $note]
            );
        }
        flash($updated > 0 ? 'success' : 'error', $updated > 0 ? 'Tugas dikembalikan untuk revisi.' : 'Tidak ada pengajuan selesai pada tugas ini.');
        redirect('task', ['id' => $task['id']]);
    }

    private static function formData(): array
    {
        $deadline = (string) post('deadline', '');
        return [
            'title' => (string) post('title', ''),
            'description' => trim((string) post('description', '')),
            'deadline' => $deadline,
            'assigned_to' => (int) post('assigned_to', 0),
        ];
    }

    private static function validateDeadline(string $deadline): ?string
    {
        if ($deadline === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $deadline);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return 'Format tenggat waktu tidak valid.';
        }
        return null;
    }

    private static function activeEmployees(): array
    {
        return db_all("SELECT id, employee_code, name, position FROM users WHERE role = 'employee' AND status = 'active' ORDER BY name ASC");
    }

    private static function findTask(): array
    {
        $id = (int) post('id', query('id', 0));
        $task = db_one('SELECT * FROM tasks WHERE id = ?', [$id]);
        if (!$task) {
            flash('error', 'Tugas tidak ditemukan.');
            redirect('tasks');
        }
        return $task;
    }

    private static function assignedTask(): array
    {
        $id = (int) post('task_id', 0);
        $task = db_one('SELECT * FROM tasks WHERE id = ? AND assigned_to = ?', [$id, currentUser()['id']]);
        if (!$task) {
            flash('error', 'Tugas tidak ditemukan atau bukan milik Anda.');
            redirect('tasks');
        }
        if ($task['status'] === 'completed') {
            flash('error', 'Tugas yang sudah disetujui tidak dapat diubah lagi.');
            redirect('task', ['id' => $task['id']]);
        }
        return $task;
    }
}
