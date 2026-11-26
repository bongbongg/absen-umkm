<?php
$legend = [
    'FR-01' => 'Login Owner', 'FR-02' => 'Login Karyawan',
    'FR-03' => 'Dashboard Owner', 'FR-04' => 'Aktivitas & Status Tugas',
    'FR-05' => 'Jadwal Kerja', 'FR-06' => 'Check-In / Check-Out',
    'FR-07' => 'Riwayat Absensi', 'FR-08' => 'Pengajuan Koreksi',
    'FR-09' => 'Persetujuan Koreksi', 'FR-10' => 'Daftar Tugas',
    'FR-11' => 'Update Progres 0–100%', 'FR-12' => 'Upload Bukti Kerja',
    'FR-13' => 'Review & Persetujuan', 'FR-14' => 'Filter Laporan',
    'FR-15' => 'Ekspor PDF / Excel',
];
$backPage = currentUser() ? 'dashboard' : 'login';
?>
<div class="wf-sheet">
    <div class="wf-sheet__head">
        <div>
            <div class="wf-tag wf-tag--filled">WIREFRAME / LO-FI</div>
            <h1 class="wf-sheet__title">Monitoring Pekerjaan &amp; Absensi Karyawan UMKM</h1>
            <div class="wf-sheet__meta">15 alur inti · palet monokrom · sketsa layout · responsif hingga 360 px · bukan desain final</div>
        </div>
        <div class="wf-nav" style="border:0;padding:0">
            <a class="wf-btn" href="<?= e(url($backPage)) ?>">← Kembali ke aplikasi</a>
            <a class="wf-btn wf-btn--filled" href="<?= e(url($backPage, ['wireframe' => 1])) ?>">Buka aplikasi: Lo-Fi ON</a>
        </div>
    </div>

    <div class="wf-legend" aria-label="Daftar kode fitur wireframe">
        <?php foreach ($legend as $code => $label) : ?>
            <span class="wf-tag" title="<?= e($label) ?>"><?= e($code) ?> · <?= e($label) ?></span>
        <?php endforeach; ?>
    </div>

    <p class="wf-note">
        Konvensi: <span class="wf-tag">FR-xx</span> = kode alur fitur ·
        kotak dashed = placeholder (grafik, foto/bukti, avatar) ·
        kotak solid = elemen interaktif ·
        arsiran = area gambar/statik. Mode Lo-Fi juga bisa dinyalakan di aplikasi asli lewat tombol <strong>Lo-Fi: ON/OFF</strong> di header.
    </p>

    <!-- ============================ LOGIN ============================ -->
    <section class="wf-screen" data-fr="FR-01 / FR-02">
        <div class="wf-screen__head">
            <span>01 · Halaman Login — Owner &amp; Karyawan</span>
            <span><span class="wf-tag">FR-01 Login Owner</span> <span class="wf-tag">FR-02 Login Karyawan</span></span>
        </div>
        <div class="wf-screen__body">
            <?php foreach ([
                ['FR-01', 'LOGIN — OWNER', 'owner@umkm.test', 'Dashboard owner · metrik &amp; laporan'],
                ['FR-02', 'LOGIN — KARYAWAN', 'budi@umkm.test', 'Absensi · tugas · koreksi'],
            ] as [$code, $heading, $email, $hint]) : ?>
                <div class="wf-block" data-fr="<?= e($code) ?>">
                    <h2 class="wf-block__title"><?= e($heading) ?></h2>
                    <div class="row" style="gap:8px;margin-bottom:12px">
                        <span class="wf-avatar">UM</span>
                        <div>
                            <div class="wf-line wf-line--mid"></div>
                            <div class="wf-line wf-line--short"></div>
                        </div>
                    </div>
                    <div class="wf-row"><span>EMAIL / USERNAME</span><span>[ input teks ]</span></div>
                    <div class="wf-row"><span>KATA SANDI</span><span>[ input password ]</span></div>
                    <div class="wf-row"><span><?= e($email) ?></span><span><?= e($hint) ?></span></div>
                    <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                        <span class="wf-btn wf-btn--filled">MASUK →</span>
                        <span class="wf-btn wf-btn--dashed">Lupa sandi?</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ========================= DASHBOARD ========================= -->
    <section class="wf-screen" data-fr="FR-03 / FR-04">
        <div class="wf-screen__head">
            <span>02 · Dashboard Owner — Ringkasan &amp; Aktivitas</span>
            <span><span class="wf-tag">FR-03 Ringkasan</span> <span class="wf-tag">FR-04 Aktivitas</span></span>
        </div>
        <div class="wf-screen__body wf-screen__body--wide">
            <div class="wf-block" style="border-style:solid">
                <div class="wf-nav">
                    <span class="wf-btn wf-btn--filled">☰ MENU</span>
                    <span class="wf-btn is-active">Dashboard</span>
                    <span class="wf-btn">Karyawan</span>
                    <span class="wf-btn">Jadwal</span>
                    <span class="wf-btn">Absensi</span>
                    <span class="wf-btn">Koreksi</span>
                    <span class="wf-btn">Tugas</span>
                    <span class="wf-btn">Laporan</span>
                </div>
                <div class="wf-row" style="border:0">
                    <span>[ HEADER: judul halaman · pencarian · avatar pengguna · tombol Lo-Fi ]</span>
                    <span class="wf-avatar">O</span>
                </div>
            </div>

            <div class="wf-block" data-fr="FR-03">
                <h2 class="wf-block__title">Kartu ringkasan hari ini</h2>
                <div class="wf-stat-row">
                    <?php foreach ([
                        ['HADIR', '18'], ['TERLAMBAT', '3'], ['KOREKSI PENDING', '2'], ['TUGAS AKTIF', '7'],
                    ] as [$label, $value]) : ?>
                        <div class="wf-stat"><span><?= e($label) ?></span><strong><?= e($value) ?></strong><span>[ angka metrik ]</span></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="wf-block wf-chart" data-fr="FR-03"></div>

            <div class="wf-block" data-fr="FR-04">
                <h2 class="wf-block__title">Aktivitas terbaru &amp; status tugas</h2>
                <?php foreach ([
                    ['08:12', 'Budi Santoso check-in · Hadir', 'badge: HADIR'],
                    ['08:35', 'Pengajuan koreksi menunggu · Siti', 'badge: PENDING'],
                    ['09:05', 'Tugas «Rekap stok» progres 60%', 'badge: BERJALAN'],
                    ['10:20', 'Bukti pekerjaan diunggah · Rizky', 'badge: MENUNGGU'],
                ] as [$time, $text, $badge]) : ?>
                    <div class="wf-row"><span><?= e($time) ?> · <?= e($text) ?></span><span class="wf-tag"><?= e($badge) ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ==================== ABSENSI & RIWAYAT ==================== -->
    <section class="wf-screen" data-fr="FR-05 / FR-06 / FR-07">
        <div class="wf-screen__head">
            <span>03 · Absensi — Jadwal, Check-In/Out, Riwayat</span>
            <span><span class="wf-tag">FR-05 Jadwal</span> <span class="wf-tag">FR-06 Check-In/Out</span> <span class="wf-tag">FR-07 Riwayat</span></span>
        </div>
        <div class="wf-screen__body">
            <div class="wf-block" data-fr="FR-06">
                <h2 class="wf-block__title">Absensi karyawan — jadwal hari ini</h2>
                <div class="wf-row"><span>26 Nov 2026 · 08:00–17:00</span><span class="wf-tag">HARI INI</span></div>
                <div class="wf-row"><span>Check-in</span><span>[ 08:02 ] · otomatis</span></div>
                <div class="wf-row"><span>Check-out</span><span>[ 17:05 ] · otomatis</span></div>
                <div class="wf-row"><span>Status</span><span class="wf-tag">TERLAMBAT 2 MENIT</span></div>
                <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                    <span class="wf-btn wf-btn--filled">CHECK-IN</span>
                    <span class="wf-btn">CHECK-OUT</span>
                    <span class="wf-btn wf-btn--dashed">Ajukan koreksi</span>
                </div>
            </div>

            <div class="wf-block" data-fr="FR-05">
                <h2 class="wf-block__title">Jadwal kerja (owner)</h2>
                <div class="wf-row"><span>[ avatar ] Budi · EMP-001</span><span>08:00–17:00</span></div>
                <div class="wf-row"><span>[ avatar ] Siti · EMP-002</span><span>09:00–18:00</span></div>
                <div class="wf-row"><span>[ avatar ] Rizky · EMP-003</span><span>08:00–16:00</span></div>
                <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                    <span class="wf-btn wf-btn--filled">+ TAMBAH JADWAL</span>
                    <span class="wf-btn wf-btn--dashed">Filter tanggal</span>
                </div>
            </div>

            <div class="wf-block wf-screen__body--wide" data-fr="FR-07" style="grid-column:1/-1">
                <h2 class="wf-block__title">Riwayat absensi — tabel dengan filter</h2>
                <div class="wf-nav" style="margin-bottom:10px">
                    <span class="wf-btn">Dari: [ tanggal ]</span>
                    <span class="wf-btn">Sampai: [ tanggal ]</span>
                    <span class="wf-btn">Karyawan: [ pilih ]</span>
                    <span class="wf-btn wf-btn--filled">TERAPKAN</span>
                </div>
                <div class="wf-row"><strong>TANGGAL</strong><strong>KARYAWAN</strong><strong>MASUK</strong><strong>PULANG</strong><strong>STATUS</strong></div>
                <?php foreach ([
                    ['26 Nov 2026', 'Budi', '08:02', '17:05', 'TERLAMBAT'],
                    ['25 Nov 2026', 'Siti', '09:01', '18:00', 'HADIR'],
                    ['24 Nov 2026', 'Budi', '08:00', '17:02', 'HADIR'],
                ] as $row) : ?>
                    <div class="wf-row">
                        <?php foreach ($row as $cell) : ?><span><?= e($cell) ?></span><?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ========================== KOREKSI ========================== -->
    <section class="wf-screen" data-fr="FR-08 / FR-09">
        <div class="wf-screen__head">
            <span>04 · Koreksi Absensi — Pengajuan &amp; Persetujuan</span>
            <span><span class="wf-tag">FR-08 Pengajuan</span> <span class="wf-tag">FR-09 Persetujuan</span></span>
        </div>
        <div class="wf-screen__body">
            <div class="wf-block" data-fr="FR-08">
                <h2 class="wf-block__title">Form pengajuan (karyawan)</h2>
                <div class="wf-row"><span>TANGGAL YANG DIKOREKSI</span><span>[ tanggal ]</span></div>
                <div class="wf-row"><span>JAM MASUK DIAJUKAN</span><span>[ 08:00 ]</span></div>
                <div class="wf-row"><span>JAM PULANG DIAJUKAN</span><span>[ 17:00 ]</span></div>
                <div class="wf-row"><span>ALASAN</span><span>[ textarea ]</span></div>
                <div class="wf-line wf-line--mid"></div>
                <div class="wf-line wf-line--short"></div>
                <div style="margin-top:12px"><span class="wf-btn wf-btn--filled">KIRIM PENGAJUAN</span></div>
            </div>

            <div class="wf-block" data-fr="FR-09">
                <h2 class="wf-block__title">Review &amp; persetujuan (owner)</h2>
                <div class="wf-row"><span>[ avatar ] Siti · 24 Nov</span><span class="wf-tag">PENDING</span></div>
                <div class="wf-row"><span>Lama: 09:10 → Baru: 09:00</span><span>[ alasan ]</span></div>
                <div class="wf-row"><span>Catatan review</span><span>[ input ]</span></div>
                <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                    <span class="wf-btn wf-btn--filled">SETUJUI</span>
                    <span class="wf-btn wf-btn--dashed">TOLAK</span>
                </div>
                <p class="wf-note" style="margin-top:10px">Setelah disetujui → tabel <strong>attendances</strong> diperbarui (transaksi).</p>
            </div>
        </div>
    </section>

    <!-- ====================== TUGAS & PROGRES ====================== -->
    <section class="wf-screen" data-fr="FR-10 / FR-11 / FR-12 / FR-13">
        <div class="wf-screen__head">
            <span>05 · Tugas &amp; Progres — Daftar, Update, Bukti, Review</span>
            <span><span class="wf-tag">FR-10 Daftar</span> <span class="wf-tag">FR-11 Progres</span> <span class="wf-tag">FR-12 Bukti</span> <span class="wf-tag">FR-13 Review</span></span>
        </div>
        <div class="wf-screen__body">
            <div class="wf-block" data-fr="FR-10">
                <h2 class="wf-block__title">Daftar tugas (status &amp; progres)</h2>
                <div class="wf-row"><strong>TUGAS</strong><strong>KARYAWAN</strong><strong>PROGRES</strong><strong>STATUS</strong></div>
                <?php foreach ([
                    ['Rekap penjualan', 'Budi', '60%', 'BERJALAN'],
                    ['Input stok gudang', 'Rizky', '100%', 'MENUNGGU'],
                    ['Foto display toko', 'Siti', '0%', 'BELUM DIMULAI'],
                ] as $row) : ?>
                    <div class="wf-row">
                        <?php foreach ($row as $cell) : ?><span><?= e($cell) ?></span><?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                    <span class="wf-btn wf-btn--filled">+ BUAT TUGAS</span>
                    <span class="wf-btn wf-btn--dashed">Filter status</span>
                </div>
            </div>

            <div class="wf-block" data-fr="FR-11">
                <h2 class="wf-block__title">Update progres 0–100% (karyawan)</h2>
                <div class="wf-row"><span>PERSENTASE</span><span>[ 0 --------●-------- 100 ] = 60%</span></div>
                <div class="wf-row"><span>STATUS</span><span>[ pilih: berjalan ]</span></div>
                <div class="wf-row"><span>CATATAN</span><span>[ textarea ]</span></div>
                <div style="margin-top:12px"><span class="wf-btn wf-btn--filled">SIMPAN PROGRES</span></div>
                <p class="wf-note" style="margin-top:10px">Setiap perubahan masuk ke <strong>task_progress_logs</strong>.</p>
            </div>

            <div class="wf-block wf-photo" data-fr="FR-12">
                <div style="position:absolute;top:8px;left:8px" class="wf-tag">FR-12 Upload bukti</div>
            </div>

            <div class="wf-block" data-fr="FR-12">
                <h2 class="wf-block__title">Unggah bukti pekerjaan</h2>
                <div class="wf-row"><span>FILE (JPG/PNG/PDF/DOC)</span><span>[ pilih berkas ]</span></div>
                <div class="wf-row"><span>Ukuran maksimal</span><span>5 MB · MIME divalidasi</span></div>
                <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                    <span class="wf-btn">UNGGAH</span>
                    <span class="wf-btn wf-btn--filled">AJUKAN SELESAI</span>
                </div>
            </div>

            <div class="wf-block" data-fr="FR-13" style="grid-column:1/-1">
                <h2 class="wf-block__title">Review &amp; persetujuan owner</h2>
                <div class="wf-row"><span>[ avatar ] Rizky · bukti #2 terlampir</span><span class="wf-tag">MENUNGGU PERSETUJUAN</span></div>
                <div class="wf-row"><span>Catatan revisi (jika ditolak)</span><span>[ textarea ]</span></div>
                <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                    <span class="wf-btn wf-btn--filled">✓ SETUJUI SELESAI</span>
                    <span class="wf-btn wf-btn--dashed">↻ KEMBALIKAN UNTUK REVISI</span>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================== LAPORAN =========================== -->
    <section class="wf-screen" data-fr="FR-14 / FR-15">
        <div class="wf-screen__head">
            <span>06 · Laporan — Filter Periode &amp; Ekspor</span>
            <span><span class="wf-tag">FR-14 Filter</span> <span class="wf-tag">FR-15 Ekspor</span></span>
        </div>
        <div class="wf-screen__body wf-screen__body--wide">
            <div class="wf-block" data-fr="FR-14">
                <h2 class="wf-block__title">Filter laporan</h2>
                <div class="wf-nav">
                    <span class="wf-btn wf-btn--filled">JENIS: [ absensi / tugas ]</span>
                    <span class="wf-btn">DARI: [ tanggal ]</span>
                    <span class="wf-btn">SAMPAI: [ tanggal ]</span>
                    <span class="wf-btn">TAMPILKAN</span>
                </div>
            </div>

            <div class="wf-block" data-fr="FR-15">
                <h2 class="wf-block__title">Ekspor &amp; pratinjau data</h2>
                <div class="wf-nav" style="margin-bottom:10px">
                    <span class="wf-btn wf-btn--filled">↓ EKSPOR PDF</span>
                    <span class="wf-btn">↓ EKSPOR EXCEL (CSV)</span>
                </div>
                <div class="wf-row"><strong>ID</strong><strong>TANGGAL</strong><strong>KARYAWAN</strong><strong>MASUK</strong><strong>PULANG</strong><strong>STATUS</strong></div>
                <?php foreach ([
                    ['#1', '26 Nov 2026', 'Budi', '08:02', '17:05', 'TERLAMBAT'],
                    ['#2', '25 Nov 2026', 'Siti', '09:01', '18:00', 'HADIR'],
                ] as $row) : ?>
                    <div class="wf-row">
                        <?php foreach ($row as $cell) : ?><span><?= e($cell) ?></span><?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="wf-note" style="border-top:2px solid #111;padding-top:12px">
        Catatan: wireframe ini adalah dokumentasi visual statis. Tidak ada data yang diubah dan tidak ada query database yang dijalankan.
        Untuk melihat sketsa pada halaman aplikasi asli, nyalakan <strong>Lo-Fi: ON</strong> di header (tersimpan di session selama browsing).
    </div>
</div>
