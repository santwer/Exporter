<?php

namespace Santwer\Exporter\Tests\Unit\Processor;

use Illuminate\Support\Str;
use Mockery;
use Santwer\Exporter\Exceptions\PDFConversionException;
use Santwer\Exporter\Helpers\ExportHelper;
use Santwer\Exporter\Processor\PDFExporter;
use Santwer\Exporter\Tests\TestCase;
use Symfony\Component\Process\Process;

class PDFExporterTest extends TestCase
{
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

	public function test_build_command_uses_isolated_user_installation(): void
	{
		$command = PDFExporter::buildCommand(['/data/a.docx', '/data/b.docx'], '/data', 'pdf', '/tmp/lo_profile_abc');

		$this->assertContains('-env:UserInstallation=file:///tmp/lo_profile_abc', $command);
		$this->assertContains('--headless', $command);
		$this->assertSame(['--outdir', '/data', '/data/a.docx', '/data/b.docx'], array_slice($command, -4));
	}

	public function test_build_command_without_profile_has_no_user_installation(): void
	{
		$command = PDFExporter::buildCommand(['/data/a.docx'], '/data');

		$this->assertEmpty(array_filter($command, fn ($arg) => str_contains($arg, 'UserInstallation')));
	}

	public function test_path_to_file_uri(): void
	{
		$this->assertSame('file:///tmp/lo_profile_1', ExportHelper::pathToFileUri('/tmp/lo_profile_1'));
		$this->assertSame('file:///var/my%20temp/p', ExportHelper::pathToFileUri('/var/my temp/p'));
		$this->assertSame('file:///C:/Users/Max%20M/Temp/p', ExportHelper::pathToFileUri('C:\\Users\\Max M\\Temp\\p'));
	}

	public function test_missing_pdf_throws_even_if_soffice_exits_successfully(): void
	{
		// simulates soffice handing the job to another running instance: exit 0, no PDF
		config()->set('exporter.word2pdf.command', escapeshellarg(PHP_BINARY).' -r "echo implode(chr(32), \$argv);" ? ?');
		$docxPath = $this->createMinimalDocx();

		try {
			PDFExporter::docxToPdf($docxPath, pathinfo($docxPath, PATHINFO_DIRNAME));
			$this->fail('PDFConversionException expected');
		} catch (PDFConversionException $exception) {
			$this->assertStringContainsString('Expected PDF was not created', $exception->sofficeError);
			// the custom command template gets its own profile as well
			$this->assertStringContainsString('-env:UserInstallation=file://', $exception->sofficeOutput);
		}
	}

	public function test_profile_directories_are_removed_after_conversion(): void
	{
		config()->set('exporter.word2pdf.command', escapeshellarg(PHP_BINARY).' -r "exit(0);" ? ?');
		$docxPath = $this->createMinimalDocx();

		for ($i = 0; $i < 2; $i++) {
			try {
				PDFExporter::docxToPdf($docxPath);
			} catch (PDFConversionException) {
			}
		}

		$this->assertSame([], glob(ExportHelper::tempDir().DIRECTORY_SEPARATOR.'lo_profile_*'));
	}

	public function test_parallel_conversions_with_isolated_profiles_all_produce_pdfs(): void
	{
		if (!$this->sofOfficeAvailable()) {
			$this->markTestSkipped('LibreOffice not available');
		}

		$dir = ExportHelper::tempDir().DIRECTORY_SEPARATOR.'parallel_'.uniqid();
		mkdir($dir, 0700, true);

		$processes = [];
		for ($i = 0; $i < 4; $i++) {
			$docx = $dir.DIRECTORY_SEPARATOR.'doc'.$i.'.docx';
			copy($this->createMinimalDocx('Dokument '.$i), $docx);
			$profile = $dir.DIRECTORY_SEPARATOR.'lo_profile_'.$i;
			$process = new Process(PDFExporter::buildCommand([$docx], $dir, 'pdf', $profile));
			$process->setTimeout(120);
			$process->start();
			$processes[$i] = $process;
		}

		foreach ($processes as $i => $process) {
			$process->wait();
			$pdf = $dir.DIRECTORY_SEPARATOR.'doc'.$i.'.pdf';
			$this->assertFileExists($pdf, $process->getErrorOutput());
			$this->assertGreaterThan(0, filesize($pdf));
		}
	}

	public function test_process_word_to_pdf_converts_all_batch_files(): void
	{
		if (!$this->sofOfficeAvailable()) {
			$this->markTestSkipped('LibreOffice not available');
		}

		$files = [];
		for ($i = 0; $i < 3; $i++) {
			[$file, $folder] = ExportHelper::tempFileName('batchtest');
			copy($this->createMinimalDocx('Batch '.$i), $file);
			$files[] = $file;
		}

		$pdfs = ExportHelper::processWordToPdf($folder);

		$this->assertCount(3, $pdfs);
		foreach ($files as $file) {
			$this->assertFileExists(Str::replace('.tmp', '.pdf', $file));
		}
	}

	private function sofOfficeAvailable(): bool
	{
		return ExportHelper::sofficeIsAvailable();
	}
}
