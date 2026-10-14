/**
 * Sistem Manager Hosting - Main JS
 */

document.addEventListener('DOMContentLoaded', function () {
    // Alert close button handler
    const alertCloses = document.querySelectorAll('.alert-close');
    alertCloses.forEach(btn => {
        btn.addEventListener('click', function () {
            const alert = this.closest('.alert');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-6px)';
                alert.style.transition = 'all 200ms ease';
                setTimeout(() => alert.remove(), 200);
            }
        });
    });

    // Auto format Rupiah display on input fields with .rupiah-input
    const rupiahInputs = document.querySelectorAll('.rupiah-input');
    rupiahInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            let val = this.value.replace(/[^0-9]/g, '');
            if (val) {
                this.value = parseInt(val, 10).toLocaleString('id-ID');
            } else {
                this.value = '';
            }
        });
    });

    // Image preview handler for file inputs with .image-preview-input
    const imageInputs = document.querySelectorAll('.image-preview-input');
    imageInputs.forEach(input => {
        const previewTargetId = input.dataset.previewTarget;
        const previewTarget = previewTargetId ? document.getElementById(previewTargetId) : null;

        if (previewTarget) {
            input.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Ukuran file melebihi batas 2 MB.');
                        this.value = '';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewTarget.src = e.target.result;
                        previewTarget.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
});

/**
 * Global Confirm Action Helper
 */
function confirmAction(message) {
    return confirm(message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?');
}
