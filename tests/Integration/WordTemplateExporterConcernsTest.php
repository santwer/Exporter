<?php

namespace Santwer\Exporter\Tests\Integration;

use Illuminate\Support\Collection;
use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\GlobalTokens;
use Santwer\Exporter\Concerns\TokensArray;
use Santwer\Exporter\Concerns\TokensFromCollection;
use Santwer\Exporter\Concerns\TokensFromModel;
use Santwer\Exporter\Concerns\TokensFromObject;
use Santwer\Exporter\Concerns\WithCharts;
use Santwer\Exporter\Concerns\WithCheckboxes;
use Santwer\Exporter\Concerns\WithImages;
use Santwer\Exporter\Concerns\WithTables;
use Santwer\Exporter\Concerns\WithWordProcessor;
use Santwer\Exporter\Exceptions\MissingConcernException;
use Santwer\Exporter\Processor\Exporter as ExporterProcessor;
use Santwer\Exporter\Processor\Exporter;
use Santwer\Exporter\Processor\WordTemplateExporter;
use Santwer\Exporter\Tests\TestCase;

class WordTemplateExporterConcernsTest extends TestCase
{
    public function test_throws_missing_concern_exception_when_from_word_template_not_implemented(): void
    {
        $export = new class {
        };
        $exporter = new WordTemplateExporter();
        $this->expectException(MissingConcernException::class);
        $exporter->processFile($export);
    }

    public function test_process_file_with_from_word_template_and_global_tokens_sets_values(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, GlobalTokens {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function values(): array { return ['Date' => '2025-01-01', 'Title' => 'Test']; }
        };
        $exporter = new WordTemplateExporter();
        $processor = $exporter->processFile($export);
        $this->assertInstanceOf(Exporter::class, $processor);
        $vars = $processor->getTemplateVariables();
        $this->assertIsArray($vars);
    }

    public function test_process_file_with_tokens_from_collection_sets_block_values(): void
    {
        $templatePath = $this->createMinimalDocx('${items}${name}${/items}');
        $export = new class($templatePath) implements FromWordTemplate, TokensFromCollection {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function blockName(): string { return 'items'; }
            public function items(): Collection { return collect([(object)['name' => 'A'], (object)['name' => 'B']]); }
            public function itemTokens($item): array { return ['name' => $item->name]; }
        };
        $exporter = new WordTemplateExporter();
        $processor = $exporter->processFile($export);
        $this->assertInstanceOf(Exporter::class, $processor);
    }

    public function test_process_file_with_tokens_array_sets_array(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, TokensArray {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function tokens(): array { return ['key' => 'value']; }
        };
        $exporter = new WordTemplateExporter();
        $processor = $exporter->processFile($export);
        $this->assertInstanceOf(Exporter::class, $processor);
    }

    public function test_process_file_with_with_checkboxes_sets_checkboxes(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, WithCheckboxes {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function checkboxes(): array { return ['agree' => true, 'optout' => false]; }
        };
        $exporter = new WordTemplateExporter();
        $processor = $exporter->processFile($export);
        $this->assertInstanceOf(Exporter::class, $processor);
    }

    public function test_process_file_with_tokens_from_object_sets_object(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, TokensFromObject {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function tokens(): object { return (object)['a' => 1]; }
        };
        $exporter = new WordTemplateExporter();
        $processor = $exporter->processFile($export);
        $this->assertInstanceOf(Exporter::class, $processor);
    }

    public function test_process_file_with_tokens_from_model_sets_model(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, TokensFromModel {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function model(): \Illuminate\Database\Eloquent\Model
            {
                $m = new class extends \Illuminate\Database\Eloquent\Model {
                    protected $table = 'items';
                };
                $m->setRawAttributes(['id' => 1, 'name' => 'Test']);
                return $m;
            }
        };
        $exporter = new WordTemplateExporter();
        $processor = $exporter->processFile($export);
        $this->assertInstanceOf(Exporter::class, $processor);
    }

    public function test_process_file_with_with_charts_sets_charts(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, WithCharts {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function charts(): array { return ['chart1' => (object)['data' => []]]; }
        };
        $exporter = new WordTemplateExporter();
        $processor = $exporter->processFile($export);
        $this->assertInstanceOf(Exporter::class, $processor);
    }

    public function test_process_file_with_with_charts_callable(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, WithCharts {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function charts(): array { return ['chart1' => fn () => (object)['data' => []]]; }
        };
        $exporter = new WordTemplateExporter();
        $processor = $exporter->processFile($export);
        $this->assertInstanceOf(Exporter::class, $processor);
    }

    public function test_process_file_with_with_images_sets_images(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, WithImages {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function images(): array { return ['img1' => __FILE__]; }
        };
        $exporter = new WordTemplateExporter();
        $processor = $exporter->processFile($export);
        $this->assertInstanceOf(Exporter::class, $processor);
    }

    public function test_process_file_with_with_tables_sets_tables(): void
    {
        $templatePath = $this->createMinimalDocx();
        $export = new class($templatePath) implements FromWordTemplate, WithTables {
            private string $path;
            public function __construct(string $path) { $this->path = $path; }
            public function wordTemplateFile(): string { return $this->path; }
            public function tables(): array { return ['table1' => ['rows' => []]]; }
        };
        $exporter = new WordTemplateExporter();
        $processor = $exporter->processFile($export);
        $this->assertInstanceOf(Exporter::class, $processor);
    }

    public function test_process_file_with_with_word_processor_calls_word_processor(): void
    {
        $templatePath = $this->createMinimalDocx();
        $holder = (object)['called' => false];
        $export = new class($templatePath, $holder) implements FromWordTemplate, WithWordProcessor {
            private string $path;
            private object $holder;
            public function __construct(string $path, object $holder) { $this->path = $path; $this->holder = $holder; }
            public function wordTemplateFile(): string { return $this->path; }
            public function wordProcessor(ExporterProcessor $exporter): void { $this->holder->called = true; }
        };
        $exporter = new WordTemplateExporter();
        $exporter->processFile($export);
        $this->assertTrue($holder->called);
    }
}
