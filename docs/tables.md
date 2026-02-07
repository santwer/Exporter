# Tables

Implement the `WithTables` interface to replace table placeholders in your Word template with data tables.

## Interface

```php
interface WithTables
{
    public function tables(): array;
}
```

Return an array mapping placeholder names to table data or closures that return table data. Keys are the placeholder names in the template.

## Table data structure

Each value must be an array (or a closure returning one) with:

- **headers**: Array of header cells. Each cell can be a string or `['width' => int, 'text' => string]`. For PDF export, set column widths on headers so columns render correctly.
- **rows**: Array of rows; each row is an array of cell values (strings or `['width' => int, 'text' => string]`).
- **style** (optional): Array with `borderSize`, `borderColor`, `width`, etc.

## Example

```php
use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\WithTables;

class InvoiceExport implements FromWordTemplate, WithTables
{
    public function wordTemplateFile(): string
    {
        return 'templates/invoice.docx';
    }

    public function tables(): array
    {
        return [
            'invoiceItems' => function () {
                return [
                    'headers' => [
                        ['width' => 3000, 'text' => 'Item'],
                        ['width' => 2000, 'text' => 'Quantity'],
                        ['width' => 2000, 'text' => 'Price'],
                    ],
                    'rows' => [
                        ['Product A', '2', '100.00'],
                        ['Product B', '1', '50.00'],
                    ],
                    'style' => [
                        'borderSize' => 6,
                        'borderColor' => '000000',
                        'width'      => 7000,
                    ],
                ];
            },
        ];
    }
}
```

## PDF export

For PDF output, at least the headers should have column widths set. Unset column widths may not display correctly in the generated PDF.
