// Minimal JS for InventarisKu
document.addEventListener('DOMContentLoaded', function () {
    // Hentikan jika Bootstrap belum termuat (mis. CDN tidak tersedia).
    if (!window.bootstrap || !bootstrap.Alert) {
        return;
    }

    // Sembunyikan notifikasi setelah 6 detik agar tidak menutupi konten.
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(function () {
            bootstrap.Alert.getOrCreateInstance(alert).close();
        }, 6000);
    });
});
