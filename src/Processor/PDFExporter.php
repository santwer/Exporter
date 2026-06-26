<?php

declare(strict_types=1);

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;
use Santwer\Exporter\Exceptions\PDFConversionException;
use Santwer\Exporter\Helpers\ExportHelper;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class PDFExporter
{
	/**
	 * @throws PDFConversionException
	 */
	public static function html2Pdf(string $html, ?string $path = null): string
	{
		$htmlfile = ExportHelper::tempFile();
		file_put_contents($htmlfile, $html);

		$outDir = $path ?? pathinfo($htmlfile, PATHINFO_DIRNAME);

		$command = [
			ExportHelper::sofficeBinary(),
			'--convert-to',
			'pdf',
			'--outdir',
			$outDir,
			$htmlfile,
			'--headless',
		];

		$process = new Process($command);
		$process->setTimeout(120);
		$process->run();

		if (!$process->isSuccessful()) {
			throw PDFConversionException::fromProcess(
				$process->getOutput(),
				$process->getErrorOutput()
			);
		}

		$file = Str::replace('.tmp', '.pdf', $htmlfile);
		return ExportHelper::convertForRunningInConsole($file);
	}

	/**
	 * @throws PDFConversionException
	 */
	public static function docxToPdf(string $docX, ?string $path = null): string
	{
		$outDir = $path ?? pathinfo($docX, PATHINFO_DIRNAME);

		$command = [
			ExportHelper::sofficeBinary(),
			'--convert-to',
			'pdf',
			'--outdir',
			$outDir,
			$docX,
			'--headless',
		];

		$process = new Process($command);
		$process->setTimeout(120);
		$process->run();

		if (!$process->isSuccessful()) {
			throw PDFConversionException::fromDocxConversion(
				$docX,
				$process->getOutput(),
				$process->getErrorOutput()
			);
		}

		$fileext = pathinfo($docX, PATHINFO_EXTENSION);
		if (empty($fileext)) {
			$file = $docX.'.pdf';
		} else {
			$file = Str::replace('.'.$fileext, '.pdf', $docX);
		}

		return ExportHelper::convertForRunningInConsole($file);
	}
}