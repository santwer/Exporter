# Concerns & Interfaces

Export classes implement interfaces (concerns) to provide data and features. Every export class must implement **FromWordTemplate**. All others are optional.

## Required: FromWordTemplate

```php
interface FromWordTemplate
{
    public function wordTemplateFile(): string;
}
```

- Return a valid file path (relative to `storage_path()` or absolute).
- Supported formats: `.docx`, `.doc`.
- See [Template Resolution](template-resolution.md) for resolution order.

## Token interfaces

### GlobalTokens

Global placeholders (not inside blocks).

```php
interface GlobalTokens
{
    public function values(): array;
}
```

- Return key-value pairs: keys match placeholders `${key}` in the template.
- Values can be strings, numbers, or other primitives.

Example:

```php
public function values(): array
{
    return [
        'Date' => now()->format('Y-m-d'),
        'CompanyName' => 'My Company',
    ];
}
```

### TokensFromCollection

Collection-based blocks (loops).

```php
interface TokensFromCollection
{
    public function blockName(): string|array;
    public function items(): Collection;
    public function itemTokens($item): array;
}
```

- `blockName()`: name of the block in the template (e.g. `'customer'` for `${customer}...${/customer}`). Can be an array for multiple blocks.
- `items()`: collection of items to iterate.
- `itemTokens($item)`: map each item to an array of placeholder values. Nested arrays are supported for nested blocks.

Example:

```php
public function blockName(): string { return 'customer'; }

public function items(): Collection
{
    return collect([
        ['name' => 'Jane', 'email' => 'jane@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ]);
}

public function itemTokens($item): array { return $item; }
```

### TokensFromArray

Same as TokensFromCollection but `items()` returns an array. Internally converted to a collection.

```php
interface TokensFromArray
{
    public function blockName(): string|array;
    public function items(): array;
    public function itemTokens($item): array;
}
```

### TokensArray

Simple key-value tokens without blocks.

```php
interface TokensArray
{
    public function tokens(): array;
}
```

### TokensFromObject

Single object converted to tokens (no blocks). Object is serialized to an array internally.

```php
interface TokensFromObject
{
    public function tokens(): object;
}
```

### TokensFromModel

Single Eloquent model converted to tokens (no blocks).

```php
interface TokensFromModel
{
    public function model(): Model;
}
```

## Feature interfaces

### WithCharts

Replace chart placeholders. See [Charts](charts.md).

```php
interface WithCharts
{
    public function charts(): array;
}
```

### WithImages

Replace image placeholders. See [Images](images.md).

```php
interface WithImages
{
    public function images(): array;
}
```

### WithTables

Replace table placeholders. See [Tables](tables.md).

```php
interface WithTables
{
    public function tables(): array;
}
```

### WithCheckboxes

Replace text with checked/unchecked checkboxes. See [Checkboxes](checkboxes.md).

```php
interface WithCheckboxes
{
    public function checkboxes(): array;
}
```

## Combining interfaces

You can combine any of the optional concerns with `FromWordTemplate`:

```php
class InvoiceExport implements
    FromWordTemplate,
    GlobalTokens,
    TokensFromCollection,
    WithCharts,
    WithImages,
    WithTables
{
    // Implement all required methods
}
```

`FromWordTemplate` is always required; the rest are optional.
