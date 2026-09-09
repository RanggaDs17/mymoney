<?php
/**
 * ===================================================
 * PROFIL PENGGUNA (profile/index.php)
 * ===================================================
 * Menampilkan dan memperbarui data profil pengguna (Nama, Email),
 * fitur ganti password, serta fitur hapus akun permanen.
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$page_title   = "Profil Pengguna";
$page_heading = "Profil Pengguna";

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user data
try {
    $stmtUser = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = :id LIMIT 1");
    $stmtUser->execute([':id' => $userId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        die("Pengguna tidak ditemukan.");
    }

    // Account stats
    $stmtTransCount = $pdo->prepare("SELECT COUNT(*) AS total FROM transactions WHERE user_id = :id");
    $stmtTransCount->execute([':id' => $userId]);
    $totalTrans = $stmtTransCount->fetch()['total'];

} catch (PDOException $e) {
    error_log("Profile Fetch Error: " . $e->getMessage());
    die("Terjadi kesalahan saat memuat data profil.");
}

$profileErrors  = [];
$passwordErrors = [];

// Process Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_error'] = "Token keamanan CSRF tidak valid.";
        header("Location: index.php");
        exit();
    }

    // 1. Update Profile Info
    if ($action === 'update_profile') {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name)) {
            $profileErrors[] = "Nama lengkap wajib diisi.";
        }

        if (empty($email)) {
            $profileErrors[] = "Alamat email wajib diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileErrors[] = "Format alamat email tidak valid.";
        } else {
            // Check if email taken by another user
            $stmtEmailCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
            $stmtEmailCheck->execute([':email' => $email, ':id' => $userId]);
            if ($stmtEmailCheck->fetch()) {
                $profileErrors[] = "Email ini sudah digunakan oleh pengguna lain.";
            }
        }

        if (empty($profileErrors)) {
            try {
                $stmtUp = $pdo->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
                $stmtUp->execute([':name' => $name, ':email' => $email, ':id' => $userId]);

                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['flash_success'] = "Profil Anda berhasil diperbarui!";
                header("Location: index.php");
                exit();
            } catch (PDOException $e) {
                error_log("Update Profile Error: " . $e->getMessage());
                $profileErrors[] = "Gagal memperbarui profil pengguna.";
            }
        }
    }

    // 2. Change Password
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Verify current password
        $stmtPass = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
        $stmtPass->execute([':id' => $userId]);
        $userPass = $stmtPass->fetch();

        if (!$userPass || !password_verify($current_password, $userPass['password'])) {
            $passwordErrors[] = "Password saat ini salah.";
        }

        if (empty($new_password)) {
            $passwordErrors[] = "Password baru wajib diisi.";
        } elseif (strlen($new_password) < 8) {
            $passwordErrors[] = "Password baru minimal 8 karakter.";
        }

        if ($new_password !== $confirm_password) {
            $passwordErrors[] = "Konfirmasi password baru tidak cocok.";
        }

        if (empty($passwordErrors)) {
            try {
                $hashedNew = password_hash($new_password, PASSWORD_BCRYPT);
                $stmtPassUp = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmtPassUp->execute([':password' => $hashedNew, ':id' => $userId]);

                $_SESSION['flash_success'] = "Password Anda berhasil diubah!";
                header("Location: index.php");
                exit();
            } catch (PDOException $e) {
                error_log("Change Password Error: " . $e->getMessage());
                $passwordErrors[] = "Gagal memperbarui password.";
            }
        }
    }
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

<div class="row g-4">
    <!-- User Info Card & Zona Bahaya -->
    <div class="col-12 col-lg-4">
        <!-- Card Detail Pengguna -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 text-center mb-4">
            <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['name']) ?></h5>
            <p class="text-muted small mb-3"><?= htmlspecialchars($user['email']) ?></p>

            <div class="bg-light p-3 rounded-3 text-start mb-0">
                <div class="d-flex align-items-center justify-content-between mb-2 small">
                    <span class="text-muted"><i class="bi bi-calendar3 me-1"></i> Terdaftar Sejak</span>
                    <span class="fw-semibold text-dark"><?= date('d M Y', strtotime($user['created_at'])) ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between small">
                    <span class="text-muted"><i class="bi bi-receipt me-1"></i> Total Transaksi</span>
                    <span class="badge bg-primary px-2 py-1"><?= number_format($totalTrans) ?> Transaksi</span>
                </div>
            </div>
        </div>

        <!-- Card Zona Bahaya (Hapus Akun) -->
        <div class="card border-danger-subtle shadow-sm rounded-4 bg-white p-4">
            <h6 class="fw-bold text-danger mb-2">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>Zona Bahaya
            </h6>
            <p class="small text-muted mb-3">
                Menghapus akun akan menghapus seluruh data transaksi, kategori, dan target tabungan Anda secara permanen.
            </p>
            <button type="button" class="btn btn-outline-danger w-100 btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                <i class="bi bi-trash me-1"></i> Hapus Akun Permanen
            </button>
        </div>
    </div>

    <!-- Edit Forms -->
    <div class="col-12 col-lg-8">
        <!-- Form Edit Data Profil -->
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-gear me-2 text-primary"></i>Informasi Akun</h6>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($profileErrors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($profileErrors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label small fw-semibold">Alamat Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-circle me-1"></i> Simpan Profil
                    </button>
                </form>
            </div>
        </div>

        <!-- Form Ubah Password -->
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-shield-lock me-2 text-danger"></i>Ubah Password</h6>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($passwordErrors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($passwordErrors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label for="current_password" class="form-label small fw-semibold">Password Saat Ini</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label small fw-semibold">Password Baru (Minimal 8 Karakter)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label small fw-semibold">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-key me-1"></i> Perbarui Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Akun -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Hapus Akun Permanen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="delete_account.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="modal-body py-4">
                    <div class="alert alert-danger mb-3 small" role="alert">
                        <strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan. Seluruh data transaksi, kategori custom, dan target tabungan Anda akan dihapus secara permanen dari database.
                    </div>
                    <div class="mb-3">
                        <label for="delete_password" class="form-label small fw-semibold">Masukkan Password Anda Untuk Konfirmasi</label>
                        <input type="password" class="form-control" id="delete_password" name="password" placeholder="Masukkan password Anda..." required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-semibold"><i class="bi bi-trash me-1"></i> Ya, Hapus Akun Saya</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
