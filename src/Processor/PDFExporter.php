<?php

declare(strict_types=1);

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Santwer\Exporter\Exceptions\PDFConversionException;
use Santwer\Exporter\Helpers\ExportHelper;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final class PDFExporter
{
	public const DEFAULT_COMMAND = 'soffice --convert-to pdf --outdir ? ? --headless';

	/**
	 * @throws PDFConversionException
	 */
	public static function html2Pdf(string $html, ?string $path = null): string
	{
		$htmlfile = ExportHelper::tempFile();
		file_put_contents($htmlfile, $html);

		$outDir = $path ?? pathinfo($htmlfile, PATHINFO_DIRNAME);

		[$output, $error, $exitCode] = self::convert([$htmlfile], $outDir);

		$file = self::expectedOutputFile($htmlfile, $outDir);
		if ($exitCode !== 0 || ! self::isValidOutput($file)) {
			throw PDFConversionException::fromProcess(
				$output,
				self::withMissingOutputHint($error, $exitCode, [$file])
			);
		}

		return ExportHelper::convertForRunningInConsole($file);
	}

	/**
	 * @throws PDFConversionException
	 */
	public static function docxToPdf(string $docX, ?string $path = null): string
	{
		$outDir = $path ?? pathinfo($docX, PATHINFO_DIRNAME);

		return self::docxFilesToPdf([$docX], $outDir)[0];
	}

	/**
	 * Converts several documents with a single LibreOffice call.
	 *
	 * @param  array<string>  $files
	 * @return array<string> generated PDF paths, in the order of $files
	 * @throws PDFConversionException
	 */
	public static function docxFilesToPdf(array $files, string $outDir): array
	{
		if ($files === []) {
			return [];
		}

		[$output, $error, $exitCode] = self::convert($files, $outDir);

		$pdfs = [];
		$missing = [];
		foreach ($files as $file) {
			$pdf = self::expectedOutputFile($file, $outDir);
			if (! self::isValidOutput($pdf)) {
				$missing[$file] = $pdf;
			}
			$pdfs[] = ExportHelper::convertForRunningInConsole($pdf);
		}

		if ($exitCode !== 0 || $missing !== []) {
			throw PDFConversionException::fromDocxConversion(
				(string) (array_key_first($missing) ?? $files[0]),
				$output,
				self::withMissingOutputHint($error, $exitCode, array_values($missing))
			);
		}

		return $pdfs;
	}

	/**
	 * Runs LibreOffice with its own user profile, so parallel conversions
	 * (FPM workers, queue workers) do not hand their jobs to each other.
	 *
	 * @param  array<string>  $files
	 * @return array{0: string, 1: string, 2: int} stdout, stderr, exit code
	 */
	public static function convert(array $files, string $outDir, string $filter = 'pdf'): array
	{
		$profileDir = self::isolateInstances() ? self::createProfileDir() : null;

		try {
			$template = (string) config('exporter.word2pdf.command', self::DEFAULT_COMMAND);

			if ($template !== self::DEFAULT_COMMAND) {
				return self::runCommandTemplate($template, $files, $outDir, $profileDir);
			}

			$process = new Process(self::buildCommand($files, $outDir, $filter, $profileDir));
			$process->setTimeout(self::timeout(count($files)));

			try {
				$process->run();
			} catch (ProcessTimedOutException $exception) {
				return [$process->getOutput(), $exception->getMessage(), 1];
			}

			return [$process->getOutput(), $process->getErrorOutput(), (int) $process->getExitCode()];
		} finally {
			if ($profileDir !== null) {
				File::deleteDirectory($profileDir);
			}
		}
	}

	/**
	 * @param  array<string>  $files
	 * @return list<string>
	 */
	public static function buildCommand(array $files, string $outDir, string $filter = 'pdf', ?string $profileDir = null): array
	{
		$command = [ExportHelper::sofficeBinary()];

		if ($profileDir !== null) {
			$command[] = '-env:UserInstallation='.ExportHelper::pathToFileUri($profileDir);
		}

		return array_values([
			...$command,
			'--headless',
			'--norestore',
			'--nologo',
			'--nodefault',
			'--nofirststartwizard',
			'--nolockcheck',
			'--convert-to',
			$filter,
			'--outdir',
			$outDir,
			...$files,
		]);
	}

	/**
	 * Legacy path for a custom SOFFICE_COMMAND_PATH template.
	 *
	 * @param  array<string>  $files
	 * @return array{0: string, 1: string, 2: int}
	 */
	private static function runCommandTemplate(string $template, array $files, string $outDir, ?string $profileDir): array
	{
		$command = Str::replaceArray('?', [
			escapeshellarg($outDir),
			implode(' ', array_map('escapeshellarg', $files)),
		], $template);

		if ($profileDir !== null && ! Str::contains($command, 'UserInstallation')) {
			$command .= ' '.escapeshellarg('-env:UserInstallation='.ExportHelper::pathToFileUri($profileDir));
		}

		exec($command.' 2>&1', $outputLines, $exitCode);

		return [implode("\n", $outputLines), '', (int) $exitCode];
	}

	private static function createProfileDir(): string
	{
		$dir = ExportHelper::tempDir().DIRECTORY_SEPARATOR.'lo_profile_'.bin2hex(random_bytes(8));
		if (! mkdir($dir, 0700, true) && ! is_dir($dir)) {
			throw new \RuntimeException('Could not create LibreOffice profile directory '.$dir);
		}

		return realpath($dir) ?: $dir;
	}

	private static function isolateInstances(): bool
	{
		return (bool) config('exporter.word2pdf.isolate_instances', true);
	}

	private static function timeout(int $fileCount): ?float
	{
		$timeout = config('exporter.word2pdf.timeout', 120);
		if ($timeout === null || (float) $timeout <= 0) {
			return null;
		}

		return (float) $timeout * max(1, $fileCount);
	}

	private static function expectedOutputFile(string $file, string $outDir): string
	{
		return rtrim($outDir, '\\/').DIRECTORY_SEPARATOR.pathinfo($file, PATHINFO_FILENAME).'.pdf';
	}

	private static function isValidOutput(string $file): bool
	{
		$file = ExportHelper::convertForRunningInConsole($file);
		clearstatcache(true, $file);

		return is_file($file) && filesize($file) > 0;
	}

	/**
	 * @param  array<string>  $missing
	 */
	private static function withMissingOutputHint(string $error, int $exitCode, array $missing): string
	{
		$lines = array_filter([trim($error)]);
		if ($exitCode !== 0) {
			$lines[] = "LibreOffice exited with code {$exitCode}.";
		}
		foreach ($missing as $file) {
			$lines[] = "Expected PDF was not created: {$file}";
		}

		return implode("\n", $lines);
	}
}
