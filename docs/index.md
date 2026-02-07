# Word Template Exporter

A Laravel package for exporting Word templates with placeholders as `.docx` or `.pdf` files. Placeholders (e.g. `${variable}`, `${block}...${/block}`) are filled with data from export classes, Eloquent models, or collections.

## Features

- **Word template processing**: Placeholders in Word templates are replaced with your data.
- **Export formats**: `.docx` (Word) and `.pdf` (via LibreOffice).
- **Data sources**: Export classes implementing concerns/interfaces, or Eloquent models with the Exportable trait.
- **Extended features**: Charts, images, tables, checkboxes.
- **Relations**: Automatic loading and processing of Eloquent relations based on template placeholders.
- **Security**: Automatic XML escaping for all text values with UTF-8 support.

## Tech Stack

- **PHPWord** (`phpoffice/phpword`): Template processing and Word file generation.
- **LibreOffice**: PDF conversion via the `soffice` command.
- **Laravel**: Service provider, facades, Eloquent builder extensions, queue jobs.

## Architecture

### Data Flow

The export class (or Eloquent model) provides the **template path** and the **data**. The processor loads the template, fills an internal Exporter with that data, then runs PHPWord’s TemplateProcessor to produce the file.

**Export class path (recommended):**

```
Export class (wordTemplateFile() + concerns: values, items, charts, images, tables, …)
    │
    ▼
WordTemplateExporter::processFile($export)
    │  Resolves template path, creates Exporter(templatePath), fills it from export (setValues)
    ▼
Exporter (holds template path + values, blocks, charts, images, tables)
    │
    ▼
Exporter::getProcessedFile() / getProcessedConvertedFile()
    │  process() → TemplateProcessor (PHPWord) replaces placeholders, saveAs(.docx)
    ▼
.docx file
    │  If format is PDF:
    ▼
PDFExporter::docxToPdf() (LibreOffice soffice)
    │
    ▼
.pdf file
```

**Model path (deprecated):** Eloquent builder with Exportable → ModelProcessor builds data from the model → Exporter (same as above) → .docx or .pdf.

1. **Export class** (or model) provides the template path and all data (tokens, blocks, charts, images, tables, checkboxes).
2. **WordTemplateExporter** (or ModelProcessor) resolves the template file path, creates an **Exporter** instance with that path, and fills it from the export/model.
3. **Exporter** holds the template path and data; when output is requested it runs **process()**, which uses **TemplateProcessor** (PHPWord) to replace placeholders and write the `.docx`.
4. For PDF, **PDFExporter** converts the `.docx` to `.pdf` via LibreOffice.

### Package Structure

| Directory | Purpose |
|-----------|---------|
| `Concerns/` | Interfaces for export classes (FromWordTemplate, GlobalTokens, WithCharts, etc.) |
| `Processor/` | WordTemplateExporter, ModelProcessor, TemplateProcessor, Exporter, PDFExporter |
| `Eloquent/` | Builder extensions for models |
| `Jobs/` | Batch export and Word-to-PDF queue jobs |
| `Facade/` | WordExport facade |
| `Traits/` | Exportable trait for models |
| `Commands/` | `make:word` Artisan command |

## Usage Paths

1. **Export classes** (recommended): Implement `FromWordTemplate` and optional concerns; use the `WordExport` facade.
2. **Exportable trait** (deprecated): Use the trait on models and call `->template()->export()` on the builder. See [Exportable (Deprecated)](exportable-deprecated.md).

Start with [Installation](installation.md) and [Quick Start](quickstart.md).
