# Exportable (Deprecated)

> **Deprecated.** This usage path (Exportable trait + Eloquent builder methods like `template()`, `export()`, `exportPdf()`) is **deprecated** and will be removed in a future major version. Use export classes (FromWordTemplate) and the `WordExport` facade instead.

This page documents the deprecated model-based export flow for existing code. New code should use [Export Classes](export-classes.md) and the [WordExport facade](export-classes.md#wordexport-facade).

## Exportable trait

Add the trait to your Eloquent model:

```php
use Santwer\Exporter\Exportable;

class User extends Model
{
    use Exportable;
}
```

By default, all model attributes (and loaded relations) are available as template tokens (e.g. `${name}`, `${email}`).

## HasTokens

Implement `HasTokens` to define which attributes are exported:

```php
use Santwer\Exporter\Exportable;
use Santwer\Exporter\Concerns\HasTokens;

class User extends Model implements HasTokens
{
    use Exportable;

    public function exportTokens(): array
    {
        return [
            'fullName' => $this->first_name . ' ' . $this->last_name,
            'email'    => $this->email,
        ];
    }
}
```

Only the keys returned by `exportTokens()` are available in the template.

## HasTemplate

Implement `HasTemplate` to set a default template for the model:

```php
use Santwer\Exporter\Exportable;
use Santwer\Exporter\Concerns\HasTemplate;

class User extends Model implements HasTemplate
{
    use Exportable;

    public function exportTemplate(): string
    {
        return 'templates/user-template.docx';
    }
}
```

If you do not call `->template(...)` on the builder, this path is used.

## Block name

By default the block name is the table name (e.g. `users` for `${users}...${/users}`). Override with `$exportBlock`:

```php
protected $exportBlock = 'customer';
```

Then use `${customer}...${/customer}` in the template.

## Builder methods

- **template($path)**: Set the Word template path. Must be called before export methods.
- **export()**: Returns a download response for all matching records.
- **exportFirst()**: Exports only the first record.
- **exportPdf()**: Exports as PDF (requires LibreOffice).
- **exportFirstPdf()**: First record as PDF.
- **store($path)**: Saves the file to storage; filename is generated.
- **storeAs($path, $name)**: Saves with a specific filename.

Example:

```php
User::where('active', true)
    ->template('templates/users.docx')
    ->export();

User::find(1)->export('user.docx', ['template' => 'templates/user.docx']);
```

## Relation autoloading

The package can infer which relations are needed from template placeholders and load them automatically. So instead of:

```php
User::with('posts')->with('posts.comments')->template('templates/users.docx')->export();
```

you can use:

```php
User::template('templates/users.docx')->export();
```

This behaviour can be tuned with `config('exporter.removeRelations')` and `config('exporter.relationsFromTemplate')`.

## Condition-based relation variables

In the template you can reference a single related record by ID or condition:

- `${orders:15.product_id}` — order with ID 15.
- `${orders:product_id,=,4.product_id}` — order where `product_id` equals 4.

If no record matches, the value is null.

## Migration to export classes

**Before (deprecated):**

```php
class User extends Model { use Exportable; }
User::where('active', true)->template('templates/users.docx')->export();
```

**After (recommended):**

```php
class UserExport implements FromWordTemplate, TokensFromCollection
{
    public function __construct(private Collection $users) {}

    public function wordTemplateFile(): string { return 'templates/users.docx'; }
    public function blockName(): string { return 'users'; }
    public function items(): Collection { return $this->users; }
    public function itemTokens($user): array { return $user->toArray(); }
}

WordExport::download(
    new UserExport(User::where('active', true)->get()),
    'users.docx'
);
```

Use [Export Classes](export-classes.md) and [Concerns](concerns.md) for all new exports.
