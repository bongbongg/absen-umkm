-- =============================================================
-- seed.sql - Data contoh monitoring absensi & tugas UMKM
-- Diselaraskan dengan skema MySQL Workbench yang sudah dipakai.
-- Semua akun contoh memakai password: password123
-- Hash: $2y$12$XipvCrqm4p4Xn.Z920aXReL9ZyQ50xZZ62idI1ICKkDEu6RcZoYbe
-- Aman dijalankan berulang: INSERT IGNORE (tidak menimpa data ada).
-- =============================================================
--
-- PENTING: jalankan HANYA pada database kosong/data contoh.
-- install.php otomatis melewati file ini bila tabel users sudah berisi data.

-- -------------------------------------------------------------
-- 1. users (id eksplisit 1..6)
--    1 = owner, 2..5 = karyawan aktif, 6 = karyawan nonaktif
-- -------------------------------------------------------------
INSERT IGNORE INTO users
  (id, employee_code, name, email, password_hash, role, `position`, status)
VALUES
  (1, 'OWN-001', 'Bapak Owner', 'owner@umkm.test',
   '$2y$12$XipvCrqm4p4Xn.Z920aXReL9ZyQ50xZZ62idI1ICKkDEu6RcZoYbe',
   'owner', 'Owner', 'active'),
  (2, 'EMP-001', 'Budi Santoso', 'budi@umkm.test',
   '$2y$12$XipvCrqm4p4Xn.Z920aXReL9ZyQ50xZZ62idI1ICKkDEu6RcZoYbe',
   'employee', 'Kasir', 'active'),
  (3, 'EMP-002', 'Siti Rahma', 'siti@umkm.test',
   '$2y$12$XipvCrqm4p4Xn.Z920aXReL9ZyQ50xZZ62idI1ICKkDEu6RcZoYbe',
   'employee', 'Produksi', 'active'),
  (4, 'EMP-003', 'Rizky Pratama', 'rizky@umkm.test',
   '$2y$12$XipvCrqm4p4Xn.Z920aXReL9ZyQ50xZZ62idI1ICKkDEu6RcZoYbe',
   'employee', 'Pengiriman', 'active'),
  (5, 'EMP-004', 'Nina Anggraini', 'nina@umkm.test',
   '$2y$12$XipvCrqm4p4Xn.Z920aXReL9ZyQ50xZZ62idI1ICKkDEu6RcZoYbe',
   'employee', 'Admin', 'active'),
  (6, 'EMP-005', 'Andi Nonaktif', 'andi@umkm.test',
   '$2y$12$XipvCrqm4p4Xn.Z920aXReL9ZyQ50xZZ62idI1ICKkDEu6RcZoYbe',
   'employee', 'Satpam', 'inactive');

-- -------------------------------------------------------------
-- 2. work_schedules (id 1..12)
--    Kemarin / hari ini / besok untuk 4 karyawan aktif.
--    Nina (user 5) mulai 09:00 sampai 18:00.
-- -------------------------------------------------------------
INSERT IGNORE INTO work_schedules
  (id, employee_id, work_date, start_time, end_time, created_by)
VALUES
  -- Budi (user 2)
  (1,  2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 1),
  (2,  2, CURDATE(),                           '08:00:00', '17:00:00', 1),
  (3,  2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 1),
  -- Siti (user 3)
  (4,  3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 1),
  (5,  3, CURDATE(),                           '08:00:00', '17:00:00', 1),
  (6,  3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 1),
  -- Rizky (user 4)
  (7,  4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 1),
  (8,  4, CURDATE(),                           '08:00:00', '17:00:00', 1),
  (9,  4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 1),
  -- Nina (user 5, jam berbeda)
  (10, 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:00:00', '18:00:00', 1),
  (11, 5, CURDATE(),                           '09:00:00', '18:00:00', 1),
  (12, 5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '18:00:00', 1);

-- -------------------------------------------------------------
-- 3. attendances (id 1..8)
--    schedule_id diambil lewat subquery agar mengikuti jadwal yang ada.
--    Hari ini: Budi & Siti on_time, Rizky late 20 menit, Nina incomplete.
--    Kemarin:  data lengkap untuk keempat karyawan.
-- -------------------------------------------------------------
INSERT IGNORE INTO attendances
  (id, employee_id, schedule_id, check_in_at, check_out_at, status)
VALUES
  -- Hari ini
  (1, 2, (SELECT id FROM work_schedules WHERE employee_id = 2 AND work_date = CURDATE()),
   CAST(CONCAT(CURDATE(), ' 07:55:00') AS DATETIME),
   CAST(CONCAT(CURDATE(), ' 17:00:00') AS DATETIME), 'on_time'),
  (2, 3, (SELECT id FROM work_schedules WHERE employee_id = 3 AND work_date = CURDATE()),
   CAST(CONCAT(CURDATE(), ' 07:58:00') AS DATETIME),
   CAST(CONCAT(CURDATE(), ' 17:00:00') AS DATETIME), 'on_time'),
  (3, 4, (SELECT id FROM work_schedules WHERE employee_id = 4 AND work_date = CURDATE()),
   CAST(CONCAT(CURDATE(), ' 08:20:00') AS DATETIME), NULL, 'late'),
  (4, 5, (SELECT id FROM work_schedules WHERE employee_id = 5 AND work_date = CURDATE()),
   CAST(CONCAT(CURDATE(), ' 08:58:00') AS DATETIME), NULL, 'incomplete'),
  -- Kemarin
  (5, 2, (SELECT id FROM work_schedules WHERE employee_id = 2 AND work_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 07:50:00') AS DATETIME),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:05:00') AS DATETIME), 'on_time'),
  (6, 3, (SELECT id FROM work_schedules WHERE employee_id = 3 AND work_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 08:00:00') AS DATETIME),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:00:00') AS DATETIME), 'on_time'),
  (7, 4, (SELECT id FROM work_schedules WHERE employee_id = 4 AND work_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 08:15:00') AS DATETIME),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:10:00') AS DATETIME), 'late'),
  (8, 5, (SELECT id FROM work_schedules WHERE employee_id = 5 AND work_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 08:58:00') AS DATETIME),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 18:02:00') AS DATETIME), 'on_time');

-- -------------------------------------------------------------
-- 4. attendance_corrections (id 1..3)
--    1 pending (Budi), 1 approved (Siti), 1 rejected (Rizky).
-- -------------------------------------------------------------
INSERT IGNORE INTO attendance_corrections
  (id, employee_id, attendance_id, requested_date,
   requested_check_in, requested_check_out, reason, status,
   review_note, reviewed_by, reviewed_at)
VALUES
  (1, 2,
   (SELECT id FROM attendances WHERE employee_id = 2 AND schedule_id =
     (SELECT id FROM work_schedules WHERE employee_id = 2 AND work_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY))),
   DATE_SUB(CURDATE(), INTERVAL 1 DAY),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 08:10:00') AS DATETIME), NULL,
   'Lupa tombol check-in, izin dari owner',
   'pending', NULL, NULL, NULL),
  (2, 3,
   (SELECT id FROM attendances WHERE employee_id = 3 AND schedule_id =
     (SELECT id FROM work_schedules WHERE employee_id = 3 AND work_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY))),
   DATE_SUB(CURDATE(), INTERVAL 1 DAY),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 08:05:00') AS DATETIME),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:00:00') AS DATETIME),
   'Pembetulan jam pulang setelah lembur',
   'approved', 'Disetujui owner', 1,
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 18:00:00') AS DATETIME)),
  (3, 4,
   (SELECT id FROM attendances WHERE employee_id = 4 AND schedule_id =
     (SELECT id FROM work_schedules WHERE employee_id = 4 AND work_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY))),
   DATE_SUB(CURDATE(), INTERVAL 1 DAY),
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 08:00:00') AS DATETIME), NULL,
   'Koreksi keterlambatan karena macet',
   'rejected', 'Tidak disetujui, bukti tidak lengkap', 1,
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:30:00') AS DATETIME));

-- -------------------------------------------------------------
-- 5. tasks (id 1..6)
--    Deadline: kemarin .. +5 hari. Tugas id 2 lewat tenggat & in_progress.
-- -------------------------------------------------------------
INSERT IGNORE INTO tasks
  (id, title, description, deadline, assigned_to, created_by, status,
   progress_percent)
VALUES
  (1, 'Input stok harian', 'Perbarui stok barang di sistem kasir.',
   CAST(CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 17:00:00') AS DATETIME),
   2, 1, 'assigned', 0),
  (2, 'Cek stok gudang', 'Verifikasi jumlah stok dengan catatan gudang.',
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:00:00') AS DATETIME),
   3, 1, 'in_progress', 35),
  (3, 'Serahkan laporan pengiriman', 'Rekap pengiriman hari ini.',
   CAST(CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 12:00:00') AS DATETIME),
   4, 1, 'submitted', 100),
  (4, 'Rapikan laporan keuangan', 'Sesuaikan format laporan bulanan.',
   CAST(CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 17:00:00') AS DATETIME),
   5, 1, 'revision', 60),
  (5, 'Arsipkan nota pembelian', 'Scan dan simpan nota supplier.',
   CAST(CONCAT(DATE_ADD(CURDATE(), INTERVAL 5 DAY), ' 17:00:00') AS DATETIME),
   2, 1, 'completed', 100),
  (6, 'Persiapkan jadwal kurir', 'Atur jadwal pengiriman minggu depan.',
   CAST(CONCAT(DATE_ADD(CURDATE(), INTERVAL 4 DAY), ' 17:00:00') AS DATETIME),
   3, 1, 'in_progress', 25);

-- -------------------------------------------------------------
-- 6. task_progress_logs (id 1..3) - log tugas in_progress/submitted
-- -------------------------------------------------------------
INSERT IGNORE INTO task_progress_logs
  (id, task_id, updated_by, progress_percent, status_snapshot, notes, created_at)
VALUES
  (1, 2, 3, 15, 'in_progress', 'Mulai pengecekan stok gudang',
   CAST(CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 09:15:00') AS DATETIME)),
  (2, 2, 3, 35, 'in_progress', 'Sebagian stok sudah diverifikasi',
   CAST(CONCAT(CURDATE(), ' 08:30:00') AS DATETIME)),
  (3, 3, 4, 100, 'submitted', 'Laporan pengiriman dikumpulkan',
   CAST(CONCAT(CURDATE(), ' 10:30:00') AS DATETIME));

-- -------------------------------------------------------------
-- 7. task_evidences (id 1..2) - berkas contoh (fisik tidak dibuat)
-- -------------------------------------------------------------
INSERT IGNORE INTO task_evidences
  (id, task_id, uploaded_by, file_name, file_path, mime_type, file_size_bytes)
VALUES
  (1, 3, 4, 'contoh-bukti.jpg',
   'uploads/evidence/contoh-bukti.jpg', 'image/jpeg', 184320),
  (2, 4, 5, 'contoh-laporan.pdf',
   'uploads/evidence/contoh-laporan.pdf', 'application/pdf', 245760);
