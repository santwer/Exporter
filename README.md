# Exporter #
Package for easy Word Export.

## How to use
Add Trait *Exportable*
```php
use Santwer\Exporter\Exportable;

class User {
    use Exportable;
    ...
}
```

As default all Variables are available of the Model.
Add a Concern for special export fields.
```php
use Santwer\Exporter\Concerns\HasTokens;
...

class User implements HasTokens {
    use Exportable;
    ...
    public function exportTokens(): array
    {
        return [
            'name'  => $this->name,
            'email' => $this->email,
        ];
    }
}
```

You can add a fixed Template for each Model.
```php
use Santwer\Exporter\Concerns\HasTemplate;
...

class User implements HasTemplate {
    use Exportable;
    ...
    public function exportTemplate(): string
    {
        return '/template.docx';
    }
}
```

### Basic Export

export() gives download response back.
```php
return User::where(...)
        ->template('templatefile.docx')
        ->export();
```

If you defined export template in Model.
```php
return User::where(...)
        ->export();
```


```php
return User::where(...)
        ->template('templatefile.docx')
        ->store('storage/path');
```

```php
return User::where(...)
        ->template('templatefile.docx')
        ->storeAs('storage/path', 'name.docx');
```
Also possible To set Export after Executing the query or on a Model after Find 
```php
return User::where(...)
        ->first()
        ->export('templatefile.docx');
```
```php
return User::find(1234)
        ->export('templatefile.docx');
```

## Export as PDF
Gernally with the option format = pdf it is possible to export pdf. 
It is important that libreOffice is installed for that actions.
```php
return User::where(...)
        ->template('templatefile.docx')
        ->export(['format' => 'pdf']);
```
For short terms it is Possible to call Export functions as PDF
```php
return User::where(...)
        ->exportPdf();
```
```php
return User::where(...)
        ->exportFirstPdf();
```
