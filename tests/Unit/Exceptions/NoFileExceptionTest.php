<?php

namespace Santwer\Exporter\Tests\Unit\Exceptions;

use Santwer\Exporter\Exceptions\NoFileException;
use Santwer\Exporter\Tests\TestCase;

class NoFileExceptionTest extends TestCase
{
    public function test_construct_with_null_uses_default_message(): void
    {
        $e = new NoFileException(null);
        $this->assertSame(0, $e->getCode());
        $this->assertNull($e->getPrevious());
        $this->assertStringContainsString('No File', $e->getMessage());
    }

    public function test_construct_with_path_includes_path_in_message(): void
    {
        $path = '/some/missing/file.docx';
        $e = new NoFileException($path);
        $this->assertSame(0, $e->getCode());
        $this->assertStringContainsString($path, $e->getMessage());
    }

    public function test_construct_with_empty_string(): void
    {
        $e = new NoFileException('');
        $this->assertStringContainsString('FilePath', $e->getMessage());
    }
}
