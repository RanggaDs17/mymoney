<?php
/**
 * ===================================================
 * LAPORAN KEUANGAN (reports/index.php)
 * ===================================================
 * Menghasilkan laporan pemasukan & pengeluaran berdasarkan rentang tanggal,
 * rincian per kategori, statistik rata-rata, grafik visualisasi,
 * serta tabel rincian transaksi lengkap (Tanggal, Jenis, Kategori, Nominal, Catatan, Aksi).
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$page_title   = "Laporan Keuangan";
$page_heading = "Laporan Keuangan";

// CSRF Token Init
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Flash Messages
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Default Filter: Bulan Ini
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-t');

function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

try {
    // 1. Total Pemasukan & Pengeluaran pada Periode Terpilih
    $stmtTotals = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense,
            COUNT(*) AS total_transactions
        FROM transactions
        WHERE user_id = :user_id AND transaction_date BETWEEN :start_date AND :end_date
    ");
    $stmtTotals->execute([
        ':user_id'    => $userId,
        ':start_date' => $startDate,
        ':end_date'   => $endDate
    ]);
    $totals = $stmtTotals->fetch();

    $reportIncome  = (float)$totals['total_income'];
    $reportExpense = (float)$totals['total_expense'];
    $reportNet     = $reportIncome - $reportExpense;
    $totalCount    = (int)$totals['total_transactions'];

    // Hitung rata-rata pengeluaran harian dalam periode
    $dStart = new DateTime($startDate);
    $dEnd   = new DateTime($endDate);
    $daysDiff = max(1, $dStart->diff($dEnd)->days + 1);
    $dailyAvgExpense = $reportExpense / $daysDiff;

    // 2. Rincian Pengeluaran Per Kategori
    $stmtCatExpense = $pdo->prepare("
        SELECT c.name AS category_name, SUM(t.amount) AS total_amount, COUNT(t.id) AS trans_count
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = :user_id AND t.type = 'expense' 
        AND t.transaction_date BETWEEN :start_date AND :end_date
        GROUP BY c.id, c.name
        ORDER BY total_amount DESC
    ");
    $stmtCatExpense->execute([
        ':user_id'    => $userId,
        ':start_date' => $startDate,
        ':end_date'   => $endDate
    ]);
    $categoryExpenses = $stmtCatExpense->fetchAll();

    // 3. Rincian Pemasukan Per Kategori
    $stmtCatIncome = $pdo->prepare("
        SELECT c.name AS category_name, SUM(t.amount) AS total_amount, COUNT(t.id) AS trans_count
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = :user_id AND t.type = 'income' 
        AND t.transaction_date BETWEEN :start_date AND :end_date
        GROUP BY c.id, c.name
        ORDER BY total_amount DESC
    ");
    $stmtCatIncome->execute([
        ':user_id'    => $userId,
        ':start_date' => $startDate,
        ':end_date'   => $endDate
    ]);
    $categoryIncomes = $stmtCatIncome->fetchAll();

    // 4. Daftar Transaksi Detail Periode Terpilih
    $stmtDetailTrans = $pdo->prepare("
        SELECT t.*, c.name AS category_name 
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = :user_id 
        AND t.transaction_date BETWEEN :start_date AND :end_date
        ORDER BY t.transaction_date DESC, t.id DESC
    ");
    $stmtDetailTrans->execute([
        ':user_id'    => $userId,
        ':start_date' => $startDate,
        ':end_date'   => $endDate
    ]);
    $reportTransactions = $stmtDetailTrans->fetchAll();

    // Data Chart Pie/Donut Expense
    $pieLabels = [];
    $pieData   = [];
    foreach ($categoryExpenses as $ce) {
        $pieLabels[] = $ce['category_name'];
        $pieData[]   = (float)$ce['total_amount'];
    }

} catch (PDOException $e) {
    error_log("Report Query Error: " . $e->getMessage());
    $reportIncome = $reportExpense = $reportNet = $dailyAvgExpense = 0;
    $categoryExpenses = $categoryIncomes = $reportTransactions = [];
    $pieLabels = $pieData = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Alert Feedback -->
<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4 no-print" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($flashSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 no-print" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($flashError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Header Actions -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 no-print">
    <div>
        <h5 class="fw-bold mb-1">Laporan Ringkasan Keuangan</h5>
        <p class="text-muted small mb-0">Analisis arus kas masuk, pengeluaran per kategori, dan rincian transaksi Anda.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- Filter Date Form -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4 no-print">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-12 col-sm-5 col-md-4">
                <label for="start_date" class="form-label small fw-semibold">Dari Tanggal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" required>
            </div>
            <div class="col-12 col-sm-5 col-md-4">
                <label for="end_date" class="form-label small fw-semibold">Sampai Tanggal</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" required>
            </div>
            <div class="col-12 col-sm-2 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-filter me-1"></i> Tampilkan Laporan</button>
                <a href="index.php" class="btn btn-light border" title="Reset Periode Bulan Ini"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Printable Header (Visible only on print) -->
<div class="d-none d-print-block mb-4">
    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">MyMoney - Laporan Keuangan</h3>
            <p class="mb-0 text-muted small">Aplikasi Pencatat Keuangan Pribadi</p>
        </div>
        <div class="text-end small text-muted">
            <div><strong>Pemilik Akun:</strong> <?= htmlspecialchars($userName) ?></div>
            <div><strong>Periode Laporan:</strong> <?= date('d/m/Y', strtotime($startDate)) ?> s/d <?= date('d/m/Y', strtotime($endDate)) ?></div>
            <div><strong>Tanggal Cetak:</strong> <?= date('d/m/Y H:i') ?></div>
        </div>
    </div>
</div>

<!-- 4 Stat Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-success-subtle text-success">
                    <i class="bi bi-arrow-down-left-circle-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Total Pemasukan</span>
                    <h5 class="fw-bold text-success mb-0"><?= format_rupiah($reportIncome) ?></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-danger-subtle text-danger">
                    <i class="bi bi-arrow-up-right-circle-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Total Pengeluaran</span>
                    <h5 class="fw-bold text-danger mb-0"><?= format_rupiah($reportExpense) ?></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-primary-subtle text-primary">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Arus Kas Bersih</span>
                    <h5 class="fw-bold mb-0 <?= $reportNet >= 0 ? 'text-dark' : 'text-danger' ?>"><?= format_rupiah($reportNet) ?></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-info-subtle text-info">
                    <i class="bi bi-calendar2-day-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Rata-Rata Pengeluaran/Hari</span>
                    <h5 class="fw-bold text-dark mb-0"><?= format_rupiah($dailyAvgExpense) ?></h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart & Breakdown Section -->
<div class="row g-4 mb-4">
    <!-- Chart Donut Category Expense -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-3 p-md-4">
            <h6 class="fw-bold text-dark mb-3">
                <i class="bi bi-pie-chart-fill text-danger me-2"></i>Distribusi Pengeluaran
            </h6>
            <div class="position-relative d-flex align-items-center justify-content-center" style="height: 280px;">
                <?php if (empty($pieData)): ?>
                    <div class="text-center text-muted p-4">
                        <i class="bi bi-info-circle fs-2 d-block mb-2 text-white-50"></i>
                        <span class="small">Belum ada pengeluaran pada periode ini.</span>
                    </div>
                <?php else: ?>
                    <canvas id="reportExpenseChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Category Expense Table Breakdown -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-list-task me-2 text-primary"></i>Rincian Pengeluaran per Kategori
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Kategori</th>
                            <th class="text-center">Jumlah Transaksi</th>
                            <th class="text-end">Total Nominal</th>
                            <th class="pe-4 text-end">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categoryExpenses)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Tidak ada data pengeluaran.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categoryExpenses as $ce): ?>
                                <?php $pct = $reportExpense > 0 ? round(($ce['total_amount'] / $reportExpense) * 100, 1) : 0; ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($ce['category_name']) ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border px-2.5 py-1"><?= $ce['trans_count'] ?> kali</span></td>
                                    <td class="text-end fw-bold text-danger"><?= format_rupiah($ce['total_amount']) ?></td>
                                    <td class="pe-4 text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <div class="progress rounded-pill flex-grow-1 d-none d-sm-flex" style="height: 6px; min-width: 60px;">
                                                <div class="progress-bar bg-danger" style="width: <?= min(100, $pct) ?>%"></div>
                                            </div>
                                            <span class="small fw-semibold text-muted" style="min-width: 42px;"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Rincian Pemasukan Per Kategori -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-wallet2 me-2 text-success"></i>Rincian Pemasukan per Kategori
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Kategori Pemasukan</th>
                            <th class="text-center">Jumlah Transaksi</th>
                            <th class="text-end">Total Nominal</th>
                            <th class="pe-4 text-end">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categoryIncomes)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Tidak ada data pemasukan pada periode ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categoryIncomes as $ci): ?>
                                <?php $pctInc = $reportIncome > 0 ? round(($ci['total_amount'] / $reportIncome) * 100, 1) : 0; ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($ci['category_name']) ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border px-2.5 py-1"><?= $ci['trans_count'] ?> kali</span></td>
                                    <td class="text-end fw-bold text-success"><?= format_rupiah($ci['total_amount']) ?></td>
                                    <td class="pe-4 text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <div class="progress rounded-pill flex-grow-1 d-none d-sm-flex" style="height: 6px; min-width: 60px;">
                                                <div class="progress-bar bg-success" style="width: <?= min(100, $pctInc) ?>%"></div>
                                            </div>
                                            <span class="small fw-semibold text-muted" style="min-width: 42px;"><?= $pctInc ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- TABEL DETAIL TRANSAKSI LENGKAP (TANGGAL, JENIS, KATEGORI, NOMINAL, CATATAN, AKSI) -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-receipt-cutoff me-2 text-primary"></i>Rincian Transaksi Laporan Periode Ini (<?= count($reportTransactions) ?> Transaksi)
                </h6>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Jenis</th>
                            <th>Kategori</th>
                            <th>Nominal</th>
                            <th>Catatan</th>
                            <th class="pe-4 text-end no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportTransactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-3 d-block mb-2 text-white-50"></i>
                                    Tidak ada transaksi tercatat pada periode tanggal terpilih.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reportTransactions as $t): ?>
                                <tr>
                                    <td class="ps-4 text-nowrap fw-medium">
                                        <?= date('d/m/Y', strtotime($t['transaction_date'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($t['type'] === 'income'): ?>
                                            <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-2 fw-semibold">
                                                <i class="bi bi-arrow-down-left"></i> Pemasukan
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger px-2.5 py-1 rounded-2 fw-semibold">
                                                <i class="bi bi-arrow-up-right"></i> Pengeluaran
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold text-dark">
                                        <?= htmlspecialchars($t['category_name']) ?>
                                    </td>
                                    <td class="fw-bold <?= $t['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                                        <?= $t['type'] === 'income' ? '+' : '-' ?> <?= format_rupiah($t['amount']) ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= !empty($t['note']) ? htmlspecialchars($t['note']) : '<span class="text-white-50">-</span>' ?>
                                    </td>
                                    <td class="pe-4 text-end no-print">
                                        <div class="btn-group btn-group-sm">
                                            <a href="../transactions/edit.php?id=<?= $t['id'] ?>" class="btn btn-light border text-muted" title="Edit Transaksi">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="../transactions/delete.php?id=<?= $t['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                               class="btn btn-light border text-danger" 
                                               title="Hapus Transaksi"
                                               onclick="return confirmDelete(event, 'Apakah Anda yakin ingin menghapus transaksi ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($pieData)): ?>
    const ctxReport = document.getElementById('reportExpenseChart').getContext('2d');
    new Chart(ctxReport, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($pieLabels) ?>,
            datasets: [{
                data: <?= json_encode($pieData) ?>,
                backgroundColor: [
                    '#ef4444', '#f59e0b', '#3b82f6', '#10b981', '#8b5cf6',
                    '#ec4899', '#06b6d4', '#84cc16', '#64748b', '#d97706'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
