# Images

Implement the `WithImages` interface to replace image placeholders in your Word template with images.

## Interface

```php
interface WithImages
{
    public function images(): array;
}
```

Return an array mapping placeholder names to image data. Keys are the placeholder names in the template.

## Value types

Each value can be:

1. **String**: Path to the image file (absolute or relative to `public_path()`).
2. **Array**: `['path' => string, 'width' => int, 'height' => int, 'ratio' => bool]`. `ratio` controls aspect ratio preservation.
3. **Closure**: A callable that returns a string path or an array as above.

## Example

```php
use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\WithImages;

class ReportExport implements FromWordTemplate, WithImages
{
    public function wordTemplateFile(): string
    {
        return 'templates/report.docx';
    }

    public function images(): array
    {
        return [
            'CompanyLogo' => public_path('images/logo.jpg'),
            'UserAvatar'  => [
                'path'   => public_path('images/avatar.jpg'),
                'width'  => 100,
                'height' => 100,
                'ratio'  => false,
            ],
            'HeaderImage'  => function () {
                return [
                    'path'   => public_path('images/header.png'),
                    'width'  => 600,
                    'height' => 200,
                    'ratio'  => true,
                ];
            },
        ];
    }
}
```

In the template, use placeholders with the same names (e.g. `CompanyLogo`, `UserAvatar`, `HeaderImage`). They are replaced with the corresponding images.

## PHPWord reference

For more options, see [PHPWord template images](https://phpoffice.github.io/PHPWord/usage/template.html#setimagevalue).
