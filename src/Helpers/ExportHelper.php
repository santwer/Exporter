<?php

namespace Santwer\Exporter\Helpers;

use DirectoryIterator;
use Illuminate\Support\Str;
use Santwer\Exporter\Writer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Santwer\Exporter\Processor\PDFExporter;
use Santwer\Exporter\Processor\GlobalVariables;
use Santwer\Exporter\Exceptions\TempFolderException;

class ExportHelper
{
	protected static int $subBatch = 0;
	protected static int $subBatchCalls = 0;
	protected static array $garbage = [];
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
		//Based on https://wiki.documentfoundation.org/Faq/General/150
		//The converter can not handle big chunks, therefore the batch size gets reduced to 200
		$tempDir = sys_get_temp_dir();
		self::$subBatchCalls++;
		if(self::$subBatchCalls > GlobalVariables::config('batch_size', 200)) {
			self::$subBatch++;
			self::$subBatchCalls = 1;
		}

		$folderName = 'php_we_'.$prefix;
		$batchName = 'batch_'.self::$subBatch;
		$newTempDir = $tempDir.DIRECTORY_SEPARATOR.$folderName;
		$batchNameFolder = $newTempDir.DIRECTORY_SEPARATOR.$batchName;
		if (!is_dir($newTempDir)) {
			if (!mkdir($newTempDir, 0700)) {
				throw new TempFolderException('Folder couldn\'t be created');
			}
		}
		if (!is_dir($batchNameFolder)) {
			if (!mkdir($batchNameFolder, 0700)) {
				throw new TempFolderException('Folder couldn\'t be created');
			}
		}

		return [tempnam($batchNameFolder, "php_we"), $newTempDir];
	}

	/**
	 * @param  string  $folder
	 * @return array
	 * @throws \Exception
	 */
	public static function processWordToPdf(string $folder) : array
	{
		$dirs = new DirectoryIterator($folder);
		$files = [];
		foreach ($dirs as $dir) {
			if($dir->isDot()) continue;
			if ($dir->isDir()) {
				$subfolder = $folder.DIRECTORY_SEPARATOR.$dir->getFilename();
				PDFExporter::docxToPdf($subfolder.DIRECTORY_SEPARATOR.'*', $subfolder);
				$subFiles = glob($subfolder .DIRECTORY_SEPARATOR. '*.pdf');
				if(false !== $subFiles) {
					$files = array_merge($files, $subFiles);
				}
			}
		}
		self::garbageCollector($folder);
		return $files;
	}

	public static function garbageCollector(string $folder)
	{
		self::$garbage[] = $folder;
	}

	public static function cleanGarbage()
	{
		foreach (self::$garbage as $folder) {
			try {
				//try to delete to save disk space
				File::deleteDirectory($folder);

			} catch (\Exception $exception) {
				Log::error($exception->getMessage());
				//folder could not be deleted, not a throwable error since its temp folder
			}
		}
	}
}