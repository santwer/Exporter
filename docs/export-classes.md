# Export Classes

Export classes are the main way to use the package. Each export class implements at least `FromWordTemplate` and optionally other concerns (GlobalTokens, TokensFromCollection, WithCharts, etc.).

## Required: FromWordTemplate

Every export class must implement `FromWordTemplate`, which defines the Word template file:

```php
public function wordTemplateFile(): string
```

Return a path relative to `storage_path()` or an absolute path. See [Template Resolution](template-resolution.md).

## Creating an export class

Use the Artisan command:

```bash
php artisan make:word {ClassName}
```

Replace `{ClassName}` with the name of your export class (e.g. `InvoiceExport`). The class is created in your application (e.g. `app/Http/Export/`).

## WordExport facade

Use the `WordExport` facade to run exports. The output format is determined by the file extension you pass.

```php
use Santwer\Exporter\Facade\WordExport;
```

### download($export, $fileName)

Returns an HTTP response that triggers a file download.

```php
WordExport::download(new MyExport(), 'export.docx');
WordExport::download(new MyExport(), 'export.pdf');  // requires LibreOffice
```

### store($export, $filePath)

Saves the exported file to the given path. Filename is generated.

```php
WordExport::store(new MyExport(), 'exports/');
```

### storeAs($export, $filePath, $name)

Saves with a specific filename.

```php
WordExport::storeAs(new MyExport(), 'exports/', 'invoice-2024.docx');
```

### queue($export, $filePath)

Dispatches a job to generate the file asynchronously.

```php
WordExport::queue(new MyExport(), 'exports/report.docx');
```

### batchStore(...$exports)

Stores multiple exports. Each argument must be an `Santwer\Exporter\Exportables\Exportable` instance wrapping the export class, path, and filename.

```php
use Santwer\Exporter\Exportables\Exportable;

WordExport::batchStore(
    new Exportable(new MyExport1(), 'exports/', 'one.docx'),
    new Exportable(new MyExport2(), 'exports/', 'two.pdf'),
);
```

See [Batch & Queue](batch-and-queue.md) for details.
