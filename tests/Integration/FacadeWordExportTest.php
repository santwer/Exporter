<?php

namespace Santwer\Exporter\Tests\Integration;

use Illuminate\Support\Facades\Storage;
use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\GlobalTokens;
use Santwer\Exporter\Facade\WordExport;
use Santwer\Exporter\Processor\ExportClassExporter;
use Santwer\Exporter\Tests\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FacadeWordExportTest extends TestCase
{
    public function test_wordexport_service_resolves_to_export_class_exporter(): void
    {
        $instance = $this->app->make('wordexport');
        $this->assertInstanceOf(ExportClassExporter::class, $instance);
    }

    public function test_download_via_facade_returns_response(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, GlobalTokens {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function values(): array { return []; }
        };
        $response = WordExport::download($export, 'facade-out.docx');
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function test_store_as_via_facade(): void
    {
        Storage::fake('local');
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, GlobalTokens {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function values(): array { return []; }
        };
        $result = WordExport::storeAs($export, 'exports', 'facade.docx', 'local');
        $this->assertNotFalse($result);
    }
}
