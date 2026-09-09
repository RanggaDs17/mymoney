<?php
/**
 * ===================================================
 * TARGET TABUNGAN (savings/index.php)
 * ===================================================
 * Menampilkan daftar target tabungan (Savings Goals), 
 * progres persentase, sisa hari tenggat waktu, serta penambahan dana.
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$page_title   = "Target Tabungan";
$page_heading = "Target Tabungan";

// Flash Messages
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// CSRF Token Init
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

try {
    $stmt = $pdo->prepare("
        SELECT * FROM savings_goals 
        WHERE user_id = :user_id 
        ORDER BY deadline ASC, id DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $goals = $stmt->fetchAll();

    // Summary calculations
    $totalTarget = 0;
    $totalCurrent = 0;
    foreach ($goals as $g) {
        $totalTarget += (float)$g['target_amount'];
        $totalCurrent += (float)$g['current_amount'];
    }

} catch (PDOException $e) {
    error_log("Savings Goals Fetch Error: " . $e->getMessage());
    $flashError = "Terjadi kesalahan saat memuat data target tabungan.";
    $goals = [];
    $totalTarget = $totalCurrent = 0;
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
        <h5 class="fw-bold mb-1">Target Tabungan (Savings Goals)</h5>
        <p class="text-muted small mb-0">Rencanakan dan pantau pencapaian impian finansial Anda.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGoalModal">
        <i class="bi bi-plus-lg me-1"></i> Buat Target Baru
    </button>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-primary-subtle text-primary">
                    <i class="bi bi-piggy-bank-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Total Target Tabungan</span>
                    <h6 class="fw-bold text-dark mb-0"><?= format_rupiah($totalTarget) ?></h6>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-success-subtle text-success">
                    <i class="bi bi-safe-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Total Terkumpul</span>
                    <h6 class="fw-bold text-success mb-0"><?= format_rupiah($totalCurrent) ?></h6>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-stat p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-info-subtle text-info">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold">Rata-Rata Pencapaian</span>
                    <h6 class="fw-bold text-dark mb-0">
                        <?= $totalTarget > 0 ? number_format(min(100, ($totalCurrent / $totalTarget) * 100), 1) . '%' : '0%' ?>
                    </h6>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grid Cards Target Tabungan -->
<div class="row g-4 mb-4">
    <?php if (empty($goals)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-5 text-center text-muted">
                <i class="bi bi-piggy-bank fs-1 d-block mb-3 text-white-50"></i>
                <h6 class="fw-bold text-dark mb-1">Belum Ada Target Tabungan</h6>
                <p class="small mb-3">Mulai buat target impian Anda seperti Beli Laptop, Liburan, Dana Darurat, dll.</p>
                <div>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                        <i class="bi bi-plus-lg me-1"></i> Buat Target Pertama
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($goals as $g): ?>
            <?php 
                $target  = (float)$g['target_amount'];
                $current = (float)$g['current_amount'];
                $percent = $target > 0 ? round(($current / $target) * 100, 1) : 0;
                $percentClamped = min(100, $percent);

                // Date remaining
                $today = new DateTime();
                $deadlineDate = new DateTime($g['deadline']);
                $diff = $today->diff($deadlineDate);
                $isPast = $today > $deadlineDate;
                $daysLeft = $diff->days;

                // Color code
                $barColor = 'bg-primary';
                if ($percent >= 100) {
                    $barColor = 'bg-success';
                } elseif ($isPast) {
                    $barColor = 'bg-danger';
                }
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box <?= $percent >= 100 ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' ?>" style="width: 44px; height: 44px;">
                                    <i class="bi <?= $percent >= 100 ? 'bi-check-circle-fill' : 'bi-bullseye' ?> fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($g['name']) ?></h6>
                                    <span class="small text-muted">
                                        <i class="bi bi-calendar-event me-1"></i> 
                                        <?= date('d M Y', strtotime($g['deadline'])) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border-0" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical text-muted"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                    <li>
                                        <button class="dropdown-menu-item dropdown-item small edit-goal-btn" 
                                                data-id="<?= $g['id'] ?>"
                                                data-name="<?= htmlspecialchars($g['name']) ?>"
                                                data-target="<?= $g['target_amount'] ?>"
                                                data-current="<?= $g['current_amount'] ?>"
                                                data-deadline="<?= $g['deadline'] ?>"
                                                data-bs-toggle="modal" data-bs-target="#editGoalModal">
                                            <i class="bi bi-pencil-square me-2 text-primary"></i> Edit Target
                                        </button>
                                    </li>
                                    <li>
                                        <a href="delete.php?id=<?= $g['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                           class="dropdown-item small text-danger"
                                           onclick="return confirmDelete(event, 'Apakah Anda yakin ingin menghapus target tabungan ini?')">
                                            <i class="bi bi-trash me-2"></i> Hapus Target
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Progress Section -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between small mb-1">
                                <span class="text-muted fw-semibold">Progres Terkumpul</span>
                                <span class="fw-bold text-dark"><?= $percent ?>%</span>
                            </div>
                            <div class="progress rounded-pill" style="height: 10px;">
                                <div class="progress-bar <?= $barColor ?> rounded-pill" role="progressbar" style="width: <?= $percentClamped ?>%"></div>
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="small text-muted">Terkumpul:</span>
                                <span class="fw-bold text-success"><?= format_rupiah($current) ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Target Total:</span>
                                <span class="fw-semibold text-dark"><?= format_rupiah($target) ?></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex align-items-center justify-content-between border-top pt-3">
                            <span class="small text-muted">
                                <?php if ($percent >= 100): ?>
                                    <span class="badge bg-success-subtle text-success fw-bold"><i class="bi bi-check-all"></i> Target Tercapai!</span>
                                <?php elseif ($isPast): ?>
                                    <span class="badge bg-danger-subtle text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> Lewat Tenggat</span>
                                <?php else: ?>
                                    <i class="bi bi-hourglass-split me-1"></i> Sisa <strong><?= $daysLeft ?> hari</strong>
                                <?php endif; ?>
                            </span>

                            <button type="button" class="btn btn-sm btn-outline-success fw-semibold deposit-goal-btn"
                                    data-id="<?= $g['id'] ?>"
                                    data-name="<?= htmlspecialchars($g['name']) ?>"
                                    data-bs-toggle="modal" data-bs-target="#depositModal">
                                <i class="bi bi-plus-circle me-1"></i> Tabung
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Tambah Target Baru -->
<div class="modal fade" id="addGoalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Buat Target Tabungan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="add.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold">Nama Target</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: Beli Motor Baru, Liburan Bali" required>
                    </div>

                    <div class="mb-3">
                        <label for="target_amount" class="form-label small fw-semibold">Target Nominal (Rp)</label>
                        <input type="text" class="form-control input-rupiah" id="target_amount" name="target_amount" placeholder="10.000.000" required>
                    </div>

                    <div class="mb-3">
                        <label for="current_amount" class="form-label small fw-semibold">Saldo Awal / Tabungan Saat Ini (Rp)</label>
                        <input type="text" class="form-control input-rupiah" id="current_amount" name="current_amount" placeholder="0">
                    </div>

                    <div class="mb-3">
                        <label for="deadline" class="form-label small fw-semibold">Tenggat Waktu (Target Selesai)</label>
                        <input type="date" class="form-control" id="deadline" name="deadline" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Target</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tabung / Tambah Dana -->
<div class="modal fade" id="depositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Dana Tabungan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="deposit.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="goal_id" id="deposit_goal_id">
                <div class="modal-body py-4">
                    <p class="small text-muted mb-3">Menambah tabungan untuk: <strong id="deposit_goal_name" class="text-dark"></strong></p>
                    
                    <div class="mb-3">
                        <label for="deposit_amount" class="form-label small fw-semibold">Nominal Disetorkan (Rp)</label>
                        <input type="text" class="form-control form-control-lg input-rupiah fw-bold" id="deposit_amount" name="amount" placeholder="500.000" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4"><i class="bi bi-plus-circle me-1"></i> Tambah Dana</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Target -->
<div class="modal fade" id="editGoalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Target Tabungan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="edit.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id" id="edit_goal_id">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label small fw-semibold">Nama Target</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_target_amount" class="form-label small fw-semibold">Target Nominal (Rp)</label>
                        <input type="text" class="form-control input-rupiah" id="edit_target_amount" name="target_amount" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_current_amount" class="form-label small fw-semibold">Saldo Terkumpul (Rp)</label>
                        <input type="text" class="form-control input-rupiah" id="edit_current_amount" name="current_amount" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_deadline" class="form-label small fw-semibold">Tenggat Waktu</label>
                        <input type="date" class="form-control" id="edit_deadline" name="deadline" required>
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
    // Fill Deposit Modal
    document.querySelectorAll('.deposit-goal-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('deposit_goal_id').value = this.dataset.id;
            document.getElementById('deposit_goal_name').textContent = this.dataset.name;
        });
    });

    // Fill Edit Modal
    document.querySelectorAll('.edit-goal-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_goal_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_target_amount').value = Math.round(this.dataset.target);
            document.getElementById('edit_current_amount').value = Math.round(this.dataset.current);
            document.getElementById('edit_deadline').value = this.dataset.deadline;
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
