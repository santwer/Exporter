# XML Escaping and Security

The package automatically escapes all text values to ensure XML-safe output and prevent injection vulnerabilities in generated Word documents.

## Automatic Escaping

All text values passed through the package are automatically escaped for XML safety:

- **Ampersands** `&` → `&amp;`
- **Less than** `<` → `&lt;`
- **Greater than** `>` → `&gt;`
- **Double quotes** `"` → `&quot;`
- **Single quotes** `'` → `&apos;`

This applies to all text sources:

- `GlobalTokens::values()`
- `TokensFromCollection::itemTokens()`
- `TokensFromArray::itemTokens()`
- `TokensArray::tokens()` / `TokensFromObject::tokens()` / `TokensFromModel::model()`
- `WithTables::tables()` (headers and rows)
- `GlobalVariables::setVariable()` / `setVariables()`
- Block values (`setBlockValues()`)

### UTF-8 Support

The package ensures proper UTF-8 encoding for all text values. International characters (Ä, Ö, Ü, ñ, €, etc.) are preserved correctly.

**Implementation:** Uses `htmlspecialchars()` with `ENT_XML1 | ENT_QUOTES` flags and UTF-8 encoding, with `double_encode = false` to avoid double-escaping existing entities.

## AllowTags Mode

In some cases, you may want to include formatted text (bold, italic, etc.) in your placeholders. Use the `allowTags` parameter to preserve HTML/XML tags while still escaping quotes and ampersands.

### Using AllowTags in setValue()

```php
$processor = $exporter->getTemplateProcessor();
$processor->setValue('formatted', '<w:b>Bold</w:b> text', allowTags: true);
```

When `allowTags` is enabled:
- `<` and `>` are **preserved** (tags remain intact)
- `&`, `"`, `'` are **still escaped** (for XML attribute safety)

### Using AllowTags in Blocks

For block replacements, pass an array with `[value, allowTags]`:

```php
use Santwer\Exporter\Concerns\TokensFromCollection;

class MyExport implements FromWordTemplate, TokensFromCollection
{
    public function blockName(): string
    {
        return 'items';
    }

    public function items(): Collection
    {
        return collect([
            ['name' => 'Item 1', 'formatted' => '<w:b>Bold text</w:b>'],
            ['name' => 'Item 2', 'formatted' => '<w:i>Italic text</w:i>'],
        ]);
    }

    public function itemTokens($item): array
    {
        return [
            'name' => $item['name'],
            // Enable allowTags for this specific placeholder
            'formatted' => [$item['formatted'], true],
        ];
    }
}
```

**Template:**
```
${items}
    ${name}: ${formatted}
${/items}
```

### Security Warning

Only use `allowTags` with **trusted content**. If you pass user-generated content with `allowTags` enabled, you may expose your documents to XML injection vulnerabilities.

**Safe:**
```php
// Content from your application code
'formatted' => ['<w:b>System Message</w:b>', true]
```

**Unsafe:**
```php
// Content from user input - DO NOT use allowTags!
'description' => [$request->input('description'), true]  // ❌ Dangerous
```

## No Double-Escaping

The package automatically prevents double-escaping of already-encoded entities:

```php
// Input: 'Already &amp; encoded'
// Output: 'Already &amp; encoded' (not &amp;amp;)
```

This is achieved by:
1. Using `double_encode = false` in `htmlspecialchars()`
2. Disabling PHPWord's internal escaping via `Settings::setOutputEscapingEnabled(false)`

## Chart Titles and Labels

**Note:** Chart titles and labels are passed directly to PHPWord as part of chart objects. If you're using user-generated content in chart titles, ensure you escape it manually before creating the chart:

```php
use Santwer\Exporter\Concerns\WithCharts;

class MyExport implements FromWordTemplate, WithCharts
{
    public function charts(): array
    {
        $userTitle = 'Sales & Revenue'; // May contain special chars
        
        return [
            'salesChart' => function () use ($userTitle) {
                // PHPWord will handle this, but be aware of the content source
                return new Chart('column', $categories, $series, [
                    'width' => 5000000,
                    'height' => 5000000,
                ], $userTitle);
            },
        ];
    }
}
```

For maximum safety with untrusted chart titles, manually escape before passing:

```php
$safeTitle = htmlspecialchars($userTitle, ENT_XML1 | ENT_QUOTES, 'UTF-8', false);
```

## Type Handling

Non-string values are automatically converted:

| Input Type | Output |
|------------|--------|
| `null` | `''` (empty string) |
| `42` (int) | `'42'` |
| `3.14` (float) | `'3.14'` |
| `true` (bool) | `'1'` |
| `false` (bool) | `''` |

All conversions happen before XML escaping is applied.

## Best Practices

1. **Default to automatic escaping** - Let the package handle escaping for all user content
2. **Use allowTags sparingly** - Only for trusted, application-generated formatted content
3. **Never trust user input** - Always use automatic escaping (no allowTags) for user-generated content
4. **Test with special characters** - Include `&`, `<`, `>`, `"`, `'` in your test data
5. **UTF-8 everywhere** - Ensure your database and Laravel app use UTF-8 encoding
