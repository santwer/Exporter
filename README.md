# Word Template Exporter

Laravel package for exporting Word templates with placeholders as `.docx` or `.pdf`. Based on [phpoffice/phpword](https://github.com/PHPOffice/PHPWord).

<p style="text-align: center;">
<a href="https://github.com/santwer/exporter"><img src="https://img.shields.io/github/commit-activity/m/santwer/exporter" alt="Commit Activity"></a>
<a href="https://packagist.org/packages/santwer/exporter"><img src="https://img.shields.io/packagist/dt/santwer/exporter" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/santwer/exporter"><img src="https://img.shields.io/packagist/v/santwer/exporter" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/santwer/exporter"><img src="https://img.shields.io/packagist/l/santwer/exporter" alt="License"></a>
</p>

## Installation

```bash
composer require santwer/exporter
```

For PDF export, LibreOffice must be installed. See the full documentation.

## Full documentation

**Full documentation** is in the [docs/](docs/) folder (MkDocs). When hosted on Read the Docs, use your project’s RTD URL. The docs cover installation, configuration, export classes, template syntax, charts, images, tables, and more.

## Quick example

```php
use Santwer\Exporter\Facade\WordExport;

WordExport::download(new MyExport(), 'export.docx');
```

Create an export class that implements `FromWordTemplate` (and optional concerns like `GlobalTokens`, `TokensFromCollection`). See the docs for export classes, template syntax, and configuration.
