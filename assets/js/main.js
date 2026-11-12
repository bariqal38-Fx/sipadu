document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm');

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.alert-auto').forEach(function (alert) {
        window.setTimeout(function () {
            var instance = bootstrap.Alert.getOrCreateInstance(alert);
            instance.close();
        }, 6000);
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
        new bootstrap.Tooltip(element);
    });

    var fileInput = document.querySelector('input[type="file"][data-file-label]');

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var target = document.querySelector(fileInput.getAttribute('data-file-label'));

            if (target) {
                target.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : 'Belum ada file dipilih';
            }
        });
    }
});
