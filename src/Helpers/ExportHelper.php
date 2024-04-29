<?php

namespace Santwer\Exporter\Helpers;

use Illuminate\Support\Str;
use Santwer\Exporter\Writer;
use Santwer\Exporter\Processor\PDFExporter;
use Santwer\Exporter\Exceptions\TempFolderException;

class ExportHelper
{
	public static function generateRandomString()
	{
		return uniqid();
	}

	/**
	 * @param  string       $fileName
	 * @param  string|null  $writerType
	 * @return string
	 * @throws \Exception
	 */
	public static function getFormat(
		string $fileName,
		string $writerType = null
	): string {

		if ($writerType) {
			if (!in_array(strtolower($writerType), Writer::formats())) {
				return Writer::DOCX;
			}

			return strtolower($writerType);
		}
		$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		if (empty($ext)) {
			return Writer::DOCX;
		}

		return self::getFormat($fileName, $ext);
	}

	/**
	 * @param  string  $prefix
	 * @return string
	 * @throws TempFolderException
	 */
	public static function tempFileName(string $prefix = ''): array
	{
		$tempDir = sys_get_temp_dir();

		$folderName = 'php_we_'.$prefix;
		$newTempDir = $tempDir.DIRECTORY_SEPARATOR.$folderName;
		if (!is_dir($newTempDir)) {
			if (!mkdir($newTempDir, 0700)) {
				throw new TempFolderException('Folder couldn\'t be created');
			}
		}

		return [tempnam($newTempDir, "php_we"), $newTempDir];
	}

	/**
	 * @param  string  $folder
	 * @return array
	 * @throws \Exception
	 */
	public static function processWordToPdf(string $folder) : array
	{
		PDFExporter::docxToPdf($folder.DIRECTORY_SEPARATOR.'*', $folder);
		$files = glob($folder .DIRECTORY_SEPARATOR. '*.pdf');
		return $files;
	}
}