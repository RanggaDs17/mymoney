<?php
/**
 * ===================================================
 * KELOLA TRANSAKSI (transactions/index.php)
 * ===================================================
 * Menampilkan daftar transaksi pengguna dengan fitur filter 
 * tipe, kategori, tanggal, pencarian kata kunci, serta summary total.
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$page_title   = "Kelola Transaksi";
$page_heading = "Kelola Transaksi";

// Flash Messages
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// CSRF Token Init
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Filter Parameters
$filterType     = $_GET['type'] ?? 'all';
$filterCategory = (int)($_GET['category_id'] ?? 0);
$filterStart    = $_GET['start_date'] ?? '';
$filterEnd      = $_GET['end_date'] ?? '';
$filterSearch   = trim($_GET['search'] ?? '');

// Helper format Rupiah
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

try {
    // 1. Ambil daftar kategori untuk dropdown filter & modal
    $stmtCategories = $pdo->prepare("
        SELECT id, name, type FROM categories 
        WHERE user_id IS NULL OR user_id = :user_id 
        ORDER BY type ASC, name ASC
    ");
    $stmtCategories->execute([':user_id' => $userId]);
    $categoriesList = $stmtCategories->fetchAll();

    // 2. Susun Query Dinamis Transaksi
    $whereClauses = ["t.user_id = :user_id"];
    $params = [':user_id' => $userId];

    if (in_array($filterType, ['income', 'expense'])) {
        $whereClauses[] = "t.type = :type";
        $params[':type'] = $filterType;
    }

    if ($filterCategory > 0) {
        $whereClauses[] = "t.category_id = :category_id";
        $params[':category_id'] = $filterCategory;
    }

    if (!empty($filterStart)) {
        $whereClauses[] = "t.transaction_date >= :start_date";
        $params[':start_date'] = $filterStart;
    }

    if (!empty($filterEnd)) {
        $whereClauses[] = "t.transaction_date <= :end_date";
        $params[':end_date'] = $filterEnd;
    }

    if (!empty($filterSearch)) {
        $whereClauses[] = "t.note LIKE :search";
        $params[':search'] = '%' . $filterSearch . '%';
    }

    $whereSql = implode(" AND ", $whereClauses);

    // Query daftar transaksi
    $sqlTransactions = "
        SELECT t.*, c.name AS category_name 
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE $whereSql
        ORDER BY t.transaction_date DESC, t.id DESC
    ";
    $stmtTrans = $pdo->prepare($sqlTransactions);
    $stmtTrans->execute($params);
    $transactions = $stmtTrans->fetchAll();

    // Query total summary sesuai filter
    $sqlSummary = "
        SELECT 
            COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) AS total_income,
            COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS total_expense
        FROM transactions t
        WHERE $whereSql
    ";
    $stmtSummary = $pdo->prepare($sqlSummary);
    $stmtSummary->execute($params);
    $summary = $stmtSummary->fetch();

    $sumIncome  = (float)$summary['total_income'];
    $sumExpense = (float)$summary['total_expense'];
    $sumNet     = $sumIncome - $sumExpense;

} catch (PDOException $e) {
    error_log("Transactions Query Error: " . $e->getMessage());
    $flashError = "Terjadi kesalahan saat memuat data transaksi.";
    $transactions = [];
    $categoriesList = [];
    $sumIncome = $sumExpense = $sumNet = 0;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Alert Feedback -->
<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($flashSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($flashError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Header Actions -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h5 class="fw-bold mb-1">Daftar Transaksi Keuangan</h5>
        <p class="text-muted small mb-0">Catat dan pantau seluruh arus kas masuk dan keluar Anda.</p>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Tambah Transaksi Baru
    </a>
</div>

<!-- Summary Mini Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-success-subtle text-success">
                    <i class="bi bi-arrow-down-left-circle-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Pemasukan (Terfilter)</span>
                    <h6 class="fw-bold text-success mb-0"><?= format_rupiah($sumIncome) ?></h6>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-danger-subtle text-danger">
                    <i class="bi bi-arrow-up-right-circle-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Pengeluaran (Terfilter)</span>
                    <h6 class="fw-bold text-danger mb-0"><?= format_rupiah($sumExpense) ?></h6>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-primary-subtle text-primary">
                    <i class="bi bi-calculator-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Selisih Bersih</span>
                    <h6 class="fw-bold mb-0 <?= $sumNet >= 0 ? 'text-dark' : 'text-danger' ?>"><?= format_rupiah($sumNet) ?></h6>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Form Card -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label small fw-semibold">Jenis</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Semua Jenis</option>
                    <option value="income" <?= $filterType === 'income' ? 'selected' : '' ?>>Pemasukan</option>
                    <option value="expense" <?= $filterType === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-semibold">Kategori</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="0">Semua Kategori</option>
                    <?php foreach ($categoriesList as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>>
                            [<?= strtoupper($cat['type']) ?>] <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label small fw-semibold">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterStart) ?>">
            </div>

            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label small fw-semibold">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterEnd) ?>">
            </div>

            <div class="col-12 col-sm-8 col-md-2">
                <label class="form-label small fw-semibold">Cari Catatan</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Kata kunci..." value="<?= htmlspecialchars($filterSearch) ?>">
            </div>

            <div class="col-12 col-sm-4 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i></button>
                <a href="index.php" class="btn btn-sm btn-light border" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table Transaksi -->
<div class="card border-0 shadow-sm rounded-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Tanggal</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Nominal</th>
                    <th>Catatan</th>
                    <th class="pe-4 text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-white-50"></i>
                            Tidak ada transaksi ditemukan. Silakan sesuaikan filter atau tambah transaksi baru.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
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
                            <td class="text-muted small">
                                <?= !empty($t['note']) ? htmlspecialchars($t['note']) : '<span class="text-white-50">-</span>' ?>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?= $t['id'] ?>" class="btn btn-light border text-muted" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="delete.php?id=<?= $t['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                       class="btn btn-light border text-danger" 
                                       title="Hapus"
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
