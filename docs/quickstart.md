# Quick Start

Minimal example: an export class with a Word template and global tokens, then download via the facade.

## 1. Template

Create a Word file (e.g. `storage/app/templates/hello.docx`) with:

```
${Title}

${greeting}
    ${name}, ${email}
${/greeting}
```

## 2. Export class

Create a class that implements `FromWordTemplate` and `GlobalTokens`. For a single block you can also use `TokensFromCollection`.

```php
namespace App\Http\Export;

use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\GlobalTokens;
use Santwer\Exporter\Concerns\TokensFromCollection;
use Illuminate\Support\Collection;

class HelloExport implements FromWordTemplate, GlobalTokens, TokensFromCollection
{
    public function wordTemplateFile(): string
    {
        return 'templates/hello.docx';
    }

    public function values(): array
    {
        return [
            'Title' => 'Quick Start Export',
        ];
    }

    public function blockName(): string
    {
        return 'greeting';
    }

    public function items(): Collection
    {
        return collect([
            ['name' => 'Jane', 'email' => 'jane@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ]);
    }

    public function itemTokens($item): array
    {
        return $item;
    }
}
```

## 3. Download

In a controller or route:

```php
use Santwer\Exporter\Facade\WordExport;
use App\Http\Export\HelloExport;

return WordExport::download(new HelloExport(), 'hello.docx');
```

The response is a file download. Use `.pdf` as extension to get PDF (requires LibreOffice). See [Export Classes](export-classes.md) and [Template Syntax](template-syntax.md) for more.

## Security Note

All text values are automatically escaped for XML safety. Special characters like `&`, `<`, `>`, `"`, `'` are converted to XML entities. This prevents injection vulnerabilities. See [XML Escaping & Security](xml-escaping.md) for details on `allowTags` mode for formatted content.
