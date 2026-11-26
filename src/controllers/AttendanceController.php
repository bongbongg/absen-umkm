<?php

final class AttendanceController
{
    public static function index(): void
    {
        if (isOwner()) {
            $rows = db_all(
                "SELECT a.*, u.name, u.employee_code, s.work_date,
                        s.start_time AS check_in_time, s.end_time AS check_out_time,
                        a.check_in_at AS check_in, a.check_out_at AS check_out,
                        CASE a.status WHEN 'on_time' THEN 'hadir' WHEN 'late' THEN 'terlambat' WHEN 'incomplete' THEN 'alpha' ELSE a.status END AS status,
                        GREATEST(0, TIMESTAMPDIFF(MINUTE, CONCAT(s.work_date, ' ', s.start_time), a.check_in_at)) AS late_minutes
                 FROM attendances a
                 JOIN users u ON u.id = a.employee_id
                 JOIN work_schedules s ON s.id = a.schedule_id
                 WHERE s.work_date = ?
                 ORDER BY a.check_in_at DESC, u.name ASC",
                [date('Y-m-d')]
            );
            view('attendance_owner', [
                'title' => 'Absensi Hari Ini',
                'activePage' => 'attendance',
                'rows' => $rows,
            ]);
            return;
        }

        $userId = (int) currentUser()['id'];
        $schedules = db_all(
            "SELECT s.*, s.employee_id AS user_id, s.start_time AS check_in_time, s.end_time AS check_out_time,
                    a.id AS attendance_id, a.check_in_at AS check_in, a.check_out_at AS check_out,
                    CASE a.status WHEN 'on_time' THEN 'hadir' WHEN 'late' THEN 'terlambat' WHEN 'incomplete' THEN 'alpha' ELSE a.status END AS status,
                    GREATEST(0, TIMESTAMPDIFF(MINUTE, CONCAT(s.work_date, ' ', s.start_time), a.check_in_at)) AS late_minutes
             FROM work_schedules s
             LEFT JOIN attendances a ON a.schedule_id = s.id AND a.employee_id = s.employee_id
             WHERE s.employee_id = ? AND s.work_date BETWEEN ? AND ?
             ORDER BY s.work_date DESC",
            [$userId, date('Y-m-d', strtotime('-7 days')), date('Y-m-d', strtotime('+7 days'))]
        );
        view('attendance_employee', [
            'title' => 'Absensi Saya',
            'activePage' => 'attendance',
            'schedules' => $schedules,
        ]);
    }

    public static function checkin(): void
    {
        requirePost();
        requireRole('employee');

        $scheduleId = (int) post('schedule_id', 0);
        $schedule = db_one(
            'SELECT *, employee_id AS user_id, start_time AS check_in_time FROM work_schedules WHERE id = ? AND employee_id = ?',
            [$scheduleId, currentUser()['id']]
        );
        if (!$schedule) {
            flash('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
            redirect('attendance');
        }
        if ($schedule['work_date'] !== date('Y-m-d')) {
            flash('error', 'Check-in hanya dapat dilakukan pada tanggal jadwal yang berlaku (' . formatDate($schedule['work_date']) . ').');
            redirect('attendance');
        }

        $now = new DateTimeImmutable();
        $scheduled = new DateTimeImmutable($schedule['work_date'] . ' ' . $schedule['check_in_time']);
        $lateMinutes = max(0, (int) floor(($now->getTimestamp() - $scheduled->getTimestamp()) / 60));

        $existing = db_one('SELECT id FROM attendances WHERE employee_id = ? AND schedule_id = ?', [currentUser()['id'], $scheduleId]);
        if ($existing) {
            flash('error', 'Anda sudah melakukan check-in pada jadwal ini.');
            redirect('attendance');
        }

        try {
            db_run(
                'INSERT INTO attendances (employee_id, schedule_id, check_in_at, status)
                 VALUES (?, ?, ?, ?)',
                [
                    currentUser()['id'], $scheduleId, $now->format('Y-m-d H:i:s'),
                    $lateMinutes > 0 ? 'late' : 'on_time',
                ]
            );
            flash('success', $lateMinutes > 0
                ? 'Check-in berhasil, terlambat ' . $lateMinutes . ' menit.'
                : 'Check-in berhasil. Selamat bekerja!');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
        }
        redirect('attendance');
    }

    public static function checkout(): void
    {
        requirePost();
        requireRole('employee');

        $scheduleId = (int) post('schedule_id', 0);
        $attendance = db_one(
            'SELECT a.*, a.check_in_at AS check_in, a.check_out_at AS check_out, s.work_date
             FROM attendances a JOIN work_schedules s ON s.id = a.schedule_id
             WHERE a.schedule_id = ? AND a.employee_id = ?',
            [$scheduleId, currentUser()['id']]
        );

        if (!$attendance || $attendance['check_in'] === null) {
            flash('error', 'Anda belum melakukan check-in pada jadwal ini.');
            redirect('attendance');
        }
        if ($attendance['check_out'] !== null) {
            flash('error', 'Check-out pada jadwal ini sudah tercatat.');
            redirect('attendance');
        }
        if ($attendance['work_date'] !== date('Y-m-d')) {
            flash('error', 'Check-out hanya dapat dilakukan pada hari jadwal tersebut.');
            redirect('attendance');
        }

        $now = new DateTimeImmutable();
        if ($now < new DateTimeImmutable($attendance['check_in'])) {
            flash('error', 'Waktu check-out tidak boleh lebih awal dari check-in.');
            redirect('attendance');
        }

        try {
            db_run('UPDATE attendances SET check_out_at = ? WHERE id = ?', [$now->format('Y-m-d H:i:s'), $attendance['id']]);
            flash('success', 'Check-out berhasil. Terima kasih atas kerja kerasnya!');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
        }
        redirect('attendance');
    }

    public static function history(): void
    {
        $owner = isOwner();
        $employeeId = $owner ? (int) query('employee_id', 0) : (int) currentUser()['id'];
        $from = (string) query('from', date('Y-m-01'));
        $to = (string) query('to', date('Y-m-t'));

        if (v_date($from, 'Dari tanggal')) {
            $from = date('Y-m-01');
        }
        if (v_date($to, 'Sampai tanggal')) {
            $to = date('Y-m-t');
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $where = ['s.work_date BETWEEN ? AND ?'];
        $params = [$from, $to];
        if ($employeeId > 0) {
            $where[] = 'a.employee_id = ?';
            $params[] = $employeeId;
        }

        $rows = db_all(
            "SELECT a.*, u.name, u.employee_code, u.position, s.work_date,
                    s.start_time AS check_in_time, s.end_time AS check_out_time,
                    a.check_in_at AS check_in, a.check_out_at AS check_out,
                    CASE a.status WHEN 'on_time' THEN 'hadir' WHEN 'late' THEN 'terlambat' WHEN 'incomplete' THEN 'alpha' ELSE a.status END AS status,
                    GREATEST(0, TIMESTAMPDIFF(MINUTE, CONCAT(s.work_date, ' ', s.start_time), a.check_in_at)) AS late_minutes
             FROM attendances a
             JOIN users u ON u.id = a.employee_id
             JOIN work_schedules s ON s.id = a.schedule_id
             WHERE " . implode(' AND ', $where) . '
             ORDER BY s.work_date DESC, u.name ASC',
            $params
        );

        view('attendance_history', [
            'title' => 'Riwayat Absensi',
            'activePage' => 'history',
            'rows' => $rows,
            'employees' => $owner ? db_all("SELECT id, employee_code, name FROM users WHERE role = 'employee' ORDER BY name") : [],
            'employeeId' => $employeeId,
            'from' => $from,
            'to' => $to,
            'owner' => $owner,
        ]);
    }
}
