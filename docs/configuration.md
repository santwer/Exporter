# Configuration

Publish and edit the config file:

```bash
php artisan vendor:publish --tag=exporter-config
```

Config file: `config/exporter.php`.

## Options

| Key | Type | Default | Description |
|-----|------|--------|-------------|
| `batch_size` | int | `200` | Number of exports per batch. Can be overridden via `EXPORTER_BATCH_SIZE`. |
| `removeRelations` | bool | `true` | When using Exportable trait: remove relations not referenced in the template. |
| `relationsFromTemplate` | bool | `false` | Derive which relations to load from template placeholders. |
| `temp_folder` | string | `sys_get_temp_dir()` | Directory for temporary export files. Override with `EXPORTER_TEMP_FOLDER`. |
| `temp_folder_relative` | bool | `false` | Whether `temp_folder` is relative. Override with `EXPORTER_TEMP_FOLDER_RELATIVE`. |
| `validate_xml` | bool | `true` | Before saving, check the processed XML (document, headers, footers) and log a warning with a `DocxXmlDiagnostics` report if it is invalid, i.e. Word could not open the file. Only logs, never changes or blocks the export. Costs a few milliseconds per document. Override with `EXPORTER_VALIDATE_XML`. |
| `word2pdf` | array | see below | LibreOffice PDF conversion. |

## `word2pdf`

```php
'word2pdf' => [
    'soffice_prefix' => env('SOFFICE_PATH', ''),
    'command'        => env('SOFFICE_COMMAND_PATH', 'soffice --convert-to pdf --outdir ? ? --headless'),
    'isolate_instances' => env('SOFFICE_ISOLATE_INSTANCES', true),
    'timeout'        => env('SOFFICE_TIMEOUT', 120),
],
```

- **`soffice_prefix`**: Prefix for the `soffice` command (e.g. full path on Windows). Set `SOFFICE_PATH` in `.env` if `soffice` is not on `PATH`.
- **`command`**: Full command template; `?` placeholders are replaced with output directory and input file. Override with `SOFFICE_COMMAND_PATH` if needed.
- **`isolate_instances`**: Every conversion runs with its own temporary LibreOffice user profile (`-env:UserInstallation`), which is deleted afterwards. Without it, parallel conversions (several PHP-FPM or queue workers) share one LibreOffice instance and PDFs can go missing or end up incomplete. Leave it enabled unless you manage profiles yourself. A custom `command` gets the argument appended unless it already contains `UserInstallation`.
- **`timeout`**: Seconds per converted file (a batch of 10 files gets 10 × timeout). `null` or `0` disables the timeout.

Use `ExportHelper::sofficeIsAvailable()` to check whether PDF export is possible. When conversion fails, see [PDF Troubleshooting](pdf-troubleshooting.md) for `PDFConversionException`, log output, and DOCX XML analysis.
