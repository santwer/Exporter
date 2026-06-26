<?php

namespace Santwer\Exporter\Tests\Integration;

use Santwer\Exporter\Processor\Exporter;
use Santwer\Exporter\Tests\TestCase;
use Santwer\Exporter\Writer;

class ExporterTest extends TestCase
{
    public function test_set_value_and_get_processed_file_produces_docx(): void
    {
        $templatePath = $this->createMinimalDocx('${test}');
        $exporter = new Exporter($templatePath);
        $exporter->setValue('test', 'Replaced');
        $outPath = $exporter->getProcessedFile();
        $this->assertFileExists($outPath);
        $this->assertStringEndsWith('.tmp', $outPath);
    }

    public function test_set_block_values(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $exporter->setBlockValues('block', [['key' => 'value']]);
        $this->addToAssertionCount(1);
    }

    public function test_set_object_with_to_array(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $obj = new class {
            public function toArray(): array { return ['a' => 1, 'b' => 2]; }
        };
        $exporter->setObject($obj);
        $this->addToAssertionCount(1);
    }

    public function test_set_object_without_to_array_uses_json_encode(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $exporter->setObject((object)['x' => 'y']);
        $this->addToAssertionCount(1);
    }

    public function test_get_processed_converted_file_docx(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $outPath = $exporter->getProcessedConvertedFile(Writer::DOCX);
        $this->assertFileExists($outPath);
    }

    public function test_set_array_flattens_nested_arrays(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $exporter->setArray(['user' => ['name' => 'John', 'email' => 'john@test.com']]);
        $vars = $exporter->getTemplateVariables();
        $this->assertIsArray($vars);
    }

    public function test_set_checkbox(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $exporter->setCheckbox('agree', true);
        $exporter->setCheckbox('optout', false);
        $this->addToAssertionCount(1);
    }

    public function test_set_chart(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $exporter->setChart('sales', (object)['data' => []]);
        $this->addToAssertionCount(1);
    }

    public function test_set_image_with_string_path(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $exporter->setImage('logo', __FILE__);
        $this->addToAssertionCount(1);
    }

    public function test_set_tables(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $exporter->setTables(['table1' => ['rows' => []]]);
        $this->addToAssertionCount(1);
    }

    public function test_table_data_to_complex_block_with_callable(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $exporter->setTables(['table1' => fn() => ['rows' => [['Col1', 'Col2']]]]);
        $processor = $exporter->process();
        $this->assertNotNull($processor);
    }

    public function test_table_data_with_headers_and_style(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $exporter->setTables(['table1' => [
            'style' => 'gridTable',
            'headers' => [
                ['text' => 'Name', 'width' => 2000, 'style' => 'bold'],
                'Email'
            ],
            'rows' => [
                [['text' => 'John', 'width' => 2000], 'john@test.com']
            ]
        ]]);
        $processor = $exporter->process();
        $this->assertNotNull($processor);
    }

    public function test_process_with_combined_features(): void
    {
        $templatePath = $this->createMinimalDocx('${test}${block}${item}${/block}');
        $exporter = new Exporter($templatePath);
        $exporter->setValue('test', 'Value');
        $exporter->setBlockValues('block', [['item' => 'A'], ['item' => 'B']]);
        $exporter->setCheckbox('check', true);
        $processor = $exporter->process();
        $this->assertNotNull($processor);
    }

    public function test_get_template_processor_returns_same_instance(): void
    {
        $templatePath = $this->createMinimalDocx();
        $exporter = new Exporter($templatePath);
        $proc1 = $exporter->getTemplateProcessor();
        $proc2 = $exporter->getTemplateProcessor();
        $this->assertSame($proc1, $proc2);
    }

    public function test_table_inherits_font_style_from_placeholder_token(): void
    {
        $templatePath = $this->createStyledPlaceholderDocx('${table1}', [
            'bold' => true,
            'name' => 'Arial',
            'size' => 12,
        ]);
        $exporter = new Exporter($templatePath);
        $exporter->setTables([
            'table1' => [
                'headers' => [['width' => 2000, 'text' => 'Name']],
                'rows' => [['Cell']],
            ],
        ]);

        $path = $exporter->getProcessedFile();
        $zip = new \ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($xml);
        $this->assertStringContainsString('<w:b', $xml);
        $this->assertStringContainsString('w:ascii="Arial"', $xml);
    }

    public function test_explicit_default_font_style_skips_token_lookup(): void
    {
        $templatePath = $this->createStyledPlaceholderDocx('${table1}', [
            'bold' => true,
            'name' => 'Arial',
            'size' => 12,
        ]);
        $exporter = new Exporter($templatePath);
        $exporter->setTables([
            'table1' => [
                'defaultFontStyle' => ['italic' => true, 'name' => 'Times New Roman'],
                'rows' => [['Cell']],
            ],
        ]);

        $path = $exporter->getProcessedFile();
        $zip = new \ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($xml);
        $this->assertStringContainsString('<w:i', $xml);
        $this->assertStringContainsString('w:ascii="Times New Roman"', $xml);
    }

    public function test_per_cell_font_style_overrides_default(): void
    {
        $templatePath = $this->createStyledPlaceholderDocx('${table1}', [
            'bold' => true,
            'name' => 'Arial',
        ]);
        $exporter = new Exporter($templatePath);
        $exporter->setTables([
            'table1' => [
                'rows' => [[
                    ['text' => 'Custom', 'fontStyle' => ['italic' => true, 'name' => 'Courier New']],
                ]],
            ],
        ]);

        $path = $exporter->getProcessedFile();
        $zip = new \ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($xml);
        $this->assertStringContainsString('<w:i', $xml);
        $this->assertStringContainsString('w:ascii="Courier New"', $xml);
    }
}

