# Template Resolution

The package resolves the path returned by `wordTemplateFile()` in a fixed order. Use this when placing templates and when choosing relative vs absolute paths.

## Resolution order

1. **Absolute path**: If the returned string is an absolute path and the file exists, that file is used.
2. **storage_path($path)**: The path is passed to Laravel’s `storage_path()` (e.g. `storage_path('templates/invoice.docx')`).
3. **storage_path('app/'.$path)**: The path is tried under `storage/app/` (e.g. `storage_path('app/templates/invoice.docx')`).

If no file is found after these steps, an exception is thrown.

## Supported formats

- `.docx` (Word 2007+)
- `.doc` (legacy Word)

## Examples

| Return value | Resolved to (conceptually) |
|--------------|----------------------------|
| `'templates/invoice.docx'` | `storage_path('templates/invoice.docx')` or then `storage_path('app/templates/invoice.docx')` |
| `storage_path('templates/invoice.docx')` | Used as-is if the file exists |
| `'/var/www/storage/templates/invoice.docx'` | Used as-is if the file exists |

## Best practice

Use relative paths under `storage` (e.g. `templates/...`) so the same code works across environments. Put template files in `storage/app/templates/` or adjust your path so one of the resolution steps finds the file.
