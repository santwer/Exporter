# Charts

Implement the `WithCharts` interface to replace chart placeholders in your Word template with data-driven charts.

## Interface

```php
interface WithCharts
{
    public function charts(): array;
}
```

Return an array mapping placeholder names to chart objects or closures that return chart objects. Keys are the placeholder names in the template; values are PHPWord chart instances or closures.

## Supported chart types

- `pie`, `doughnut`
- `line`, `bar`, `stacked_bar`, `percent_stacked_bar`
- `column`, `stacked_column`, `percent_stacked_column`
- `area`, `radar`, `scatter`

## Example

```php
use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\WithCharts;
use PhpOffice\PhpWord\Element\Chart;

class ReportExport implements FromWordTemplate, WithCharts
{
    public function wordTemplateFile(): string
    {
        return 'templates/report.docx';
    }

    public function charts(): array
    {
        return [
            'salesChart' => function () {
                $categories = ['Q1', 'Q2', 'Q3', 'Q4'];
                $series = [100, 150, 200, 180];
                return new Chart('column', $categories, $series, [
                    'width'  => 1000000 * 5,
                    'height' => 1000000 * 5,
                    'showLegend' => true,
                ], 'Sales');
            },
        ];
    }
}
```

In the template, use a placeholder with the same name as the key (e.g. `salesChart`). The package replaces it with the chart.

## PHPWord reference

For chart options and template usage, see [PHPWord template documentation](https://phpoffice.github.io/PHPWord/usage/template.html#setchartvalue).
