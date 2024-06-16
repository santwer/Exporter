<?php

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Process;
use Santwer\Exporter\Helpers\ExportHelper;

class PDFExporter
{
	protected static $outPutFile = '';


	public static function html2Pdf(string $html, ?string $path = null)
	{
		$htmlfile = ExportHelper::tempFile();

		$handler = fopen($htmlfile, "w");
		fwrite($handler, $html);
		fclose($handler);

		if ($path !== null) {
			$return = exec(self::getCommand('html2pdfPath', $path, $htmlfile));
		} else {
			$return = exec(self::getCommand('html2pdf', $htmlfile));
		}
		if (!self::checkReturnValue($return)) {
			throw new \Exception($return);
		}

		return self::$outPutFile;
	}

	/**
	 * @param $docX
	 * @param $path
	 * @throws \Exception
	 */
	public static function docxToPdf($docX, $path = null)
	{

		$return = shell_exec(self::getCommand('docx2pdfPath', $path, $docX));
		if(!self::checkReturnValue($return)) {
			throw new \Exception($return);
		}

		if ($path !== null) {
			//get file extension
			$fileext = pathinfo($docX, PATHINFO_EXTENSION);
			if (empty($fileext)) {
				$file = $docX.'.pdf';
			} else {
				$file = Str::replace('.'.$fileext, '.pdf', $docX);
			}
			return  ExportHelper::convertForRunningInConsole($file);
		}
		$file = $path.pathinfo($docX, PATHINFO_FILENAME).'.pdf';
		return ExportHelper::convertForRunningInConsole($file);
	}

	private static function cmdToString(array $array) : string
	{
		return implode(' ', array_map(function ($item) {
			if(Str::contains($item, ' ') && !Str::startsWith($item, '"')) {
				return '"'.$item.'"';
			} else
			return trim($item);
		},$array));
	}

	/**
	 * @param         $type
	 * @param  mixed  ...$args
	 * @return string
	 */
	private static function getCommand($type, ...$args): string
	{

		$collection = collect(explode(' ', self::commands($type)));
		$partIndex = 0;

		foreach ($collection as $index => $commandPart) {
			if($index === 0 && !empty(config('exporter.word2pdf.soffice_prefix'))) {
				$collection[$index] = config('exporter.word2pdf.soffice_prefix').$commandPart;
			}

			if (Str::contains($commandPart, ['?','%s', '"%s"'])) {
				if (isset($args[$partIndex])) {
					$collection[$index] = Str::replace(['?','%s', '"%s"'], $args[$partIndex], $collection[$index]);
				}
				$partIndex++;
			}
		}

		return self::cmdToString($collection
			->toArray());
	}


	/**
	 * @param $type
	 * @return string
	 */
	private static function commands($type)
	{
		switch ($type) {
			case 'docx2pdfPath':
			case 'html2pdfPath':
				return config('exporter.word2pdf.command');
			default:
				return $type;
		}
	}


	private static function checkReturnValue($value)
	{
		$checkConvert = explode(' ', $value);
		if (isset($checkConvert[3])) {
			self::$outPutFile = $checkConvert[3];
		}
		if ($checkConvert[0] === 'convert') {
			return true;
		} else {
			return false;
		}
	}
}