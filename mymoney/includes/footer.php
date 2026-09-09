<?php
/**
 * ===================================================
 * FOOTER TEMPLATE (includes/footer.php)
 * ===================================================
 * Menutup pembungkus halaman, menampilkan copyright,
 * memuat JS Bootstrap 5, Chart.js CDN, dan custom script.js.
 */

// Hitung path dasar aplikasi
$script_name = $_SERVER['SCRIPT_NAME'];
$app_base = preg_replace('#/(dashboard|transactions|categories|savings|reports|profile|includes|auth)/.*$#i', '', $script_name);
$app_base = rtrim($app_base, '/');
?>
        </div> <!-- End .max-width-container -->
    </div> <!-- End .app-content -->

    <!-- Footer Copyright Section -->
    <footer class="bg-white border-top py-3 text-muted small mt-auto">
        <div class="max-width-container text-center">
            <span>&copy; <?= date('Y') ?> <strong>MyMoney</strong>. Aplikasi Pencatat Keuangan Pribadi. All Rights Reserved.</span>
        </div>
    </footer>
</main> <!-- End .app-main -->
</div> <!-- End .app-wrapper -->

<!-- Bootstrap 5 JS Bundle CDN (Includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js CDN (Digunakan untuk grafik visualisasi keuangan) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom Script JS Aplikasi -->
<script src="<?= $app_base ?>/assets/js/script.js"></script>
</body>
</html>
