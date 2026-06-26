<?php

namespace Santwer\Exporter\Tests\Unit\Helpers;

use Santwer\Exporter\Exceptions\PDFConversionException;
use Santwer\Exporter\Helpers\DocxXmlDiagnostics;
use Santwer\Exporter\Tests\TestCase;

class DocxXmlDiagnosticsTest extends TestCase
{
	public function test_detects_unescaped_xml_in_broken_docx(): void
	{
		$path = $this->createBrokenDocx();

		$findings = DocxXmlDiagnostics::analyze($path);

		$this->assertNotEmpty($findings);

		$report = DocxXmlDiagnostics::formatReport($findings);

		$this->assertStringContainsString('DOCX XML analysis', $report);
		$this->assertStringContainsString('unclosed-tag', $report);
	}

	private function createBrokenDocx(): string
	{
		$temp = $this->createMinimalDocx('${broken}');

		$zip = new \ZipArchive();
		$zip->open($temp);
		$xml = $zip->getFromName('word/document.xml');
		$zip->close();

		$broken = str_replace(
			'${broken}',
			'Broken: & Co. <unclosed-tag "Quotes"',
			(string) $xml
		);

		$zip = new \ZipArchive();
		$zip->open($temp);
		$zip->deleteName('word/document.xml');
		$zip->addFromString('word/document.xml', $broken);
		$zip->close();

		return $temp;
	}

	public function test_pdf_conversion_exception_includes_docx_analysis(): void
	{
		$exception = PDFConversionException::fromDocxConversion(
			__FILE__,
			'',
			'LibreOffice failed'
		);

		$this->assertSame(__FILE__, $exception->docxPath);
		$this->assertStringContainsString('DOCX XML analysis', $exception->getMessage());
	}
}
