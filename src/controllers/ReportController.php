<?php

final class ReportController
{
    public static function index(): void
    {
        requireRole('owner');

        [$type, $from, $to] = self::filters();
        $rows = $type === 'tasks' ? self::taskRows($from, $to) : self::attendanceRows($from, $to);

        view('reports', [
            'title' => 'Laporan & Ekspor',
            'activePage' => 'reports',
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
        ]);
    }

    public static function export(): void
    {
        requireRole('owner');
        requirePost();

        [$type, $from, $to] = self::filters();
        $format = (string) post('format', 'csv');
        $rows = $type === 'tasks' ? self::taskRows($from, $to) : self::attendanceRows($from, $to);
        $suffix = $from . '-sd-' . $to;

        if ($format === 'pdf') {
            self::pdf($type, $rows, $from, $to, $suffix);
            return;
        }
        self::csv($type, $rows, $suffix);
    }

    private static function filters(): array
    {
        $type = (string) (post('type', query('type', 'attendance')) ?? 'attendance');
        if (!in_array($type, ['attendance', 'tasks'], true)) {
            $type = 'attendance';
        }

        $from = (string) (post('from', query('from', date('Y-m-01'))) ?? date('Y-m-01'));
        $to = (string) (post('to', query('to', date('Y-m-t'))) ?? date('Y-m-t'));
        if (v_date($from, 'Dari tanggal')) {
            $from = date('Y-m-01');
        }
        if (v_date($to, 'Sampai tanggal')) {
            $to = date('Y-m-t');
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        return [$type, $from, $to];
    }

    private static function attendanceRows(string $from, string $to): array
    {
        return db_all(
            "SELECT a.id, s.work_date, u.employee_code, u.name, u.position,
                    s.start_time AS check_in_time, s.end_time AS check_out_time,
                    a.check_in_at AS check_in, a.check_out_at AS check_out,
                    CASE a.status WHEN 'on_time' THEN 'hadir' WHEN 'late' THEN 'terlambat' WHEN 'incomplete' THEN 'alpha' ELSE a.status END AS status,
                    GREATEST(0, TIMESTAMPDIFF(MINUTE, CONCAT(s.work_date, ' ', s.start_time), a.check_in_at)) AS late_minutes
             FROM attendances a
             JOIN work_schedules s ON s.id = a.schedule_id
             JOIN users u ON u.id = a.employee_id
             WHERE s.work_date BETWEEN ? AND ?
             ORDER BY s.work_date DESC, u.name ASC",
            [$from, $to]
        );
    }

    private static function taskRows(string $from, string $to): array
    {
        return db_all(
            "SELECT t.id, t.title, u.employee_code, u.name, t.deadline,
                    CASE WHEN t.status = 'assigned' THEN 'todo' ELSE t.status END AS status,
                    t.progress_percent, t.created_at,
                    (SELECT MIN(l.created_at) FROM task_progress_logs l
                     WHERE l.task_id = t.id AND l.status_snapshot = 'completed') AS approved_at
             FROM tasks t JOIN users u ON u.id = t.assigned_to
             WHERE DATE(t.created_at) BETWEEN ? AND ?
             ORDER BY t.created_at DESC",
            [$from, $to]
        );
    }

    private static function csv(string $type, array $rows, string $suffix): void
    {
        if ($type === 'tasks') {
            $headers = ['ID', 'Judul Tugas', 'ID Karyawan', 'Nama Karyawan', 'Deadline', 'Status', 'Progres (%)', 'Dibuat', 'Disetujui'];
            $name = 'laporan-tugas-' . $suffix;
            $map = static fn(array $r): array => [
                $r['id'], $r['title'], $r['employee_code'], $r['name'],
                formatDate($r['deadline'], true), statusBadgeText($r['status']), $r['progress_percent'],
                formatDate($r['created_at'], true), formatDate($r['approved_at'], true),
            ];
        } else {
            $headers = ['ID', 'Tanggal', 'ID Karyawan', 'Nama Karyawan', 'Jabatan', 'Jam Masuk', 'Jam Pulang', 'Status', 'Terlambat (menit)'];
            $name = 'laporan-absensi-' . $suffix;
            $map = static fn(array $r): array => [
                $r['id'], formatDate($r['work_date']), $r['employee_code'], $r['name'], $r['position'] ?? '-',
                $r['check_in'] ? formatDate($r['check_in'], true) : '-',
                $r['check_out'] ? formatDate($r['check_out'], true) : '-',
                statusBadgeText($r['status']), $r['late_minutes'],
            ];
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $handle = fopen('php://output', 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, $map($row), ',', '"', '');
        }
        fclose($handle);
        exit;
    }

    private static function pdf(string $type, array $rows, string $from, string $to, string $suffix): void
    {
        $label = $type === 'tasks' ? 'Laporan Tugas & Progres' : 'Laporan Absensi';
        $pdf = new SimplePdf(env('APP_NAME', 'Monitoring Absensi UMKM'), $label);
        $pdf->paragraph('Periode: ' . formatDate($from) . ' sampai ' . formatDate($to) . '   |   Total data: ' . count($rows));

        if ($type === 'tasks') {
            $pdf->section('Rekap Tugas');
            $pdf->table(
                ['ID', 'Judul', 'Karyawan', 'Deadline', 'Status', 'Progres', 'Dibuat'],
                array_map(static fn(array $r): array => [
                    $r['id'], strLimit($r['title'], 38), $r['name'] . ' / ' . $r['employee_code'],
                    formatDate($r['deadline'], true), statusBadgeText($r['status']),
                    (int) $r['progress_percent'] . '%', formatDate($r['created_at'], true),
                ], $rows),
                [28, 130, 105, 85, 75, 42, 80]
            );
        } else {
            $pdf->section('Rekap Kehadiran');
            $pdf->table(
                ['ID', 'Tanggal', 'Karyawan', 'Jabatan', 'Masuk', 'Pulang', 'Status', 'Telat'],
                array_map(static fn(array $r): array => [
                    $r['id'], formatDate($r['work_date']), $r['name'] . ' / ' . $r['employee_code'],
                    $r['position'] ?? '-',
                    $r['check_in'] ? timeOnly($r['check_in']) : '-',
                    $r['check_out'] ? timeOnly($r['check_out']) : '-',
                    statusBadgeText($r['status']), (int) $r['late_minutes'] . ' mnt',
                ], $rows),
                [25, 60, 110, 70, 45, 45, 70, 40]
            );
        }

        $pdf->output(($type === 'tasks' ? 'laporan-tugas-' : 'laporan-absensi-') . $suffix);
    }
}

if (!function_exists('statusBadgeText')) {
    function statusBadgeText(string $status): string
    {
        $map = [
            'hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'alpha' => 'Alpha',
            'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak',
            'todo' => 'Belum dimulai', 'assigned' => 'Belum dimulai', 'in_progress' => 'Berjalan',
            'submitted' => 'Menunggu persetujuan', 'revision' => 'Perlu revisi', 'completed' => 'Selesai',
        ];
        return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}
