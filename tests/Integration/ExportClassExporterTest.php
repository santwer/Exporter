<?php

namespace Santwer\Exporter\Tests\Integration;

use Illuminate\Support\Facades\Storage;
use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\GlobalTokens;
use Santwer\Exporter\Processor\ExportClassExporter;
use Santwer\Exporter\Processor\WordTemplateExporter;
use Santwer\Exporter\Tests\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportClassExporterTest extends TestCase
{
    public function test_download_returns_binary_file_response(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, GlobalTokens {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function values(): array { return []; }
        };
        $exporter = new ExportClassExporter(new WordTemplateExporter());
        $response = $exporter->download($export, 'output.docx');
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function test_store_as_saves_file(): void
    {
        Storage::fake('local');
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, GlobalTokens {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function values(): array { return []; }
        };
        $exporter = new ExportClassExporter(new WordTemplateExporter());
        $result = $exporter->storeAs($export, 'exports', 'stored.docx', 'local');
        $this->assertNotFalse($result);
        Storage::disk('local')->assertExists('exports/stored.docx');
    }
}
