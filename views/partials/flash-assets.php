<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .swal2-popup.swal2-toast {
        border-radius: 18px;
        border: 1px solid rgba(196, 201, 212, 0.8);
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(16px);
        box-shadow: 0 18px 45px rgba(10, 37, 64, 0.12);
    }

    .swal2-title.swal2-toast-title {
        font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;
        font-weight: 800;
        letter-spacing: -0.01em;
    }

    .swal2-html-container.swal2-toast-content {
        font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        color: #565d6b;
    }

    .swal2-timer-progress-bar {
        background: rgba(10, 37, 64, 0.18) !important;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(() => {
    const runFlashNotifications = () => {
        const items = Array.isArray(window.__libraryFlashMessages) ? [...window.__libraryFlashMessages] : [];
        if (!items.length || typeof window.Swal === 'undefined') return;

        window.__libraryFlashMessages = [];

        const titles = {
            success: 'Listo',
            error: 'No se pudo completar',
            warning: 'Atención',
            info: 'Información',
        };

        const icons = {
            success: 'success',
            error: 'error',
            warning: 'warning',
            info: 'info',
        };

        const toast = window.Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4200,
            timerProgressBar: true,
            customClass: {
                title: 'swal2-toast-title',
                htmlContainer: 'swal2-toast-content',
            },
            didOpen: (element) => {
                element.addEventListener('mouseenter', window.Swal.stopTimer);
                element.addEventListener('mouseleave', window.Swal.resumeTimer);
            },
        });

        items.forEach((item, index) => {
            window.setTimeout(() => {
                toast.fire({
                    icon: icons[item.type] || 'info',
                    title: titles[item.type] || 'Notificación',
                    text: item.message,
                });
            }, index * 180);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runFlashNotifications);
    } else {
        runFlashNotifications();
    }
})();
</script>
