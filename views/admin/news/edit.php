<?php
// views/admin/news/edit.php
declare(strict_types=1);

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$isModal = isset($_GET['modal']) && $_GET['modal'] === '1';
$isCreate = !empty($is_create);
$current = is_array($news ?? null) ? $news : [];

$publishedAtValue = (string) ($current['published_at'] ?? '');
$publishedPickerValue = '';
if ($publishedAtValue !== '') {
    $normalized = str_replace('T', ' ', $publishedAtValue);
    $publishedPickerValue = substr($normalized, 0, 16);
}
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
@import url('https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css');
@import url('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');

.news-shell { max-width: 1120px; }
.news-topbar { background: linear-gradient(145deg, #ffffff 0%, #f4f7fb 100%); }

.news-card {
    border: 1px solid rgba(196, 201, 212, 0.7);
    border-radius: 1rem;
    background: #fff;
}

.news-input {
    width: 100%;
    border: 1px solid rgba(196, 201, 212, 0.9);
    border-radius: 0.75rem;
    background: #fff;
    color: rgb(25, 28, 29);
    font-size: 0.875rem;
    padding: 0.65rem 0.85rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.news-input:focus {
    outline: none;
    border-color: rgb(10, 37, 64);
    box-shadow: 0 0 0 3px rgba(10, 37, 64, 0.08);
}

.news-label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
    color: rgb(133, 144, 162);
    margin-bottom: 0.35rem;
}

.news-editor .ql-toolbar.ql-snow {
    border: 1px solid rgba(196, 201, 212, 1);
    border-radius: 0.75rem 0.75rem 0 0;
    background: #fff;
}

.news-editor .ql-container.ql-snow {
    border: 1px solid rgba(196, 201, 212, 1);
    border-top: 0;
    border-radius: 0 0 0.75rem 0.75rem;
    min-height: 190px;
    font-size: 0.875rem;
    color: rgb(25, 28, 29);
    background: #fff;
}

.news-editor .ql-editor {
    min-height: 190px;
    line-height: 1.7;
}

.news-cover-preview {
    aspect-ratio: 16 / 9;
    border: 1px dashed rgba(196, 201, 212, 0.9);
    border-radius: 0.9rem;
    background: #f8fafc;
    overflow: hidden;
}

.news-cover-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

@media (max-width: 1023px) {
    .news-shell { max-width: 100%; }
}
</style>

<section class="<?= $isModal ? 'p-4' : 'p-6 lg:p-8' ?>">
    <div class="news-shell mx-auto">
        <?php if (!$isModal): ?>
            <div class="news-topbar mb-6 rounded-2xl border border-outline-variant/60 p-5 shadow-ambient">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="label-sm">Contenido editorial</p>
                        <h1 class="headline-lg text-on-surface"><?= $isCreate ? 'Nueva noticia' : 'Editar noticia' ?></h1>
                        <p class="body-md mt-1">Disena una publicacion clara, con buena jerarquia y programacion de salida.</p>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/news" class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                        <i class="bi bi-arrow-left text-sm"></i> Volver a noticias
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" action="<?= $isCreate ? BASE_URL . '/admin/news' : BASE_URL . '/admin/news/' . (int) ($news_id ?? 0) ?>" id="news-edit-form" class="space-y-5">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrf ?? '') ?>">
            <input type="hidden" name="existing_cover_image" value="<?= $e((string) ($current['cover_image'] ?? '')) ?>">
            <?php if ($isModal): ?>
                <input type="hidden" name="modal" value="1">
            <?php endif; ?>

            <div class="grid gap-5 lg:grid-cols-[1.6fr_1fr]">
                <div class="news-card p-4 sm:p-5">
                    <div class="grid gap-4">
                        <div>
                            <label for="title" class="news-label">Titulo</label>
                            <input id="title" name="title" type="text" required value="<?= $e($current['title'] ?? '') ?>" class="news-input">
                        </div>

                        <div>
                            <label for="slug" class="news-label">Slug (opcional)</label>
                            <input id="slug" name="slug" type="text" value="<?= $e($current['slug'] ?? '') ?>" class="news-input" placeholder="se-autogenera-si-lo-dejas-vacio">
                        </div>

                        <div>
                            <label for="excerpt" class="news-label">Extracto (max 500)</label>
                            <textarea id="excerpt" name="excerpt" rows="1" maxlength="500" class="news-input py-1.5 text-xs leading-tight"><?= $e($current['excerpt'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label for="content" class="news-label">Contenido</label>
                            <div id="content-editor" class="news-editor mt-1"></div>
                            <textarea id="content" name="content" rows="10" required class="hidden"><?= $e($current['content'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <aside class="news-card p-4 sm:p-5 lg:sticky lg:top-24 lg:self-start">
                    <h2 class="title-sm text-on-surface">Publicacion</h2>
                    <p class="body-md mt-1 text-xs">Configura estado, portada y fecha de salida.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="is_published" class="news-label">Estado editorial</label>
                            <select id="is_published" name="is_published" class="news-input">
                                <?php $published = (string) ($current['is_published'] ?? '0'); ?>
                                <option value="0" <?= $published === '0' ? 'selected' : '' ?>>Borrador</option>
                                <option value="1" <?= $published === '1' ? 'selected' : '' ?>>Publicada/Programada</option>
                            </select>
                        </div>

                        <div>
                            <label class="news-label">Fecha/hora de publicacion</label>
                            <input id="published_at" name="published_at" type="hidden" value="<?= $e($publishedAtValue) ?>">
                            <input id="published_at_picker" type="text" value="<?= $e($publishedPickerValue) ?>" class="news-input" placeholder="YYYY-MM-DD HH:MM" aria-label="Fecha y hora de publicacion">
                        </div>

                        <div>
                            <label for="cover_image" class="news-label">Imagen de portada</label>
                            <input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="news-input">
                            <p class="mt-1 text-xs text-on-surface-subtle">Formatos permitidos: JPG, PNG, WEBP, GIF. Maximo 5MB.</p>
                        </div>

                        <div id="cover-preview" class="news-cover-preview">
                            <?php if (!empty($current['cover_image'])): ?>
                                <img src="<?= $e($current['cover_image']) ?>" alt="Portada de noticia" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="h-full w-full flex items-center justify-center text-on-surface-subtle text-xs px-4 text-center">
                                    La portada se previsualizara aqui
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </aside>
            </div>

            <?php if (!$isModal): ?>
                <div class="news-card px-4 py-3 shadow-ambient">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <span class="inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3 py-1.5 text-sm text-on-surface-muted">
                            <i class="bi bi-stars text-sm"></i> Escribe claro, usa subtitulos y prioriza informacion verificable.
                        </span>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                                <i class="bi bi-floppy text-sm"></i> <?= $isCreate ? 'Crear noticia' : 'Guardar cambios' ?>
                            </button>
                            <a href="<?= BASE_URL ?>/admin/news" class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
(() => {
    const form = document.getElementById('news-edit-form');
    if (!form) return;

    const contentField = document.getElementById('content');
    const editorTarget = document.getElementById('content-editor');
    const coverInput = document.getElementById('cover_image');
    const coverPreview = document.getElementById('cover-preview');
    const publishedAtInput = document.getElementById('published_at');
    const publishedAtPicker = document.getElementById('published_at_picker');

    let quill = null;
    if (contentField && editorTarget && typeof window.Quill !== 'undefined') {
        quill = new window.Quill(editorTarget, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ align: [] }],
                    ['link', 'image', 'blockquote', 'code-block'],
                    ['clean']
                ]
            }
        });

        const initialHtml = contentField.value || '';
        if (initialHtml.trim() !== '') {
            quill.clipboard.dangerouslyPasteHTML(initialHtml);
        }

        quill.on('text-change', () => {
            if (contentField) {
                contentField.value = quill.root.innerHTML;
            }
        });
    }

    const syncEditorContent = () => {
        if (!contentField) return;
        if (quill) {
            contentField.value = quill.root.innerHTML;
        }
    };

    const syncPublishedAt = () => {
        if (!publishedAtInput || !publishedAtPicker) return;

        const pickerVal = (publishedAtPicker.value || '').trim();
        if (!pickerVal) {
            publishedAtInput.value = '';
            return;
        }

        publishedAtInput.value = pickerVal;
    };

    const updateCoverPreview = () => {
        if (!coverInput || !coverPreview) return;
        const file = coverInput.files && coverInput.files[0] ? coverInput.files[0] : null;
        if (!file || (file.type && !file.type.startsWith('image/'))) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            const src = typeof reader.result === 'string' ? reader.result : '';
            if (src !== '') {
                coverPreview.innerHTML = '<img src="' + src + '" alt="Portada de noticia" loading="lazy" decoding="async">';
            }
        };
        reader.readAsDataURL(file);
    };

    if (coverInput) {
        coverInput.addEventListener('change', updateCoverPreview);
    }

    // Keep submit path robust even if optional widgets fail.
    form.addEventListener('submit', () => {
        syncEditorContent();
        syncPublishedAt();
    });

    window.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'submit-news-edit-form') {
            syncEditorContent();
            syncPublishedAt();
            form.requestSubmit();
        }
    });

    if (publishedAtPicker) {
        try {
            if (typeof window.flatpickr !== 'undefined') {
                const localeEs = window.flatpickr?.l10ns?.es ?? 'default';
                window.flatpickr(publishedAtPicker, {
                    enableTime: true,
                    time_24hr: true,
                    dateFormat: 'Y-m-d H:i',
                    minuteIncrement: 15,
                    allowInput: true,
                    locale: localeEs,
                    defaultDate: publishedAtPicker.value || null,
                    onChange: syncPublishedAt,
                });
            }
        } catch (error) {
            // Ignore picker init errors to avoid blocking form submission.
        }

        publishedAtPicker.addEventListener('change', syncPublishedAt);
        publishedAtPicker.addEventListener('input', syncPublishedAt);
    }

    syncPublishedAt();
})();
</script>
