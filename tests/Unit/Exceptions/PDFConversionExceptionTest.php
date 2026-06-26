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
		$this->assertNull($e->docxPath);
		$this->assertSame([], $e->docxFindings);
	}

	public function test_can_be_constructed_directly(): void
	{
		$e = new PDFConversionException('Direct message', 123);
		$this->assertSame('Direct message', $e->getMessage());
		$this->assertSame(123, $e->getCode());
	}

	public function test_context_includes_structured_findings(): void
	{
		$e = new PDFConversionException(
			message: 'report',
			docxPath: '/tmp/test.docx',
			docxFindings: [[
				'part' => 'word/document.xml',
				'type' => 'suspicious_text',
				'message' => 'Suspicious text',
				'text' => '<broken>',
			]],
			sofficeError: 'convert failed',
		);

		$context = $e->context();

		$this->assertSame('/tmp/test.docx', $context['docx_path']);
		$this->assertSame(1, $context['finding_count']);
		$this->assertSame('convert failed', $context['soffice_error']);
	}

	public function test_exception_message_includes_docx_analysis_summary(): void
	{
		$e = new PDFConversionException(
			message: 'PDF conversion failed'."\n\n".'2 DOCX XML issues detected — likely invalid placeholder content.'."\n\n".'DOCX XML analysis (likely cause of the failed PDF export):'."\n".'1) [word/document.xml] Suspicious text',
			docxFindings: [
				['part' => 'word/document.xml', 'type' => 'suspicious_text', 'message' => 'Suspicious text', 'text' => '<broken>'],
				['part' => 'word/document.xml', 'type' => 'parse_error', 'message' => 'Tag mismatch'],
			],
		);

		$this->assertStringContainsString('PDF conversion failed', $e->getMessage());
		$this->assertStringContainsString('2 DOCX XML issues detected', $e->getMessage());
	}

	public function test_format_for_log_is_multiline_and_readable(): void
	{
		$e = new PDFConversionException(
			message: 'ignored',
			docxPath: '/tmp/test.docx',
			docxFindings: [[
				'part' => 'word/document.xml',
				'type' => 'suspicious_text',
				'message' => 'Suspicious text',
				'text' => '<broken>',
			]],
			sofficeError: 'LibreOffice exit 1',
		);

		$reflection = new \ReflectionMethod($e, 'formatForLog');
		$log = $reflection->invoke($e);

		$this->assertStringContainsString("PDF conversion failed\n", $log);
		$this->assertStringContainsString('DOCX file: /tmp/test.docx', $log);
		$this->assertStringContainsString('--- Issue 1: word/document.xml ---', $log);
		$this->assertStringContainsString('Suspicious content: <broken>', $log);
		$this->assertStringContainsString('LibreOffice error: LibreOffice exit 1', $log);
	}
}
