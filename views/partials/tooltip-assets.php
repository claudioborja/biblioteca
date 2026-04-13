<?php
// views/partials/tooltip-assets.php
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tippy.js@6/dist/tippy.css">
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tippy.js@6/dist/tippy-bundle.umd.min.js"></script>
<style>
    .tippy-box[data-theme~='biblioteca'] {
        background: #0a2540;
        color: #f0f4fa;
        border: 1px solid rgba(196, 201, 212, 0.28);
        border-radius: 10px;
        box-shadow: 0 16px 40px rgba(10, 37, 64, 0.24);
        font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        font-size: 0.75rem;
        line-height: 1.35;
        letter-spacing: 0.01em;
    }

    .tippy-box[data-theme~='biblioteca'] .tippy-content {
        padding: 7px 9px;
    }

    .tippy-box[data-theme~='biblioteca'][data-placement^='top'] > .tippy-arrow::before {
        border-top-color: #0a2540;
    }

    .tippy-box[data-theme~='biblioteca'][data-placement^='bottom'] > .tippy-arrow::before {
        border-bottom-color: #0a2540;
    }

    .tippy-box[data-theme~='biblioteca'][data-placement^='left'] > .tippy-arrow::before {
        border-left-color: #0a2540;
    }

    .tippy-box[data-theme~='biblioteca'][data-placement^='right'] > .tippy-arrow::before {
        border-right-color: #0a2540;
    }
</style>
<script>
(() => {
    const SELECTOR = '[data-tooltip], [title]';
    const EXCLUDED = 'html,body,head,title,meta,link,script,style,iframe,[data-no-tooltip]';

    const resolveTargets = (root) => {
        const result = [];
        if (!(root instanceof Element || root instanceof Document)) return result;

        if (root instanceof Element && root.matches(SELECTOR) && !root.matches(EXCLUDED)) {
            result.push(root);
        }

        root.querySelectorAll?.(SELECTOR).forEach((el) => {
            if (!el.matches(EXCLUDED)) result.push(el);
        });

        return result;
    };

    const initTooltips = (root = document) => {
        if (typeof window.tippy !== 'function') return;

        resolveTargets(root).forEach((el) => {
            if (el.dataset.tippyInitialized === '1') return;

            const raw = (el.getAttribute('data-tooltip') ?? el.getAttribute('title') ?? '').trim();
            if (!raw) return;

            if (!el.hasAttribute('data-tooltip') && el.hasAttribute('title')) {
                el.setAttribute('data-tooltip', raw);
            }

            // Avoid duplicate native browser tooltip.
            if (el.hasAttribute('title')) {
                el.removeAttribute('title');
            }

            window.tippy(el, {
                content: raw,
                theme: 'biblioteca',
                placement: el.getAttribute('data-tooltip-placement') || 'top',
                animation: 'shift-away',
                arrow: true,
                delay: [120, 60],
                duration: [160, 130],
                offset: [0, 10],
                maxWidth: 320,
                allowHTML: false,
                interactive: false,
            });

            el.dataset.tippyInitialized = '1';
        });
    };

    const boot = () => {
        initTooltips(document);

        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (node instanceof Element) {
                        initTooltips(node);
                    }
                }
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }

    window.setupAppTooltips = initTooltips;
})();
</script>
