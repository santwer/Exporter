# Batch & Queue

## batchStore

Use `WordExport::batchStore()` to run multiple exports in one go. Each argument must be an `Santwer\Exporter\Exportables\Exportable` instance that wraps: the export class instance, the directory path, and the filename.

```php
use Santwer\Exporter\Facade\WordExport;
use Santwer\Exporter\Exportables\Exportable;

WordExport::batchStore(
    new Exportable(new ReportExport(), 'exports/', 'report.docx'),
    new Exportable(new InvoiceExport(), 'exports/', 'invoice.pdf'),
);
```

The package processes each export (Word or PDF depending on file extension) and writes files to the given directory. Batch size is controlled by `config('exporter.batch_size')` (default 200).

## queue

Use `WordExport::queue()` to generate a file asynchronously via Laravel’s queue:

```php
WordExport::queue(new MyExport(), 'exports/report.docx');
```

A job is dispatched; when it runs, the file is created at the given path. Ensure your queue worker is running and that the export class and template are available in the worker environment.

## Temp folder

Exports use a temporary directory for intermediate files (e.g. before PDF conversion). Default is PHP’s `sys_get_temp_dir()`. Override with `config('exporter.temp_folder')` or the `EXPORTER_TEMP_FOLDER` environment variable. Set `temp_folder_relative` (or `EXPORTER_TEMP_FOLDER_RELATIVE`) if that path is relative. The temp folder is used only during processing; final files are written to the paths you pass to `store`, `storeAs`, or `batchStore`.
