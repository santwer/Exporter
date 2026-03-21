<?php

namespace Santwer\Exporter\Tests\Unit;

use Santwer\Exporter\ExporterProvider;
use Santwer\Exporter\Processor\ExportClassExporter;
use Santwer\Exporter\Tests\TestCase;

class ExporterProviderTest extends TestCase
{
    public function test_wordexport_is_registered(): void
    {
        $this->assertTrue($this->app->bound('wordexport'));
        $this->assertInstanceOf(ExportClassExporter::class, $this->app->make('wordexport'));
    }

    public function test_exporter_config_is_merged(): void
    {
        $this->assertNotNull(config('exporter.temp_folder'));
        $this->assertNotNull(config('exporter.batch_size'));
    }

    public function test_boot_registers_commands(): void
    {
        $this->assertTrue(class_exists(\Santwer\Exporter\Commands\MakeExportCommand::class));
    }
}
