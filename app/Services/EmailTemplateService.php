<?php
declare(strict_types=1);

namespace Services;

final class EmailTemplateService
{
    public function renderSystem(
        string $preheader,
        string $title,
        string $intro,
        string $contentHtml,
        ?string $footerNote = null
    ): string {
        $safePreheader = $this->escape($preheader);
        $safeTitle = $this->escape($title);
        $safeIntro = $this->escape($intro);
        $safeFooter = $footerNote !== null ? $this->escape($footerNote) : '';
        $year = date('Y');

        return <<<HTML
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$safeTitle}</title>
  <style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
    table { border-collapse: collapse !important; }
    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }

    @media screen and (max-width: 640px) {
      .shell { width: 100% !important; }
      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }
      .title { font-size: 22px !important; }
      .body-copy { font-size: 15px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background:#eef2ff;font-family:'Trebuchet MS','Segoe UI',Roboto,Arial,sans-serif;color:#0f172a;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;">{$safePreheader}</div>
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#eef2ff;padding:26px 12px;">
    <tr>
      <td align="center">
        <table class="shell" role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);">
          <tr>
            <td class="px-mobile" style="background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;">
              <div style="font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;">Sistema Biblioteca</div>
              <div class="title" style="font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;">{$safeTitle}</div>
              <div style="margin-top:10px;font-size:12px;opacity:.88;">Notificacion automatica</div>
            </td>
          </tr>

          <tr>
            <td class="px-mobile" style="padding:24px 26px 8px;">
              <p class="body-copy" style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;">{$safeIntro}</p>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;">
                <tr>
                  <td style="padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;">
                    {$contentHtml}
                  </td>
                </tr>
              </table>

              <div style="height:14px;line-height:14px;font-size:14px;">&nbsp;</div>
            </td>
          </tr>

          <tr>
            <td class="px-mobile" style="padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;">
              <div style="font-weight:700;color:#334155;">Biblioteca - Mensajeria automatica</div>
              <div>Este correo fue generado por el sistema. {$year}</div>
              {$this->renderFooterLine($safeFooter)}
            </td>
          </tr>
        </table>

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="width:100%;max-width:640px;">
          <tr>
            <td style="padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;">
              Si no reconoces este mensaje, ignoralo o contacta al administrador.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public function renderSystemText(
        string $title,
        string $intro,
        string $contentText,
        ?string $footerNote = null
    ): string {
        $lines = [
            $title,
            str_repeat('=', max(10, mb_strlen($title))),
            '',
            trim($intro),
            '',
            trim($contentText),
            '',
            'Biblioteca - Mensajeria automatica',
        ];

        if ($footerNote !== null && trim($footerNote) !== '') {
            $lines[] = trim($footerNote);
        }

        return implode("\n", $lines);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function renderFooterLine(string $footer): string
    {
        if (trim($footer) === '') {
            return '';
        }

        return '<div>' . $footer . '</div>';
    }
}
