-- =============================================================
-- schema.sql - Skema database monitoring absensi & tugas UMKM
-- MySQL 8.0, InnoDB, utf8mb4 / utf8mb4_unicode_ci
-- Diselaraskan dengan skema MySQL Workbench yang sudah dipakai.
-- Aman dijalankan berulang: CREATE TABLE IF NOT EXISTS (tanpa DROP).
-- =============================================================
--
-- Catatan: file ini TIDAK menghapus atau mengubah tabel yang sudah ada.
-- Jalankan pada database yang dipilih di .env (DB_DATABASE).

-- -------------------------------------------------------------
-- 1. users - akun owner dan karyawan
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_code VARCHAR(30) NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner', 'employee') NOT NULL DEFAULT 'employee',
  `position` VARCHAR(100) NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY employee_code (employee_code),
  UNIQUE KEY email (email),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. work_schedules - jadwal kerja harian per karyawan
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS work_schedules (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employee_work_date (employee_id, work_date),
  KEY fk_schedules_creator (created_by),
  KEY idx_schedules_date (work_date),
  CONSTRAINT fk_schedules_creator
    FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_schedules_employee
    FOREIGN KEY (employee_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT chk_schedule_time CHECK (end_time > start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. attendances - catatan kehadiran (satu baris per jadwal)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attendances (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  schedule_id BIGINT UNSIGNED NOT NULL,
  check_in_at DATETIME NOT NULL,
  check_out_at DATETIME NULL,
  status ENUM('on_time', 'late', 'incomplete') NOT NULL DEFAULT 'on_time',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_attendance_employee_schedule (employee_id, schedule_id),
  KEY fk_attendances_schedule (schedule_id),
  KEY idx_attendances_checkin (check_in_at),
  CONSTRAINT fk_attendances_employee
    FOREIGN KEY (employee_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_attendances_schedule
    FOREIGN KEY (schedule_id) REFERENCES work_schedules (id) ON DELETE CASCADE,
  CONSTRAINT chk_checkout_after_checkin
    CHECK (check_out_at IS NULL OR check_out_at >= check_in_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. attendance_corrections - permintaan koreksi absensi
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attendance_corrections (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attendance_id BIGINT UNSIGNED NULL,
  employee_id BIGINT UNSIGNED NOT NULL,
  requested_date DATE NOT NULL,
  requested_check_in DATETIME NULL,
  requested_check_out DATETIME NULL,
  reason TEXT NOT NULL,
  status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_note VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_corrections_attendance (attendance_id),
  KEY fk_corrections_employee (employee_id),
  KEY fk_corrections_reviewer (reviewed_by),
  KEY idx_corrections_status (status),
  CONSTRAINT fk_corrections_attendance
    FOREIGN KEY (attendance_id) REFERENCES attendances (id) ON DELETE SET NULL,
  CONSTRAINT fk_corrections_employee
    FOREIGN KEY (employee_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_corrections_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. tasks - tugas yang diberikan owner kepada karyawan
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  deadline DATETIME NOT NULL,
  assigned_to BIGINT UNSIGNED NOT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  status ENUM('assigned', 'in_progress', 'submitted', 'revision', 'completed')
    NOT NULL DEFAULT 'assigned',
  progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_tasks_creator (created_by),
  KEY idx_tasks_assignee_status (assigned_to, status),
  KEY idx_tasks_deadline (deadline),
  CONSTRAINT fk_tasks_assignee
    FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_tasks_creator
    FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT chk_task_progress CHECK (progress_percent <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. task_progress_logs - riwayat perubahan progres tugas
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS task_progress_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id BIGINT UNSIGNED NOT NULL,
  updated_by BIGINT UNSIGNED NOT NULL,
  progress_percent TINYINT UNSIGNED NOT NULL,
  status_snapshot ENUM('assigned', 'in_progress', 'submitted', 'revision', 'completed') NOT NULL,
  notes TEXT NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_progress_logs_task (task_id),
  KEY fk_progress_logs_user (updated_by),
  CONSTRAINT fk_progress_logs_task
    FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
  CONSTRAINT fk_progress_logs_user
    FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT chk_log_progress CHECK (progress_percent <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. task_evidences - berkas bukti pengumpulan tugas
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS task_evidences (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id BIGINT UNSIGNED NOT NULL,
  uploaded_by BIGINT UNSIGNED NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size_bytes BIGINT UNSIGNED NOT NULL,
  uploaded_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_evidences_task (task_id),
  KEY fk_evidences_user (uploaded_by),
  CONSTRAINT fk_evidences_task
    FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
  CONSTRAINT fk_evidences_user
    FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
