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
}
