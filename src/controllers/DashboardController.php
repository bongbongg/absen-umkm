<?php

final class DashboardController
{
    public static function index(): void
    {
        if (isOwner()) {
            self::ownerDashboard();
            return;
        }
        self::employeeDashboard();
    }

    private static function ownerDashboard(): void
    {
        $today = date('Y-m-d');

        $metrics = [
            'scheduled' => (int) db_value('SELECT COUNT(*) FROM work_schedules WHERE work_date = ?', [$today]),
            'present' => (int) db_value(
                'SELECT COUNT(*) FROM attendances a JOIN work_schedules s ON s.id = a.schedule_id WHERE s.work_date = ? AND a.check_in_at IS NOT NULL',
                [$today]
            ),
            'late' => (int) db_value(
                "SELECT COUNT(*) FROM attendances a JOIN work_schedules s ON s.id = a.schedule_id WHERE s.work_date = ? AND a.status = 'late'",
                [$today]
            ),
            'pending_corrections' => (int) db_value("SELECT COUNT(*) FROM attendance_corrections WHERE status = 'pending'"),
            'task_running' => (int) db_value("SELECT COUNT(*) FROM tasks WHERE status IN ('assigned','in_progress','revision')"),
            'task_waiting' => (int) db_value("SELECT COUNT(*) FROM tasks WHERE status = 'submitted'"),
            'task_done' => (int) db_value("SELECT COUNT(*) FROM tasks WHERE status = 'completed'"),
            'task_overdue' => (int) db_value("SELECT COUNT(*) FROM tasks WHERE status NOT IN ('completed') AND deadline < NOW()"),
        ];

        $attendance = db_all(
            "SELECT a.*, u.name, u.employee_code, s.work_date,
                    s.start_time AS check_in_time, s.end_time AS check_out_time,
                    a.check_in_at AS check_in, a.check_out_at AS check_out,
                    CASE a.status WHEN 'on_time' THEN 'hadir' WHEN 'late' THEN 'terlambat' WHEN 'incomplete' THEN 'alpha' ELSE a.status END AS status,
                    GREATEST(0, TIMESTAMPDIFF(MINUTE, CONCAT(s.work_date, ' ', s.start_time), a.check_in_at)) AS late_minutes
             FROM attendances a
             JOIN users u ON u.id = a.employee_id
             JOIN work_schedules s ON s.id = a.schedule_id
             ORDER BY a.check_in_at DESC, a.id DESC LIMIT 8"
        );

        $corrections = db_all(
            "SELECT c.*, c.requested_date AS correction_date, u.name, s.work_date
             FROM attendance_corrections c
             JOIN users u ON u.id = c.employee_id
             LEFT JOIN work_schedules s ON s.employee_id = c.employee_id AND s.work_date = c.requested_date
             WHERE c.status = 'pending'
             ORDER BY c.created_at DESC LIMIT 5"
        );

        $tasks = db_all(
            "SELECT t.*, CASE WHEN t.status = 'assigned' THEN 'todo' ELSE t.status END AS status,
                    u.name AS employee_name, NULL AS approved_at
             FROM tasks t JOIN users u ON u.id = t.assigned_to
             WHERE t.status IN ('submitted','in_progress','revision')
                OR (t.status NOT IN ('completed') AND t.deadline < NOW())
             ORDER BY t.deadline ASC LIMIT 6"
        );

        $activities = [];
        foreach ($attendance as $row) {
            $activities[] = [
                'at' => $row['check_in'] ?? $row['created_at'],
                'type' => 'absensi',
                'title' => $row['name'] . ' melakukan ' . ($row['check_out'] ? 'check-out' : 'check-in'),
                'text' => formatDate($row['work_date']) . ' · ' . ($row['status'] === 'terlambat' ? 'Terlambat ' . (int) $row['late_minutes'] . ' menit' : ucfirst((string) $row['status'])),
                'link' => url('history', ['employee_id' => $row['employee_id']]),
            ];
        }
        foreach ($corrections as $row) {
            $activities[] = [
                'at' => $row['created_at'],
                'type' => 'koreksi',
                'title' => $row['name'] . ' mengajukan koreksi absensi',
                'text' => 'Tanggal ' . formatDate($row['correction_date']),
                'link' => url('corrections'),
            ];
        }
        foreach ($tasks as $row) {
            $activities[] = [
                'at' => $row['updated_at'] ?? $row['created_at'],
                'type' => 'tugas',
                'title' => $row['employee_name'] . ' · ' . strLimit($row['title'], 45),
                'text' => 'Progres ' . (int) $row['progress_percent'] . '% · deadline ' . formatDate($row['deadline'], true),
                'link' => url('task', ['id' => $row['id']]),
            ];
        }
        usort($activities, static fn(array $a, array $b): int => strcmp((string) $b['at'], (string) $a['at']));
        $activities = array_slice($activities, 0, 10);

        view('dashboard_owner', [
            'title' => 'Dashboard Owner',
            'activePage' => 'dashboard',
            'metrics' => $metrics,
            'todayAttendance' => array_slice($attendance, 0, 6),
            'activities' => $activities,
        ]);
    }

    private static function employeeDashboard(): void
    {
        $userId = (int) currentUser()['id'];
        $today = date('Y-m-d');

        $schedule = db_one(
            "SELECT s.*, s.employee_id AS user_id, s.start_time AS check_in_time, s.end_time AS check_out_time,
                    a.id AS attendance_id, a.check_in_at AS check_in, a.check_out_at AS check_out,
                    CASE a.status WHEN 'on_time' THEN 'hadir' WHEN 'late' THEN 'terlambat' WHEN 'incomplete' THEN 'alpha' ELSE a.status END AS status
             FROM work_schedules s
             LEFT JOIN attendances a ON a.schedule_id = s.id AND a.employee_id = s.employee_id
             WHERE s.employee_id = ? AND s.work_date = ?
             ORDER BY s.id DESC LIMIT 1",
            [$userId, $today]
        );

        $taskCounts = [
            'todo' => (int) db_value("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'assigned'", [$userId]),
            'in_progress' => (int) db_value("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'in_progress'", [$userId]),
            'submitted' => (int) db_value("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'submitted'", [$userId]),
            'completed' => (int) db_value("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'", [$userId]),
            'overdue' => (int) db_value("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status NOT IN ('completed') AND deadline < NOW()", [$userId]),
            'revision' => (int) db_value("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'revision'", [$userId]),
        ];

        $tasks = db_all(
            'SELECT * FROM tasks WHERE assigned_to = ? ORDER BY (deadline < NOW()) DESC, deadline ASC LIMIT 6',
            [$userId]
        );

        $corrections = (int) db_value(
            "SELECT COUNT(*) FROM attendance_corrections WHERE employee_id = ? AND status = 'pending'",
            [$userId]
        );

        view('dashboard_employee', [
            'title' => 'Dashboard Karyawan',
            'activePage' => 'dashboard',
            'schedule' => $schedule,
            'taskCounts' => $taskCounts,
            'tasks' => $tasks,
            'pendingCorrections' => $corrections,
        ]);
    }
}
