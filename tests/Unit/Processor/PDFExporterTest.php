<?php

namespace Santwer\Exporter\Tests\Unit\Processor;

use Mockery;
use Santwer\Exporter\Exceptions\PDFConversionException;
use Santwer\Exporter\Helpers\ExportHelper;
use Santwer\Exporter\Processor\PDFExporter;
use Santwer\Exporter\Tests\TestCase;
use Symfony\Component\Process\Process;

class PDFExporterTest extends TestCase
{
	public function test_docx_to_pdf_creates_process_with_correct_command(): void
	{
		$this->markTestSkipped('Requires soffice binary or mock - integration test');
	}

	public function test_docx_to_pdf_returns_pdf_path_on_success(): void
	{
		$docxPath = $this->createMinimalDocx();
		$dir = pathinfo($docxPath, PATHINFO_DIRNAME);

		if (!$this->sofOfficeAvailable()) {
			$this->markTestSkipped('LibreOffice not available');
		}

		$result = PDFExporter::docxToPdf($docxPath, $dir);
		$this->assertIsString($result);
		$this->assertStringEndsWith('.pdf', $result);
	}

	public function test_html_to_pdf_creates_temp_html_file(): void
	{
		if (!$this->sofOfficeAvailable()) {
			$this->markTestSkipped('LibreOffice not available');
		}

		$html = '<html><body>Test</body></html>';
		$result = PDFExporter::html2Pdf($html);
		$this->assertIsString($result);
		$this->assertStringEndsWith('.pdf', $result);
	}

	private function sofOfficeAvailable(): bool
	{
		return ExportHelper::sofficeIsAvailable();
	}
}
