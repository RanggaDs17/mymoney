/**
 * ===================================================
 * CUSTOM JAVASCRIPT (assets/js/script.js)
 * ===================================================
 * Script pendukung interaksi antarmuka pengguna (UI):
 * - Mobile sidebar toggle
 * - Format Rupiah otomatis di input nominal
 * - Konfirmasi hapus data
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Mobile Sidebar Toggle Logic
    const sidebar = document.getElementById('appSidebar');
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggleBtn && sidebar && sidebarOverlay) {
        sidebarToggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // 2. Format Rupiah Input Helper
    const rupiahInputs = document.querySelectorAll('.input-rupiah');
    rupiahInputs.forEach(function(input) {
        input.addEventListener('keyup', function(e) {
            input.value = formatRupiah(this.value);
        });
    });

    /**
     * Helper Function: Format string angka menjadi Rupiah
     * @param {string} angka 
     * @returns {string}
     */
    function formatRupiah(angka) {
        let number_string = angka.replace(/[^,\d]/g, '').toString(),
            split   = number_string.split(','),
            sisa    = split[0].length % 3,
            rupiah  = split[0].substr(0, sisa),
            ribuan  = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah;
    }
});

/**
 * Konfirmasi sebelum menghapus data penting
 * @param {Event} e 
 * @param {string} message 
 */
function confirmDelete(e, message = "Apakah Anda yakin ingin menghapus data ini?") {
    if (!confirm(message)) {
        e.preventDefault();
        return false;
    }
    return true;
}
