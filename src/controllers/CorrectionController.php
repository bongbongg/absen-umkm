<?php

final class CorrectionController
{
    public static function index(): void
    {
        $owner = isOwner();
        $status = (string) query('status', '');
        $where = [];
        $params = [];

        if (!$owner) {
            $where[] = 'c.employee_id = ?';
            $params[] = currentUser()['id'];
        }
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $where[] = 'c.status = ?';
            $params[] = $status;
        }

        $corrections = db_all(
            "SELECT c.*, c.requested_date AS correction_date, u.name, u.employee_code,
                    s.start_time AS check_in_time, s.end_time AS check_out_time,
                    a.check_in_at AS actual_check_in, a.check_out_at AS actual_check_out
             FROM attendance_corrections c
             JOIN users u ON u.id = c.employee_id
             LEFT JOIN work_schedules s ON s.employee_id = c.employee_id AND s.work_date = c.requested_date
             LEFT JOIN attendances a ON a.id = c.attendance_id
             WHERE " . ($where ? implode(' AND ', $where) : '1 = 1') . "
             ORDER BY c.status = 'pending' DESC, c.created_at DESC",
            $params
        );

        view('corrections', [
            'title' => 'Koreksi Absensi',
            'activePage' => 'corrections',
            'corrections' => $corrections,
            'status' => $status,
            'owner' => $owner,
        ]);
    }

    public static function create(): void
    {
        requireRole('employee');
        view('correction_form', [
            'title' => 'Ajukan Koreksi Absensi',
            'activePage' => 'corrections',
        ]);
    }

    public static function store(): void
    {
        requireRole('employee');
        requirePost();

        $data = [
            'correction_date' => (string) post('correction_date', ''),
            'requested_check_in' => (string) post('requested_check_in', ''),
            'requested_check_out' => (string) post('requested_check_out', ''),
            'reason' => trim((string) post('reason', '')),
        ];

        $errors = [
            v_date($data['correction_date'], 'Tanggal'),
            v_required($data['reason'], 'Alasan'),
        ];
        if ($data['requested_check_in'] !== '') {
            $errors[] = v_time($data['requested_check_in'], 'Jam masuk yang diajukan');
        }
        if ($data['requested_check_out'] !== '') {
            $errors[] = v_time($data['requested_check_out'], 'Jam pulang yang diajukan');
        }
        if ($data['requested_check_in'] === '' && $data['requested_check_out'] === '') {
            $errors[] = 'Minimal satu jam (masuk atau pulang) harus diajukan.';
        }
        if ($data['correction_date'] > date('Y-m-d')) {
            $errors[] = 'Tanggal koreksi tidak boleh di masa depan.';
        }
        if ($error = firstError($errors)) {
            flash('error', $error);
            rememberInput($data);
            redirect('corrections', ['action' => 'create']);
        }

        $requestedIn = $data['requested_check_in'] !== ''
            ? $data['correction_date'] . ' ' . $data['requested_check_in'] . ':00'
            : null;
        $requestedOut = $data['requested_check_out'] !== ''
            ? $data['correction_date'] . ' ' . $data['requested_check_out'] . ':00'
            : null;

        $schedule = db_one(
            'SELECT * FROM work_schedules WHERE employee_id = ? AND work_date = ?',
            [currentUser()['id'], $data['correction_date']]
        );
        if (!$schedule) {
            flash('error', 'Tidak ada jadwal kerja pada tanggal tersebut. Hubungi owner jika data jadwal belum dibuat.');
            rememberInput($data);
            redirect('corrections', ['action' => 'create']);
        }

        $attendance = db_one(
            'SELECT id FROM attendances WHERE employee_id = ? AND schedule_id = ?',
            [currentUser()['id'], $schedule['id']]
        );
        $duplicate = (int) db_value(
            "SELECT COUNT(*) FROM attendance_corrections
             WHERE employee_id = ? AND requested_date = ? AND status = 'pending'",
            [currentUser()['id'], $data['correction_date']]
        );
        if ($duplicate > 0) {
            flash('error', 'Masih ada pengajuan koreksi menunggu persetujuan untuk tanggal ini.');
            redirect('corrections');
        }

        try {
            db_run(
                'INSERT INTO attendance_corrections
                    (employee_id, attendance_id, requested_date, requested_check_in, requested_check_out, reason)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    currentUser()['id'],
                    $attendance['id'] ?? null,
                    $data['correction_date'],
                    $requestedIn,
                    $requestedOut,
                    $data['reason'],
                ]
            );
            flash('success', 'Pengajuan koreksi berhasil dikirim. Menunggu persetujuan owner.');
            redirect('corrections');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
            rememberInput($data);
            redirect('corrections', ['action' => 'create']);
        }
    }

    public static function approve(): void
    {
        requireRole('owner');
        requirePost();

        $id = (int) post('id', 0);
        $note = trim((string) post('review_note', ''));

        try {
            db_transaction(static function (PDO $pdo) use ($id, $note): void {
                $stmt = $pdo->prepare("SELECT * FROM attendance_corrections WHERE id = ? AND status = 'pending' FOR UPDATE");
                $stmt->execute([$id]);
                $correction = $stmt->fetch();
                if (!$correction) {
                    throw new RuntimeException('Pengajuan koreksi tidak ditemukan atau sudah diproses.');
                }

                $stmt = $pdo->prepare('SELECT * FROM work_schedules WHERE employee_id = ? AND work_date = ? LIMIT 1');
                $stmt->execute([$correction['employee_id'], $correction['requested_date']]);
                $schedule = $stmt->fetch();
                if (!$schedule) {
                    throw new RuntimeException('Jadwal terkait tidak ditemukan.');
                }

                $stmt = $pdo->prepare('SELECT * FROM attendances WHERE employee_id = ? AND schedule_id = ? FOR UPDATE');
                $stmt->execute([$correction['employee_id'], $schedule['id']]);
                $attendance = $stmt->fetch();

                $normalizeTime = static function (?string $value, string $date): ?string {
                    if (!$value) {
                        return null;
                    }
                    return str_contains($value, ' ')
                        ? $value
                        : $date . ' ' . substr($value, 0, 5) . ':00';
                };
                $checkIn = $normalizeTime($correction['requested_check_in'], $correction['requested_date'])
                    ?: ($attendance['check_in_at'] ?? null);
                $checkOut = $normalizeTime($correction['requested_check_out'], $correction['requested_date'])
                    ?: ($attendance['check_out_at'] ?? null);

                if (!$checkIn && !$checkOut) {
                    throw new RuntimeException('Pengajuan tidak memuat jam yang dapat disimpan.');
                }
                if ($checkOut && $checkIn && strtotime($checkOut) < strtotime($checkIn)) {
                    throw new RuntimeException('Jam pulang yang diajukan tidak boleh lebih awal dari jam masuk.');
                }
                if ($checkOut && !$checkIn) {
                    throw new RuntimeException('Jam masuk harus diisi sebelum jam pulang disetujui.');
                }

                $scheduledIn = new DateTimeImmutable($schedule['work_date'] . ' ' . $schedule['start_time']);
                $actualIn = $checkIn ? new DateTimeImmutable($checkIn) : null;
                $late = $actualIn && $actualIn > $scheduledIn
                    ? (int) floor(($actualIn->getTimestamp() - $scheduledIn->getTimestamp()) / 60)
                    : 0;
                $status = $actualIn ? ($late > 0 ? 'late' : 'on_time') : 'incomplete';

                if ($attendance) {
                    $stmt = $pdo->prepare(
                        'UPDATE attendances SET check_in_at = ?, check_out_at = ?, status = ? WHERE id = ?'
                    );
                    $stmt->execute([$checkIn, $checkOut, $status, $attendance['id']]);
                    $attendanceId = (int) $attendance['id'];
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO attendances (employee_id, schedule_id, check_in_at, check_out_at, status)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$correction['employee_id'], $schedule['id'], $checkIn, $checkOut, $status]);
                    $attendanceId = (int) $pdo->lastInsertId();
                }

                $stmt = $pdo->prepare(
                    "UPDATE attendance_corrections
                     SET status = 'approved', attendance_id = ?, review_note = ?, reviewed_by = ?, reviewed_at = NOW()
                     WHERE id = ?"
                );
                $stmt->execute([$attendanceId, $note ?: null, currentUser()['id'], $id]);
            });

            flash('success', 'Koreksi disetujui dan data absensi telah diperbarui.');
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
        }
        redirect('corrections');
    }

    public static function reject(): void
    {
        requireRole('owner');
        requirePost();

        $id = (int) post('id', 0);
        $note = trim((string) post('review_note', ''));

        try {
            $updated = db_run(
                "UPDATE attendance_corrections
                 SET status = 'rejected', review_note = ?, reviewed_by = ?, reviewed_at = NOW()
                 WHERE id = ? AND status = 'pending'",
                [$note ?: null, currentUser()['id'], $id]
            );
            if ($updated === 0) {
                flash('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
            } else {
                flash('success', 'Pengajuan koreksi ditolak.');
            }
        } catch (Throwable $e) {
            flash('error', friendlyError($e));
        }
        redirect('corrections');
    }
}
