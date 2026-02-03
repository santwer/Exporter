<?php

namespace Santwer\Exporter\Tests\Unit\Exportables;

use Illuminate\Support\Facades\Storage;
use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\GlobalTokens;
use Santwer\Exporter\Exportables\Exportable;
use Santwer\Exporter\Processor\WordTemplateExporter;
use Santwer\Exporter\Tests\TestCase;

class ExportableTest extends TestCase
{
    public function test_create_returns_exportable_instance(): void
    {
        $export = new class implements FromWordTemplate, GlobalTokens {
            public function wordTemplateFile(): string { return ''; }
            public function values(): array { return []; }
        };
        $instance = Exportable::create($export, 'path', 'name.docx');
        $this->assertInstanceOf(Exportable::class, $instance);
    }

    public function test_constructor_and_get_format(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, GlobalTokens {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function values(): array { return []; }
        };
        $exportable = new Exportable($export, 'exports', 'out.docx', null, 'docx');
        $this->assertSame('docx', $exportable->getFormat());
    }

    public function test_pre_process_and_get_folder_sub_batch(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, GlobalTokens {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function values(): array { return []; }
        };
        $exportable = new Exportable($export, 'exports', 'out.docx');
        $exportable->preProcess('batch1');
        $this->assertNotEmpty($exportable->getFolder());
        $this->assertNotEmpty($exportable->getSubBatch());
    }

    public function test_get_closures(): void
    {
        $export = new class implements FromWordTemplate, GlobalTokens {
            public function wordTemplateFile(): string { return ''; }
            public function values(): array { return []; }
        };
        $exportable = new Exportable($export, 'path', 'name.docx');
        $exportable->whenDone(fn () => null);
        $exportable->whenPDFDone(fn () => null);
        [$done, $pdfDone] = $exportable->getClosures();
        $this->assertNotNull($done);
        $this->assertNotNull($pdfDone);
    }

    public function test_sub_process_docx_stores_file(): void
    {
        Storage::fake('local');
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, GlobalTokens {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function values(): array { return []; }
        };
        $exportable = new Exportable($export, 'exports', 'batch-out.docx', 'local');
        $exportable->preProcess('batch1');
        $exporter = new WordTemplateExporter();
        $result = $exportable->subProcess($exporter, false);
        $this->assertNotFalse($result);
        Storage::disk('local')->assertExists('exports/batch-out.docx');
    }

    public function test_copy_own_file_of_array_returns_false_when_no_match(): void
    {
        $export = new class implements FromWordTemplate, GlobalTokens {
            public function wordTemplateFile(): string { return ''; }
            public function values(): array { return []; }
        };
        $exportable = new Exportable($export, 'path', 'other.pdf');
        $exportable->preProcess('b');
        $result = $exportable->copyOwnFileOfArray(['/some/path/file.pdf'], true);
        $this->assertFalse($result);
    }
}
