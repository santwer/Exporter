<?php

namespace Santwer\Exporter\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PhpOffice\PhpWord\PhpWord;
use Santwer\Exporter\ExporterProvider;

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
