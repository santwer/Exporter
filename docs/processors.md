# Processors (Reference)

Internal reference for the processor classes that drive template processing and export. You typically use the package via the [WordExport facade](export-classes.md#wordexport-facade) and export classes; these classes are what run under the hood.

## WordTemplateExporter

Main processor for export classes that implement concerns/interfaces. It detects which concerns are implemented, loads the template file, and builds an `Exporter` instance with values, blocks, charts, images, tables, and checkboxes. Key method: `processFile(object $export): Exporter`. Concern processing order: GlobalTokens / TokensArray / TokensFromObject / TokensFromModel, then TokensFromCollection / TokensFromArray, then WithCharts, WithImages, WithTables, WithCheckboxes.

## ModelProcessor

Used for Eloquent models with the Exportable trait (deprecated path). Checks for the Exportable trait, discovers model relations via reflection, and prepares export attributes. Key methods: `checkForExportable(?object $class): bool`, `getAllRelations(Model $model, $heritage = 'all'): array`. Relation results are cached.

## TemplateProcessor

Extends PHPWord's template processor. Handles placeholder replacement and recursive block cloning (loops). Key methods: `setValue()`, `replace()`, `cloneRecrusiveBlocks()`, `arrayListRecusive()`. Used by `Exporter` to fill the Word document.

**XML Escaping:** The `replace()` method automatically escapes all text values for XML safety: `&`, `<`, `>`, `"`, `'` → XML entities. With `allowTags` parameter, only `&`, `"`, `'` are escaped while `<` and `>` are preserved. Supports UTF-8 and prevents double-escaping. See [XML Escaping & Security](xml-escaping.md).

## Exporter

Central handler that holds the template file and collected values, blocks, charts, images, and tables. Coordinates `TemplateProcessor` and triggers processing. Key methods: `setBlockValues()`, `setValue()` / `setArrayValues()`, `setChart()` / `setImage()` / `setTables()` / `setCheckbox()`, `process()`, `getProcessedFile()`, `getProcessedConvertedFile()` for PDF conversion.

**Output Escaping:** Disables PHPWord's internal XML escaping (`Settings::setOutputEscapingEnabled(false)`) to prevent double-escaping, as the `TemplateProcessor::replace()` method handles all escaping.

## PDFExporter

Handles conversion of Word or HTML to PDF via LibreOffice's `soffice` command. Key methods: `docxToPdf($docX, $path = null)`, `html2Pdf(string $html, ?string $path = null)`. Uses `config('exporter.word2pdf')` for command and prefix.

On failure, throws `PDFConversionException` and analyses the generated DOCX for invalid XML. See [PDF Troubleshooting](pdf-troubleshooting.md) for logging, exception details, and debugging.

## BatchProcessor

Trait used for processing multiple exports (e.g. batch store). Manages temp files and PDF conversion for batches. Key methods: `preProcess()`, `subProcess()`, `whenDone()` / `whenPDFDone()` for callbacks.

## VariablesConditionProcessor

Parses condition-based relation variables (e.g. `${relation:id.field}`, `${relation:field,=,value.field}`) and reduces them for relation detection. Key methods: `getReducedForRelations(array $variables)`, `getRelatedConditions(array $variables)`.

## GlobalVariables

Static helper for global placeholder values used across exports. Key methods: `setVariable()`, `setVariables()`, `getGlobalVariables()`. Used by the Exporter during `process()`.
