<?php

namespace Santwer\Exporter\Tests\Unit\Exceptions;

use Santwer\Exporter\Exceptions\PDFConversionException;
use Santwer\Exporter\Tests\TestCase;

class PDFConversionExceptionTest extends TestCase
{
	public function test_from_process_creates_exception_with_output_and_error(): void
	{
		$e = PDFConversionException::fromProcess('output text', 'error text');
		$this->assertStringContainsString('error text', $e->getMessage());
		$this->assertStringContainsString('output text', $e->getMessage());
		$this->assertStringContainsString('PDF conversion failed', $e->getMessage());
	}

	public function test_can_be_constructed_directly(): void
	{
		$e = new PDFConversionException('Direct message', 123);
		$this->assertSame('Direct message', $e->getMessage());
		$this->assertSame(123, $e->getCode());
	}
}
