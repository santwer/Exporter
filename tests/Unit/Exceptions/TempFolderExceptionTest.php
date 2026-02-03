<?php

namespace Santwer\Exporter\Tests\Unit\Exceptions;

use Exception;
use Santwer\Exporter\Exceptions\TempFolderException;
use Santwer\Exporter\Tests\TestCase;

class TempFolderExceptionTest extends TestCase
{
    public function test_construct_with_message_and_code(): void
    {
        $e = new TempFolderException('Folder could not be created', 500);
        $this->assertSame('Folder could not be created', $e->getMessage());
        $this->assertSame(500, $e->getCode());
        $this->assertNull($e->getPrevious());
    }

    public function test_construct_with_previous(): void
    {
        $previous = new Exception('Previous error');
        $e = new TempFolderException('Wrapper', 0, $previous);
        $this->assertSame('Wrapper', $e->getMessage());
        $this->assertSame(0, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }
}
