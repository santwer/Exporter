<?php

namespace Santwer\Exporter\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PhpOffice\PhpWord\PhpWord;
use Santwer\Exporter\ExporterProvider;
use Santwer\Exporter\Helpers\ExportHelper;
use Santwer\Exporter\Processor\GlobalVariables;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ExporterProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('exporter.temp_folder', sys_get_temp_dir().'/exporter-test-'.uniqid());
        $app['config']->set('exporter.temp_folder_relative', false);
        $app['config']->set('exporter.batch_size', 200);
    }

    protected function tearDown(): void
    {
        GlobalVariables::clear();
        ExportHelper::resetBatchCounters();
        ExportHelper::resetGarbage();
        parent::tearDown();
    }

    protected function createMinimalDocx(string $placeholder = '${test}'): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText($placeholder);
        $path = sys_get_temp_dir().'/exporter-minimal-'.uniqid().'.docx';
        $phpWord->save($path, 'Word2007');

        return $path;
    }
}
