<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Services\EmailTemplateService;

/**
 * Unit tests for EmailTemplateService.
 *
 * Covers:
 *  - renderSystem(): HTML structure, encoding of user input, preheader, footer note
 *  - renderSystemText(): plain-text fallback structure
 */
final class EmailTemplateServiceTest extends TestCase
{
    private EmailTemplateService $service;

    protected function setUp(): void
    {
        $this->service = new EmailTemplateService();
    }

    // ── renderSystem() — structure ─────────────────────────────────────────

    #[Test]
    public function render_system_returns_valid_html_document(): void
    {
        $html = $this->service->renderSystem(
            preheader:   'Esto es el preheader',
            title:       'Bienvenido a la biblioteca',
            intro:       'Hola, te damos la bienvenida.',
            contentHtml: '<p>Tu cuenta ha sido creada.</p>'
        );

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</html>', $html);
        $this->assertStringContainsString('<head>', $html);
        $this->assertStringContainsString('<body', $html);
    }

    #[Test]
    public function render_system_injects_title_in_head_and_body(): void
    {
        $html = $this->service->renderSystem(
            preheader:   'Pre',
            title:       'Préstamo vencido',
            intro:       'Tu préstamo ha vencido.',
            contentHtml: '<p>Detalles del préstamo.</p>'
        );

        // Title appears in <title> tag
        $this->assertStringContainsString('<title>Préstamo vencido</title>', $html);
        // Title appears in the visible header cell
        $this->assertStringContainsString('Préstamo vencido', $html);
    }

    #[Test]
    public function render_system_includes_preheader_as_hidden_div(): void
    {
        $html = $this->service->renderSystem(
            preheader:   'Tienes un préstamo vencido hoy',
            title:       'Aviso',
            intro:       'Intro.',
            contentHtml: '<p>Cuerpo.</p>'
        );

        $this->assertStringContainsString('Tienes un préstamo vencido hoy', $html);
        // Preheader must live in a hidden div (max-height:0)
        $this->assertStringContainsString('max-height:0', $html);
    }

    #[Test]
    public function render_system_passes_content_html_through_unescaped(): void
    {
        // contentHtml is trusted HTML (caller responsibility) — must arrive intact
        $html = $this->service->renderSystem(
            preheader:   'Pre',
            title:       'T',
            intro:       'I',
            contentHtml: '<strong>Estado:</strong> activo'
        );

        $this->assertStringContainsString('<strong>Estado:</strong> activo', $html);
    }

    #[Test]
    public function render_system_escapes_title_special_chars(): void
    {
        $html = $this->service->renderSystem(
            preheader:   'Pre',
            title:       '<script>alert(1)</script>',
            intro:       'Intro',
            contentHtml: '<p>ok</p>'
        );

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    #[Test]
    public function render_system_escapes_intro_special_chars(): void
    {
        $html = $this->service->renderSystem(
            preheader:   'Pre',
            title:       'Ok',
            intro:       'Hola <b>usuario</b> & familia',
            contentHtml: '<p>ok</p>'
        );

        // The <b> tag inside intro is escaped (intro is not trusted HTML)
        $this->assertStringContainsString('&lt;b&gt;usuario&lt;/b&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
    }

    #[Test]
    public function render_system_escapes_preheader_special_chars(): void
    {
        $html = $this->service->renderSystem(
            preheader:   "<script>evil()</script>",
            title:       'T',
            intro:       'I',
            contentHtml: '<p>C</p>'
        );

        $this->assertStringNotContainsString('<script>evil()</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    #[Test]
    public function render_system_omits_footer_note_when_null(): void
    {
        $html = $this->service->renderSystem(
            preheader:   'Pre',
            title:       'T',
            intro:       'I',
            contentHtml: '<p>C</p>',
            footerNote:  null
        );

        // The footer block should exist but without any extra <div>
        $this->assertStringContainsString('Mensajeria automatica', $html);
        // No extra footer note div injected
        $this->assertEquals(0, substr_count($html, 'footer-note-placeholder'));
    }

    #[Test]
    public function render_system_includes_footer_note_when_provided(): void
    {
        $html = $this->service->renderSystem(
            preheader:   'Pre',
            title:       'T',
            intro:       'I',
            contentHtml: '<p>C</p>',
            footerNote:  'Para soporte llama al 123-456'
        );

        $this->assertStringContainsString('Para soporte llama al 123-456', $html);
    }

    #[Test]
    public function render_system_escapes_footer_note_special_chars(): void
    {
        $html = $this->service->renderSystem(
            preheader:   'Pre',
            title:       'T',
            intro:       'I',
            contentHtml: '<p>C</p>',
            footerNote:  'Contacto: <a href="evil">click</a>'
        );

        $this->assertStringNotContainsString('<a href="evil">', $html);
        $this->assertStringContainsString('&lt;a href=', $html);
    }

    #[Test]
    public function render_system_includes_css_mobile_media_query(): void
    {
        $html = $this->service->renderSystem('P', 'T', 'I', '<p>C</p>');

        $this->assertStringContainsString('@media screen', $html);
        $this->assertStringContainsString('max-width:', $html);
    }

    #[Test]
    public function render_system_contains_current_year_in_footer(): void
    {
        $html  = $this->service->renderSystem('P', 'T', 'I', '<p>C</p>');
        $year  = (string) date('Y');

        $this->assertStringContainsString($year, $html);
    }

    // ── renderSystemText() — plain text fallback ───────────────────────────

    #[Test]
    public function render_system_text_starts_with_title(): void
    {
        $text = $this->service->renderSystemText(
            title:       'Aviso de multa',
            intro:       'Tu multa ha sido registrada.',
            contentText: 'Monto: $5.00'
        );

        $this->assertStringStartsWith('Aviso de multa', $text);
    }

    #[Test]
    public function render_system_text_includes_title_underline(): void
    {
        $text = $this->service->renderSystemText(
            title:       'Préstamo',
            intro:       'I',
            contentText: 'C'
        );

        $this->assertStringContainsString(str_repeat('=', strlen('Préstamo')), $text);
    }

    #[Test]
    public function render_system_text_contains_intro_and_content(): void
    {
        $text = $this->service->renderSystemText(
            title:       'T',
            intro:       'Esto es la introducción.',
            contentText: 'Detalle del contenido.'
        );

        $this->assertStringContainsString('Esto es la introducción.', $text);
        $this->assertStringContainsString('Detalle del contenido.', $text);
    }

    #[Test]
    public function render_system_text_includes_footer_note_when_provided(): void
    {
        $text = $this->service->renderSystemText(
            title:       'T',
            intro:       'I',
            contentText: 'C',
            footerNote:  'Nota adicional para el pie'
        );

        $this->assertStringContainsString('Nota adicional para el pie', $text);
    }

    #[Test]
    public function render_system_text_omits_footer_note_when_null(): void
    {
        $text = $this->service->renderSystemText(
            title:       'T',
            intro:       'I',
            contentText: 'C',
            footerNote:  null
        );

        // Should end with the signature, no extra line
        $this->assertStringContainsString('Biblioteca - Mensajeria automatica', $text);
    }

    #[Test]
    public function render_system_text_returns_non_empty_string(): void
    {
        $text = $this->service->renderSystemText('T', 'I', 'C');
        $this->assertNotEmpty($text);
    }
}
