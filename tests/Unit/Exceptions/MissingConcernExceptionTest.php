<?php

namespace Santwer\Exporter\Tests\Unit\Exceptions;

use Santwer\Exporter\Exceptions\MissingConcernException;
use Santwer\Exporter\Tests\TestCase;

class MissingConcernExceptionTest extends TestCase
{
    public function test_construct_with_null_uses_default_message(): void
    {
        $e = new MissingConcernException(null);
        $this->assertSame(0, $e->getCode());
        $this->assertNull($e->getPrevious());
        $this->assertStringContainsString('Missing concerns', $e->getMessage());
    }

    public function test_construct_with_array_includes_concern_names_in_message(): void
    {
        $e = new MissingConcernException(['ConcernA', 'ConcernB']);
        $this->assertSame(0, $e->getCode());
        $this->assertStringContainsString('ConcernA', $e->getMessage());
        $this->assertStringContainsString('ConcernB', $e->getMessage());
    }

    public function test_construct_with_single_concern(): void
    {
        $e = new MissingConcernException(['FromWordTemplate']);
        $this->assertStringContainsString('FromWordTemplate', $e->getMessage());
    }
}
