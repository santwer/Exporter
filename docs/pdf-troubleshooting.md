# PDF export troubleshooting

When PDF conversion fails, the package throws `Santwer\Exporter\Exceptions\PDFConversionException` and analyses the generated `.docx` for invalid XML. This helps you find the placeholder or content that broke the export.

## When it runs

PDF conversion happens in `PDFExporter::docxToPdf()` after the Word template has been filled and saved as a temporary `.docx`. If LibreOffice (`soffice`) cannot convert the file, the package:

1. Analyses the `.docx` XML parts (`word/document.xml`, headers, footers, …).
2. Throws `PDFConversionException` with a readable message (shown on Laravel’s error page in debug mode).
3. Writes a structured, multi-line entry to the application log.

Typical causes:

- **Invalid XML in the DOCX** — unescaped `<`, `>`, or `&` in token values (most common).
- **LibreOffice not available** — `soffice` not found or wrong `SOFFICE_PATH`.
- **Environment issues** — temp directory not writable, LibreOffice exit without detail.

See also [XML Escaping & Security](xml-escaping.md) for how escaping works and how to avoid broken XML.

## Exception: `PDFConversionException`

Thrown when `PDFExporter::docxToPdf()` or `PDFExporter::html2Pdf()` fails.

### Properties

| Property | Description |
|----------|-------------|
| `docxPath` | Path to the generated `.docx` that could not be converted (DOCX exports only). |
| `docxFindings` | Structured list of XML issues detected in the DOCX. |
| `sofficeOutput` | stdout from LibreOffice (if any). |
| `sofficeError` | stderr from LibreOffice (if any). |

### Exception message (Laravel error page)

`getMessage()` contains a human-readable report, for example:

```text
PDF conversion failed

2 DOCX XML issues detected — likely invalid placeholder content.

DOCX XML analysis (likely cause of the failed PDF export):
1) [word/document.xml] Suspicious text: unescaped angle brackets (< or >)
   Suspicious token/content: FEHLER: Müller & Co. <unclosed-tag "Quotes"
   XML context: …
Hint: special characters (<, >, &, quotes) must be escaped in placeholder values unless allowTags is enabled.

LibreOffice error:
…
```

In local development (`APP_DEBUG=true`), Laravel’s default exception page displays this message. No custom error view is required.

### Catching in application code

```php
use Santwer\Exporter\Exceptions\PDFConversionException;
use Santwer\Exporter\Facade\WordExport;

try {
    return WordExport::download($export, 'invoice.pdf');
} catch (PDFConversionException $e) {
    // User-facing summary
    report($e);

    // Programmatic access to findings
    foreach ($e->docxFindings as $finding) {
        logger()->warning('DOCX issue', $finding);
    }

    return back()->withErrors(['export' => 'PDF export failed. Check the log for details.']);
}
```

## Logging

`PDFConversionException` implements Laravel’s exception reporting hooks:

- **`report()`** — logs a formatted multi-line message via `logger()->error()`.
- **`context()`** — adds structured context for the same log entry:

```php
[
    'docx_path' => '/tmp/php_….docx',
    'finding_count' => 2,
    'findings' => [ /* … */ ],
    'soffice_output' => '',
    'soffice_error' => '…',
]
```

Example log output:

```text
[2026-06-26 …] local.ERROR: PDF conversion failed
2 DOCX XML issues detected — likely invalid placeholder content.
DOCX file: C:\Users\…\AppData\Local\Temp\php_….tmp

--- Issue 1: word/document.xml ---
Suspicious text: unescaped angle brackets (< or >)
Suspicious content: FEHLER: Müller & Co. <unclosed-tag "Quotes"
…
{"docx_path":"…","finding_count":2,"findings":[…],"soffice_output":"","soffice_error":"…"}
```

Search your log for `PDF conversion failed` or filter by exception class `PDFConversionException`.

## DOCX analysis: `DocxXmlDiagnostics`

Internal helper: `Santwer\Exporter\Helpers\DocxXmlDiagnostics`.

You can run the same analysis manually (e.g. in tests or debugging):

```php
use Santwer\Exporter\Helpers\DocxXmlDiagnostics;

$findings = DocxXmlDiagnostics::analyze('/path/to/generated.docx');
$report = DocxXmlDiagnostics::formatReport($findings);
```

Each finding may include:

| Key | Description |
|-----|-------------|
| `part` | XML part inside the DOCX (e.g. `word/document.xml`). |
| `type` | `parse_error` (libxml) or `suspicious_text` (unescaped content in `<w:t>` runs). |
| `message` | Short description of the issue. |
| `line`, `column` | Location in the XML part (parse errors). |
| `text` | Decoded suspicious token/content text. |
| `snippet` | Surrounding XML context. |

### What it checks

- Opens the DOCX as a ZIP archive.
- Validates XML parts: `document`, `header*`, `footer*`, `footnotes`, `endnotes`.
- Runs libxml on each part (parse errors with line/column).
- Scans `<w:t>` text runs for unescaped `<`, `>`, or `&`.

If no XML issues are found but conversion still fails, check LibreOffice output in the exception/log and verify [Configuration](configuration.md) (`SOFFICE_PATH`, temp folder permissions).

## Checking LibreOffice availability

Before offering PDF export in the UI, use:

```php
use Santwer\Exporter\Helpers\ExportHelper;

if (ExportHelper::sofficeIsAvailable()) {
    // PDF option enabled
}

$binary = ExportHelper::resolveSofficeBinary(); // e.g. C:\Program Files\LibreOffice\program\soffice.exe
```

Detection order:

1. `SOFFICE_PATH` from config / `.env` (directory containing `soffice` / `soffice.exe`).
2. `soffice` on `PATH`.
3. Common Windows install paths.

On Windows, set forward slashes in `.env` to avoid dotenv parse errors:

```env
SOFFICE_PATH="C:/Program Files/LibreOffice/program"
```

## Common fixes

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Log shows `unescaped angle brackets` / `unclosed-tag` in content | Raw `<` or `>` in token value | Let the package escape values; do not bypass `TemplateProcessor::replace()`. See [XML Escaping](xml-escaping.md). |
| Log shows `unescaped ampersand` | Raw `&` in value | Same — use normal export path; escaping is automatic. |
| `No obvious XML issues` + empty LibreOffice error | Wrong/missing `SOFFICE_PATH` | Set path in `.env`; see [Installation](installation.md). |
| PDF works in CLI but not in web request | Different env / permissions | Ensure PHP-FPM/web user can run `soffice` and write to the temp folder. |

## Related classes

| Class | Role |
|-------|------|
| `PDFExporter` | Runs LibreOffice conversion; throws `PDFConversionException`. |
| `PDFConversionException` | Exception with message, log context, and findings. |
| `DocxXmlDiagnostics` | Analyses DOCX XML and formats reports. |
| `ExportHelper` | `sofficeIsAvailable()`, `sofficeBinary()`, `resolveSofficeBinary()`. |

See [Processors (Reference)](processors.md#pdfexporter) for the conversion pipeline.
