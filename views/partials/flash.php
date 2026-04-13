<?php
use Core\Session;

$flashMessages = [];
foreach (['success', 'error', 'warning', 'info'] as $type) {
    $message = Session::getFlash($type);
    if ($message !== null && $message !== '') {
        $flashMessages[] = [
            'type' => $type,
            'message' => (string) $message,
        ];
    }
}
?>
<style>
    .toastify.library-toast {
        border-radius: 18px;
        border: 1px solid rgba(196, 201, 212, 0.82);
        background: rgba(255, 255, 255, 0.97);
        color: #191c1d;
        box-shadow: 0 18px 45px rgba(10, 37, 64, 0.14);
        padding: 14px 16px;
        font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        backdrop-filter: blur(16px);
    }

    .library-toast__wrap {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .library-toast__icon {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        flex: 0 0 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 800;
    }

    .library-toast__title {
        display: block;
        font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;
        font-size: 14px;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 2px;
    }

    .library-toast__message {
        display: block;
        font-size: 13px;
        line-height: 1.45;
        color: #565d6b;
    }

    .library-toast--success .library-toast__icon {
        background: #e8f5ee;
        color: #2d7a4f;
    }

    .library-toast--error .library-toast__icon {
        background: #fef2f2;
        color: #b91c1c;
    }

    .library-toast--warning .library-toast__icon {
        background: #fff7e6;
        color: #b8860b;
    }

    .library-toast--info .library-toast__icon {
        background: #eaf2ff;
        color: #1a3a5c;
    }
</style>
<script>
(() => {
    if (!window.__libraryToastifyRequested) {
        window.__libraryToastifyRequested = true;

        if (!document.querySelector('link[data-library-toastify]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css';
            link.dataset.libraryToastify = 'true';
            document.head.appendChild(link);
        }

        if (!document.querySelector('script[data-library-toastify]')) {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/toastify-js';
            script.dataset.libraryToastify = 'true';
            document.head.appendChild(script);
        }
    }

    if (!window.showLibraryToast) {
        window.showLibraryToast = (type, message) => {
            const titles = {
                success: 'Listo',
                error: 'No se pudo completar',
                warning: 'Atención',
                info: 'Información',
            };

            const icons = {
                success: '✓',
                error: '!',
                warning: '!',
                info: 'i',
            };

            const render = () => {
                if (typeof window.Toastify === 'undefined') {
                    window.setTimeout(render, 120);
                    return;
                }

                const node = document.createElement('div');
                node.className = `library-toast__wrap library-toast--${type}`;
                node.innerHTML = `
                    <span class="library-toast__icon">${icons[type] || 'i'}</span>
                    <span>
                        <span class="library-toast__title">${titles[type] || 'Notificación'}</span>
                        <span class="library-toast__message"></span>
                    </span>
                `;
                node.querySelector('.library-toast__message').textContent = message;

                window.Toastify({
                    node,
                    duration: 4200,
                    gravity: 'top',
                    position: 'right',
                    stopOnFocus: true,
                    className: 'library-toast',
                    close: true,
                    escapeMarkup: false,
                }).showToast();
            };

            render();
        };
    }
})();
</script>
<?php if ($flashMessages !== []): ?>
    <script>
    (() => {
        const items = <?= json_encode($flashMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        items.forEach((item, index) => {
            window.setTimeout(() => {
                window.showLibraryToast(item.type, item.message);
            }, index * 180);
        });
    })();
    </script>
    <noscript>
        <div class="space-y-3 mb-4">
            <?php foreach ($flashMessages as $flash): ?>
                <div class="rounded-2xl border border-outline-variant bg-surface-container-low px-4 py-3 text-sm text-on-surface" role="alert">
                    <strong class="mr-2"><?= htmlspecialchars(strtoupper($flash['type']), ENT_QUOTES, 'UTF-8') ?>:</strong>
                    <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endforeach; ?>
        </div>
    </noscript>
<?php endif; ?>
