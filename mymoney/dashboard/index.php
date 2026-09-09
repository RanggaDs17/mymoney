<?php
/**
 * ===================================================
 * HALAMAN DASHBOARD UTAMA (dashboard/index.php)
 * ===================================================
 * Menampilkan ringkasan keuangan user: Saldo Total, Pemasukan Bulan Ini,
 * Pengeluaran Bulan Ini, Jumlah Transaksi, Grafik Chart.js, dan 5 Transaksi Terbaru.
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Helper function untuk merubah angka desimal DB menjadi format Rupiah
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Tanggal awal & akhir bulan berjalan (misal: 2026-09-01 s/d 2026-09-30)
$firstDayOfMonth = date('Y-m-01');
$lastDayOfMonth  = date('Y-m-t');

try {
    // 1. Hitung Total Pemasukan (All Time) untuk User Ini
    $stmtIncomeAll = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total 
        FROM transactions 
        WHERE user_id = :user_id AND type = 'income'
    ");
    $stmtIncomeAll->execute([':user_id' => $userId]);
    $totalIncomeAll = $stmtIncomeAll->fetch()['total'];

    // 2. Hitung Total Pengeluaran (All Time) untuk User Ini
    $stmtExpenseAll = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total 
        FROM transactions 
        WHERE user_id = :user_id AND type = 'expense'
    ");
    $stmtExpenseAll->execute([':user_id' => $userId]);
    $totalExpenseAll = $stmtExpenseAll->fetch()['total'];

    // Saldo Saat Ini = Total Pemasukan - Total Pengeluaran
    $currentBalance = $totalIncomeAll - $totalExpenseAll;

    // 3. Hitung Pemasukan Bulan Ini
    $stmtIncomeMonth = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total 
        FROM transactions 
        WHERE user_id = :user_id AND type = 'income' 
        AND transaction_date BETWEEN :start_date AND :end_date
    ");
    $stmtIncomeMonth->execute([
        ':user_id'    => $userId,
        ':start_date' => $firstDayOfMonth,
        ':end_date'   => $lastDayOfMonth
    ]);
    $incomeMonth = $stmtIncomeMonth->fetch()['total'];

    // 4. Hitung Pengeluaran Bulan Ini
    $stmtExpenseMonth = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total 
        FROM transactions 
        WHERE user_id = :user_id AND type = 'expense' 
        AND transaction_date BETWEEN :start_date AND :end_date
    ");
    $stmtExpenseMonth->execute([
        ':user_id'    => $userId,
        ':start_date' => $firstDayOfMonth,
        ':end_date'   => $lastDayOfMonth
    ]);
    $expenseMonth = $stmtExpenseMonth->fetch()['total'];

    // 5. Hitung Total Jumlah Transaksi Bulan Ini
    $stmtCountMonth = $pdo->prepare("
        SELECT COUNT(*) AS total_count 
        FROM transactions 
        WHERE user_id = :user_id 
        AND transaction_date BETWEEN :start_date AND :end_date
    ");
    $stmtCountMonth->execute([
        ':user_id'    => $userId,
        ':start_date' => $firstDayOfMonth,
        ':end_date'   => $lastDayOfMonth
    ]);
    $transactionCountMonth = $stmtCountMonth->fetch()['total_count'];

    // 6. Ambil 5 Transaksi Terbaru (Join dengan tabel categories)
    $stmtRecent = $pdo->prepare("
        SELECT t.*, c.name AS category_name 
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = :user_id
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT 5
    ");
    $stmtRecent->execute([':user_id' => $userId]);
    $recentTransactions = $stmtRecent->fetchAll();

    // 7. Ambil Ringkasan Pengeluaran Per Kategori (Untuk Pie Chart)
    $stmtCategoryPie = $pdo->prepare("
        SELECT c.name AS category_name, SUM(t.amount) AS total_amount
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = :user_id AND t.type = 'expense'
        GROUP BY c.id, c.name
        ORDER BY total_amount DESC
    ");
    $stmtCategoryPie->execute([':user_id' => $userId]);
    $categoryPieData = $stmtCategoryPie->fetchAll();

    // Siapkan array data untuk Chart.js (Donut/Pie Chart)
    $pieLabels = [];
    $pieAmounts = [];
    foreach ($categoryPieData as $item) {
        $pieLabels[]  = $item['category_name'];
        $pieAmounts[] = (float)$item['total_amount'];
    }

    // 8. Data Tren Pemasukan vs Pengeluaran 6 Bulan Terakhir (Bar/Line Chart)
    $monthlyLabels = [];
    $monthlyIncome = [];
    $monthlyExpense = [];

    for ($i = 5; $i >= 0; $i--) {
        $monthYear = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M Y', strtotime("-$i months"));
        $startDate = $monthYear . '-01';
        $endDate   = date('Y-m-t', strtotime($startDate));

        // Income per bulan
        $sInc = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total 
            FROM transactions 
            WHERE user_id = :user_id AND type = 'income' 
            AND transaction_date BETWEEN :start AND :end
        ");
        $sInc->execute([':user_id' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $incVal = (float)$sInc->fetch()['total'];

        // Expense per bulan
        $sExp = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total 
            FROM transactions 
            WHERE user_id = :user_id AND type = 'expense' 
            AND transaction_date BETWEEN :start AND :end
        ");
        $sExp->execute([':user_id' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $expVal = (float)$sExp->fetch()['total'];

        $monthlyLabels[]  = $monthLabel;
        $monthlyIncome[]  = $incVal;
        $monthlyExpense[] = $expVal;
    }

} catch (PDOException $e) {
    error_log("Dashboard Query Error: " . $e->getMessage());
    die("Terjadi kesalahan saat memuat data dashboard.");
}

// Konfigurasi Judul Halaman untuk Header & Sidebar
$page_title   = "Dashboard Keuangan";
$page_heading = "Dashboard Keuangan";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Welcome Banner -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 bg-primary text-white p-3 p-md-4 rounded-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fw-bold mb-1">Halo, <?= htmlspecialchars($userName) ?>! 👋</h4>
                    <p class="mb-0 text-white-50 small">Berikut adalah ringkasan keuangan pribadi Anda bulan ini (<?= date('F Y') ?>).</p>
                </div>
                <div class="d-none d-md-block fs-1 opacity-50">
                    <i class="bi bi-wallet-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4 Cards Stat Ringkasan -->
<div class="row g-3 mb-4">
    <!-- Card Saldo Total -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-primary-subtle text-primary">
                    <i class="bi bi-bank"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Saldo Saat Ini</span>
                    <h5 class="fw-bold mb-0 <?= $currentBalance >= 0 ? 'text-dark' : 'text-danger' ?>">
                        <?= format_rupiah($currentBalance) ?>
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Pemasukan Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-success-subtle text-success">
                    <i class="bi bi-arrow-down-left-circle-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Pemasukan Bulan Ini</span>
                    <h5 class="fw-bold text-success mb-0">
                        <?= format_rupiah($incomeMonth) ?>
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Pengeluaran Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-danger-subtle text-danger">
                    <i class="bi bi-arrow-up-right-circle-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Pengeluaran Bulan Ini</span>
                    <h5 class="fw-bold text-danger mb-0">
                        <?= format_rupiah($expenseMonth) ?>
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Jumlah Transaksi -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-info-subtle text-info">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Jumlah Transaksi</span>
                    <h5 class="fw-bold text-dark mb-0">
                        <?= number_format($transactionCountMonth) ?> Transaksi
                    </h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section Grafik (Chart.js) -->
<div class="row g-3 mb-4">
    <!-- Bar Chart: Tren Pemasukan vs Pengeluaran 6 Bulan -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i>Tren Keuangan (6 Bulan Terakhir)
                </h6>
            </div>
            <div class="position-relative" style="height: 280px;">
                <canvas id="financialTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Pie/Donut Chart: Pengeluaran per Kategori -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-pie-chart-fill text-danger me-2"></i>Kategori Pengeluaran
                </h6>
            </div>
            <div class="position-relative d-flex align-items-center justify-content-center" style="height: 280px;">
                <?php if (empty($pieLabels)): ?>
                    <div class="text-center text-muted p-4">
                        <i class="bi bi-info-circle fs-2 d-block mb-2 text-white-50"></i>
                        <span class="small">Belum ada data pengeluaran untuk ditampilkan.</span>
                    </div>
                <?php else: ?>
                    <canvas id="categoryPieChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabel 5 Transaksi Terbaru -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-clock-history me-2 text-primary"></i>5 Transaksi Terbaru
                </h6>
                <a href="../transactions/index.php" class="btn btn-sm btn-outline-primary fw-semibold">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Jenis</th>
                            <th>Kategori</th>
                            <th>Nominal</th>
                            <th class="pe-4">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTransactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2 text-white-50"></i>
                                    Belum ada transaksi tercatat. Silakan tambah transaksi baru.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $t): ?>
                                <tr>
                                    <td class="ps-4 text-nowrap fw-medium">
                                        <?= date('d/m/Y', strtotime($t['transaction_date'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($t['type'] === 'income'): ?>
                                            <span class="badge bg-success-subtle text-success px-2 py-1 rounded-2 fw-semibold">
                                                <i class="bi bi-arrow-down-left"></i> Pemasukan
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-2 fw-semibold">
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
                                    <td class="pe-4 text-muted small">
                                        <?= !empty($t['note']) ? htmlspecialchars($t['note']) : '<span class="text-white-50">-</span>' ?>
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

<!-- Inline Script untuk Render Chart.js Dynamic Data -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Render Bar Chart: Tren Keuangan (6 Bulan)
    const ctxTrend = document.getElementById('financialTrendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthlyLabels) ?>,
            datasets: [
                {
                    label: 'Pemasukan (Rp)',
                    data: <?= json_encode($monthlyIncome) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 6
                },
                {
                    label: 'Pengeluaran (Rp)',
                    data: <?= json_encode($monthlyExpense) ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.85)',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });

    // 2. Render Pie/Donut Chart: Pengeluaran per Kategori
    <?php if (!empty($pieLabels)): ?>
    const ctxPie = document.getElementById('categoryPieChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($pieLabels) ?>,
            datasets: [{
                data: <?= json_encode($pieAmounts) ?>,
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
