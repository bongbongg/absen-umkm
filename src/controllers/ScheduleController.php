<?php

final class ScheduleController
{
    public static function index(): void
    {
        $owner = isOwner();
        $employeeId = $owner ? (int) query('employee_id', 0) : (int) currentUser()['id'];
        $from = (string) query('from', '');
        $to = (string) query('to', '');

        $where = [];
        $params = [];
        if ($employeeId > 0) {
            $where[] = 's.employee_id = ?';
            $params[] = $employeeId;
        }
        if ($from !== '' && v_date($from, 'Tanggal') === null) {
            $where[] = 's.work_date >= ?';
            $params[] = $from;
        }
        if ($to !== '' && v_date($to, 'Tanggal') === null) {
            $where[] = 's.work_date <= ?';
            $params[] = $to;
        }

        $schedules = db_all(
            "SELECT s.*, s.employee_id AS user_id, s.start_time AS check_in_time, s.end_time AS check_out_time, NULL AS note,
                    u.name, u.employee_code, a.id AS attendance_id,
                    a.check_in_at AS check_in, a.check_out_at AS check_out,
                    CASE a.status WHEN 'on_time' THEN 'hadir' WHEN 'late' THEN 'terlambat' WHEN 'incomplete' THEN 'alpha' ELSE a.status END AS status
             FROM work_schedules s
             JOIN users u ON u.id = s.employee_id
             LEFT JOIN attendances a ON a.schedule_id = s.id AND a.employee_id = s.employee_id
             WHERE " . ($where ? implode(' AND ', $where) : '1 = 1') . '
             ORDER BY s.work_date DESC, u.name ASC',
            $params
        );

        view('schedules', [
            'title' => 'Jadwal Kerja',
            'activePage' => 'schedules',
            'schedules' => $schedules,
            'employees' => self::activeEmployees(),
            'employeeId' => $employeeId,
            'from' => $from,
            'to' => $to,
            'owner' => $owner,
        ]);
    }

    public static function create(): void
    {
        requireRole('owner');
        view('schedule_form', [
            'title' => 'Tambah Jadwal Kerja',
            'activePage' => 'schedules',
            'schedule' => null,
            'employees' => self::activeEmployees(),
        ]);
    }

    public static function store(): void
    {
        requireRole('owner');
        requirePost();

        $data = self::formData();
        $error = firstError(self::validate($data));
        if ($error) {
            flash('error', $error);
            rememberInput($data);
            redirect('schedules', ['action' => 'create']);
        }

        try {
            db_run(
                'INSERT INTO work_schedules (employee_id, work_date, start_time, end_time, created_by)
                 VALUES (?, ?, ?, ?, ?)',
                [
                    $data['user_id'], $data['work_date'], $data['check_in_time'],
                    $data['check_out_time'], currentUser()['id'],
                ]
            );
            flash('success', 'Jadwal kerja berhasil ditambahkan.');
            redirect('schedules');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
            rememberInput($data);
            redirect('schedules', ['action' => 'create']);
        }
    }

    public static function edit(): void
    {
        requireRole('owner');
        $schedule = self::findSchedule();
        view('schedule_form', [
            'title' => 'Ubah Jadwal Kerja',
            'activePage' => 'schedules',
            'schedule' => $schedule,
            'employees' => self::activeEmployees(),
        ]);
    }

    public static function update(): void
    {
        requireRole('owner');
        requirePost();

        $schedule = self::findSchedule();
        $data = self::formData();
        $error = firstError(self::validate($data));
        if ($error) {
            flash('error', $error);
            rememberInput($data);
            redirect('schedules', ['action' => 'edit', 'id' => $schedule['id']]);
        }

        if ((int) $data['user_id'] !== (int) $schedule['user_id']
            && (int) db_value('SELECT COUNT(*) FROM attendances WHERE schedule_id = ?', [$schedule['id']]) > 0) {
            flash('error', 'Karyawan pada jadwal ini tidak dapat diubah karena sudah ada catatan absensi.');
            redirect('schedules', ['action' => 'edit', 'id' => $schedule['id']]);
        }

        try {
            db_run(
                'UPDATE work_schedules SET employee_id = ?, work_date = ?, start_time = ?, end_time = ? WHERE id = ?',
                [$data['user_id'], $data['work_date'], $data['check_in_time'], $data['check_out_time'], $schedule['id']]
            );
            flash('success', 'Jadwal kerja berhasil diperbarui.');
            redirect('schedules');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
            rememberInput($data);
            redirect('schedules', ['action' => 'edit', 'id' => $schedule['id']]);
        }
    }

    public static function delete(): void
    {
        requireRole('owner');
        requirePost();

        $schedule = self::findSchedule();
        if ((int) db_value('SELECT COUNT(*) FROM attendances WHERE schedule_id = ?', [$schedule['id']]) > 0) {
            flash('error', 'Jadwal ini sudah memiliki catatan absensi dan tidak dapat dihapus.');
            redirect('schedules');
        }

        try {
            db_run('DELETE FROM work_schedules WHERE id = ?', [$schedule['id']]);
            flash('success', 'Jadwal kerja berhasil dihapus.');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
        }
        redirect('schedules');
    }

    private static function activeEmployees(): array
    {
        return db_all("SELECT id, employee_code, name, position FROM users WHERE role = 'employee' AND status = 'active' ORDER BY name ASC");
    }

    private static function formData(): array
    {
        return [
            'user_id' => (int) post('user_id', 0),
            'work_date' => (string) post('work_date', ''),
            'check_in_time' => (string) post('check_in_time', '08:00'),
            'check_out_time' => (string) post('check_out_time', '17:00'),
            'note' => trim((string) post('note', '')),
        ];
    }

    private static function validate(array $data): array
    {
        $errors = [
            v_int($data['user_id'], 1, PHP_INT_MAX, 'Karyawan'),
            v_date($data['work_date'], 'Tanggal kerja'),
            v_time($data['check_in_time'], 'Jam masuk'),
            v_time($data['check_out_time'], 'Jam pulang'),
        ];

        $in = strtotime($data['check_in_time']);
        $out = strtotime($data['check_out_time']);
        if ($in !== false && $out !== false && $out <= $in) {
            $errors[] = 'Jam pulang harus lebih akhir daripada jam masuk.';
        }
        return $errors;
    }

    private static function findSchedule(): array
    {
        $id = (int) post('id', query('id', 0));
        $schedule = db_one(
            'SELECT *, employee_id AS user_id, start_time AS check_in_time, end_time AS check_out_time, NULL AS note FROM work_schedules WHERE id = ?',
            [$id]
        );
        if (!$schedule) {
            flash('error', 'Jadwal tidak ditemukan.');
            redirect('schedules');
        }
        return $schedule;
    }
}
