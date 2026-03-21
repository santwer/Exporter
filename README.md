# Word Template Exporter

Laravel package for exporting Word templates with placeholders as `.docx` or `.pdf`. Based on [phpoffice/phpword](https://github.com/PHPOffice/PHPWord).

<p style="text-align: center;">
<a href="https://github.com/santwer/Exporter"><img src="https://img.shields.io/github/commit-activity/m/santwer/Exporter" alt="Commit Activity"></a>
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

**Full documentation:** [santwer.github.io/Exporter/](https://santwer.github.io/Exporter/) · **Source:** [github.com/santwer/Exporter](https://github.com/santwer/Exporter). The docs cover installation, configuration, export classes, template syntax, charts, images, tables, and more.

**GitHub Pages (optional mirror):** CI deploys the MkDocs site from `main`/`master` if the repo uses **Settings → Pages → Build and deployment → Source: GitHub Actions**. URL pattern: `https://<user>.github.io/<repo>/` (set `site_url` in `mkdocs.yml` to that base if you need canonical links).

## Quick example

```php
use Santwer\Exporter\Facade\WordExport;

WordExport::download(new MyExport(), 'export.docx');
```

Create an export class that implements `FromWordTemplate` (and optional concerns like `GlobalTokens`, `TokensFromCollection`). See the docs for export classes, template syntax, and configuration.
