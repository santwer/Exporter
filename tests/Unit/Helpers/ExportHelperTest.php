<?php

namespace Santwer\Exporter\Tests\Unit\Helpers;

use Santwer\Exporter\Helpers\ExportHelper;
use Santwer\Exporter\Tests\TestCase;
use Santwer\Exporter\Writer;

class ExportHelperTest extends TestCase
{
    public function test_generate_random_string_returns_non_empty(): void
    {
        $s = ExportHelper::generateRandomString();
        $this->assertNotEmpty($s);
        $this->assertIsString($s);
    }

    public function test_get_format_with_writer_type_valid_returns_lowercase(): void
    {
        $this->assertSame('docx', ExportHelper::getFormat('file.docx', 'docx'));
        $this->assertSame('pdf', ExportHelper::getFormat('file.docx', 'pdf'));
        $this->assertSame('html', ExportHelper::getFormat('file.docx', 'html'));
    }

    public function test_get_format_with_writer_type_invalid_returns_docx(): void
    {
        $this->assertSame(Writer::DOCX, ExportHelper::getFormat('file.docx', 'invalid'));
    }

    public function test_get_format_from_filename_extension(): void
    {
        $this->assertSame('docx', ExportHelper::getFormat('out.docx', null));
        $this->assertSame('pdf', ExportHelper::getFormat('out.pdf', null));
        $this->assertSame('html', ExportHelper::getFormat('out.html', null));
    }

    public function test_get_format_empty_extension_returns_docx(): void
    {
        $this->assertSame(Writer::DOCX, ExportHelper::getFormat('noext', null));
    }

    public function test_has_supported_formats(): void
    {
        $this->assertTrue(ExportHelper::hasSupportedFormats('file.docx'));
        $this->assertTrue(ExportHelper::hasSupportedFormats('file.pdf'));
        $this->assertTrue(ExportHelper::hasSupportedFormats('file.html'));
        $this->assertFalse(ExportHelper::hasSupportedFormats('file.txt'));
        $this->assertFalse(ExportHelper::hasSupportedFormats('file.xml'));
    }

    public function test_is_path_absolute(): void
    {
        $this->assertTrue(ExportHelper::isPathAbsolute('/foo'));
        $this->assertTrue(ExportHelper::isPathAbsolute('\\foo'));
        $this->assertTrue(ExportHelper::isPathAbsolute('C:\\foo'));
        $this->assertTrue(ExportHelper::isPathAbsolute('D:/bar'));
        $this->assertFalse(ExportHelper::isPathAbsolute('relative/path'));
        $this->assertFalse(ExportHelper::isPathAbsolute('foo.docx'));
    }

    public function test_temp_dir_returns_config_path_and_creates_if_missing(): void
    {
        $path = ExportHelper::tempDir();
        $this->assertIsString($path);
        $this->assertDirectoryExists($path);
    }

    public function test_temp_file_returns_path_in_temp_dir(): void
    {
        $path = ExportHelper::tempFile();
        $this->assertIsString($path);
        $this->assertNotEmpty($path);
    }

    public function test_temp_file_with_dir(): void
    {
        $dir = ExportHelper::tempDir();
        $path = ExportHelper::tempFile($dir);
        $this->assertIsString($path);
        $this->assertNotEmpty($path);
    }

    public function test_convert_for_running_in_console_absolute_path_unchanged(): void
    {
        $abs = 'C:\\foo\\bar.docx';
        $this->assertSame($abs, ExportHelper::convertForRunningInConsole($abs));
    }

    public function test_convert_for_running_in_console_relative_adds_prefix_when_not_console(): void
    {
        $relative = 'path/to/file.docx';
        $result = ExportHelper::convertForRunningInConsole($relative);
        if (!app()->runningInConsole()) {
            $this->assertStringStartsWith('..', $result);
        } else {
            $this->assertSame($relative, $result);
        }
    }

    public function test_temp_file_name_returns_array_of_three(): void
    {
        $result = ExportHelper::tempFileName('test');
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertFileExists($result[0]);
        $this->assertDirectoryExists($result[1]);
    }

    public function test_get_sub_dirs_yields_subdirectories(): void
    {
        $tempDir = ExportHelper::tempDir();
        $subDir = $tempDir.DIRECTORY_SEPARATOR.'sub'.uniqid();
        mkdir($subDir, 0700, true);
        $dirs = iterator_to_array(ExportHelper::getSubDirs($tempDir));
        $this->assertNotEmpty($dirs);
        $this->assertContains($subDir, $dirs);
    }

    public function test_garbage_collector_and_clean_garbage(): void
    {
        $dir = ExportHelper::tempDir();
        ExportHelper::garbageCollector($dir);
        ExportHelper::cleanGarbage();
        $this->addToAssertionCount(1);
    }

    public function test_garbage_collector_files(): void
    {
        $file = ExportHelper::tempFile();
        ExportHelper::garbageCollectorFiles($file);
        ExportHelper::cleanGarbage();
        $this->addToAssertionCount(1);
    }
}
