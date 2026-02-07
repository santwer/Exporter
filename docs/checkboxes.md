# Checkboxes

Implement the `WithCheckboxes` interface to replace text placeholders in your Word template with checked or unchecked checkboxes.

## Interface

```php
interface WithCheckboxes
{
    public function checkboxes(): array;
}
```

Return an array mapping placeholder names to boolean values. Keys are the placeholder names in the template; values are `true` (checked) or `false` (unchecked).

## Example

```php
use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\WithCheckboxes;

class FormExport implements FromWordTemplate, WithCheckboxes
{
    public function wordTemplateFile(): string
    {
        return 'templates/form.docx';
    }

    public function checkboxes(): array
    {
        return [
            'agreed'    => true,
            'newsletter' => false,
        ];
    }
}
```

In the template, use placeholders with the same names (e.g. `agreed`, `newsletter`). They are replaced with a checked or unchecked checkbox according to the boolean value.
