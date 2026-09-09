<?php
/**
 * ===================================================
 * TAMBAH TRANSAKSI BARU (transactions/add.php)
 * ===================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$page_title   = "Tambah Transaksi";
$page_heading = "Tambah Transaksi";

$errors = [];
$type             = $_POST['type'] ?? 'expense';
$category_id      = (int)($_POST['category_id'] ?? 0);
$amount_raw       = $_POST['amount'] ?? '';
$transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');
$note             = trim($_POST['note'] ?? '');

// Ambil Kategori untuk user ini
try {
    $stmtCat = $pdo->prepare("
        SELECT id, name, type FROM categories 
        WHERE user_id IS NULL OR user_id = :user_id 
        ORDER BY name ASC
    ");
    $stmtCat->execute([':user_id' => $userId]);
    $categories = $stmtCat->fetchAll();
} catch (PDOException $e) {
    error_log("Add Trans Categories Error: " . $e->getMessage());
    $categories = [];
}

// Proses Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Token keamanan CSRF tidak valid.";
    } else {
        // Cleaning numeric amount (remove dots/commas if user formatted string)
        $cleanAmount = preg_replace('/[^\d.]/', '', str_replace('.', '', $amount_raw));
        $amount = (float)$cleanAmount;

        if (!in_array($type, ['income', 'expense'])) {
            $errors[] = "Jenis transaksi tidak valid.";
        }

        if ($category_id <= 0) {
            $errors[] = "Pilih kategori transaksi.";
        } else {
            // Validasi apakah category_id milik user atau bawaan sistem
            $validCat = array_filter($categories, fn($c) => $c['id'] == $category_id);
            if (empty($validCat)) {
                $errors[] = "Kategori terpilih tidak valid.";
            }
        }

        if ($amount <= 0) {
            $errors[] = "Nominal transaksi harus lebih besar dari Rp 0.";
        }

        if (empty($transaction_date)) {
            $errors[] = "Tanggal transaksi wajib diisi.";
        }

        if (empty($errors)) {
            try {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO transactions (user_id, category_id, type, amount, transaction_date, note) 
                    VALUES (:user_id, :category_id, :type, :amount, :transaction_date, :note)
                ");
                $stmtInsert->execute([
                    ':user_id'          => $userId,
                    ':category_id'      => $category_id,
                    ':type'             => $type,
                    ':amount'           => $amount,
                    ':transaction_date' => $transaction_date,
                    ':note'             => $note
                ]);

                $_SESSION['flash_success'] = "Transaksi berhasil dicatat!";
                header("Location: index.php");
                exit();
            } catch (PDOException $e) {
                error_log("Insert Transaction Error: " . $e->getMessage());
                $errors[] = "Terjadi kesalahan sistem saat menyimpan transaksi.";
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0">Catat Transaksi Baru</h5>
                    <a href="index.php" class="btn btn-sm btn-light border"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
                </div>
            </div>

            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Gagal Menyimpan:</strong>
                        <ul class="mb-0 ps-3 mt-1 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="add.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <!-- Radio Pilihan Tipe -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold d-block">Jenis Transaksi</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="type" id="type_expense" value="expense" <?= $type === 'expense' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-danger py-2.5 fw-semibold" for="type_expense">
                                <i class="bi bi-arrow-up-right-circle me-1"></i> Pengeluaran (Expense)
                            </label>

                            <input type="radio" class="btn-check" name="type" id="type_income" value="income" <?= $type === 'income' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-success py-2.5 fw-semibold" for="type_income">
                                <i class="bi bi-arrow-down-left-circle me-1"></i> Pemasukan (Income)
                            </label>
                        </div>
                    </div>

                    <!-- Nominal Input -->
                    <div class="mb-3">
                        <label for="amount" class="form-label small fw-semibold">Nominal (Rp)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold">Rp</span>
                            <input type="text" class="form-control fw-bold text-dark input-rupiah" id="amount" name="amount" placeholder="0" value="<?= htmlspecialchars($amount_raw) ?>" required>
                        </div>
                    </div>

                    <!-- Kategori Dropdown -->
                    <div class="mb-3">
                        <label for="category_id" class="form-label small fw-semibold">Kategori Transaksi</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" data-type="<?= $c['type'] ?>" <?= $category_id == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tanggal -->
                    <div class="mb-3">
                        <label for="transaction_date" class="form-label small fw-semibold">Tanggal Transaksi</label>
                        <input type="date" class="form-control" id="transaction_date" name="transaction_date" value="<?= htmlspecialchars($transaction_date) ?>" required>
                    </div>

                    <!-- Catatan / Keterangan -->
                    <div class="mb-4">
                        <label for="note" class="form-label small fw-semibold">Catatan / Keterangan (Opsional)</label>
                        <textarea class="form-control" id="note" name="note" rows="3" placeholder="Contoh: Makan siang di warung, Gaji bulan September..."><?= htmlspecialchars($note) ?></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioExpense = document.getElementById('type_expense');
    const radioIncome  = document.getElementById('type_income');
    const catSelect    = document.getElementById('category_id');

    function filterCategoryOptions() {
        const selectedType = radioIncome.checked ? 'income' : 'expense';
        let firstValidOption = null;

        Array.from(catSelect.options).forEach(opt => {
            if (opt.value === '') return;
            const optType = opt.dataset.type;
            if (optType === selectedType) {
                opt.style.display = '';
                if (!firstValidOption) firstValidOption = opt;
            } else {
                opt.style.display = 'none';
                if (opt.selected) opt.selected = false;
            }
        });
    }

    radioExpense.addEventListener('change', filterCategoryOptions);
    radioIncome.addEventListener('change', filterCategoryOptions);
    filterCategoryOptions();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
