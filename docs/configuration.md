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
| `word2pdf` | array | see below | LibreOffice PDF conversion. |

## `word2pdf`

```php
'word2pdf' => [
    'soffice_prefix' => env('SOFFICE_PATH', ''),
    'command'        => env('SOFFICE_COMMAND_PATH', 'soffice --convert-to pdf --outdir ? ? --headless'),
],
```

- **`soffice_prefix`**: Prefix for the `soffice` command (e.g. full path on Windows). Set `SOFFICE_PATH` in `.env` if `soffice` is not on `PATH`.
- **`command`**: Full command template; `?` placeholders are replaced with output directory and input file. Override with `SOFFICE_COMMAND_PATH` if needed.
