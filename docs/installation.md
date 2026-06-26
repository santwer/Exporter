# Installation

## Composer

Install via [Composer](https://getcomposer.org/):

```bash
composer require santwer/exporter
```

Or add to your `composer.json`:

```json
{
    "require": {
        "santwer/exporter": "^0.5"
    }
}
```

Then run `composer update`.

## LibreOffice (for PDF export)

PDF export uses LibreOffice’s `soffice` command to convert `.docx` to `.pdf`. Install LibreOffice if you need PDF output.

### Linux

```bash
sudo apt-get install libreoffice
```

### Windows

1. Download and install [LibreOffice](https://www.libreoffice.org/download/download/).
2. Add the directory containing `soffice` (or `soffice.exe`) to your system `PATH`, or set the `SOFFICE_PATH` environment variable (see [Configuration](configuration.md)).

Example `.env` (use forward slashes on Windows):

```env
SOFFICE_PATH="C:/Program Files/LibreOffice/program"
```

## Security note

PDF conversion runs the `soffice` command with file paths from your application. Only use templates from trusted sources and sanitize any user-provided template paths to avoid command injection.

If PDF export fails, see [PDF Troubleshooting](pdf-troubleshooting.md).
