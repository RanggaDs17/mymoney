<?php
/**
 * ===================================================
 * KATEGORI TRANSAKSI (categories/index.php)
 * ===================================================
 * Menampilkan daftar kategori (Bawaan Sistem & Kategori Custom).
 * Pengguna dapat menambah, mengedit, dan menghapus kategori custom milik sendiri.
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$page_title   = "Kategori Transaksi";
$page_heading = "Kategori Transaksi";

// Flash Messages
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Inisialisasi CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // Ambil Kategori (Default Sistem + Custom milik User)
    $stmt = $pdo->prepare("
        SELECT * FROM categories 
        WHERE user_id IS NULL OR user_id = :user_id 
        ORDER BY is_default DESC, type ASC, name ASC
    ");
    $stmt->execute([':user_id' => $userId]);
    $categories = $stmt->fetchAll();

    $expenseCategories = array_filter($categories, fn($c) => $c['type'] === 'expense');
    $incomeCategories  = array_filter($categories, fn($c) => $c['type'] === 'income');

} catch (PDOException $e) {
    error_log("Categories Fetch Error: " . $e->getMessage());
    $flashError = "Gagal mengambil data kategori.";
    $categories = [];
    $expenseCategories = [];
    $incomeCategories = [];
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

<!-- Action Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h5 class="fw-bold mb-1">Daftar Kategori Transaksi</h5>
        <p class="text-muted small mb-0">Kelola kategori untuk mengelompokkan pemasukan dan pengeluaran Anda.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kategori Custom
    </button>
</div>

<!-- Category Lists -->
<div class="row g-4">
    <!-- List Kategori Pengeluaran -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-danger mb-0">
                    <i class="bi bi-arrow-up-right-circle-fill me-2"></i>Kategori Pengeluaran (Expense)
                </h6>
                <span class="badge bg-danger-subtle text-danger fw-semibold px-2.5 py-1 rounded-pill">
                    <?= count($expenseCategories) ?> Kategori
                </span>
            </div>
            <div class="card-body px-4">
                <div class="list-group list-group-flush border-top">
                    <?php foreach ($expenseCategories as $cat): ?>
                        <div class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box bg-danger-subtle text-danger" style="width: 38px; height: 38px; font-size: 1.1rem;">
                                    <i class="bi bi-tag-fill"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($cat['name']) ?></h6>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button class="btn btn-sm btn-light border text-muted edit-cat-btn" 
                                        data-id="<?= $cat['id'] ?>" 
                                        data-name="<?= htmlspecialchars($cat['name']) ?>" 
                                        data-type="<?= $cat['type'] ?>"
                                        data-bs-toggle="modal" data-bs-target="#editCategoryModal">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="delete.php?id=<?= $cat['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                   class="btn btn-sm btn-light border text-danger" 
                                   onclick="return confirmDelete(event, 'Hapus kategori ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- List Kategori Pemasukan -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-success mb-0">
                    <i class="bi bi-arrow-down-left-circle-fill me-2"></i>Kategori Pemasukan (Income)
                </h6>
                <span class="badge bg-success-subtle text-success fw-semibold px-2.5 py-1 rounded-pill">
                    <?= count($incomeCategories) ?> Kategori
                </span>
            </div>
            <div class="card-body px-4">
                <div class="list-group list-group-flush border-top">
                    <?php foreach ($incomeCategories as $cat): ?>
                        <div class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box bg-success-subtle text-success" style="width: 38px; height: 38px; font-size: 1.1rem;">
                                    <i class="bi bi-tag-fill"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($cat['name']) ?></h6>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button class="btn btn-sm btn-light border text-muted edit-cat-btn" 
                                        data-id="<?= $cat['id'] ?>" 
                                        data-name="<?= htmlspecialchars($cat['name']) ?>" 
                                        data-type="<?= $cat['type'] ?>"
                                        data-bs-toggle="modal" data-bs-target="#editCategoryModal">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="delete.php?id=<?= $cat['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                   class="btn btn-sm btn-light border text-danger" 
                                   onclick="return confirmDelete(event, 'Hapus kategori ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="add.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold">Nama Kategori</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: Investments, Side Income, Hobi" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label small fw-semibold">Jenis Transaksi</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="expense">Pengeluaran (Expense)</option>
                            <option value="income">Pemasukan (Income)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="edit.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label small fw-semibold">Nama Kategori</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_type" class="form-label small fw-semibold">Jenis Transaksi</label>
                        <select class="form-select" id="edit_type" name="type" required>
                            <option value="expense">Pengeluaran (Expense)</option>
                            <option value="income">Pemasukan (Income)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-cat-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_type').value = this.dataset.type;
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
