<?php

declare(strict_types=1);

namespace Santwer\Exporter\Helpers;

use DirectoryIterator;
use Illuminate\Support\Str;
use Santwer\Exporter\Writer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Santwer\Exporter\Processor\PDFExporter;
use Santwer\Exporter\Processor\GlobalVariables;
use Santwer\Exporter\Exceptions\TempFolderException;

final class ExportHelper
{
	protected static int $subBatch = 0;
	protected static int $subBatchCalls = 0;

	/** @var array<string> */
	protected static array $garbage = [];

	/** @var array<string> */
	protected static array $garbageFiles = [];

	public static function resetBatchCounters(): void
	{
		self::$subBatch = 0;
		self::$subBatchCalls = 0;
	}

	public static function resetGarbage(): void
	{
		self::$garbage = [];
		self::$garbageFiles = [];
	}

	public static function generateRandomString(): string
	{
		return uniqid();
	}

	/**
	 * @throws \Exception
	 */
	public static function getFormat(string $fileName, ?string $writerType = null): string
	{
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

	public static function hasSupportedFormats(string $fileName): bool
	{
		$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		return in_array($ext, Writer::formats());
	}

	public static function tempFile(?string $dir = null): string
	{
		if (config('exporter.temp_folder_relative')) {
			$filename = 'php_we'.ExportHelper::generateRandomString().'.tmp';
			if ($dir) {
				return $dir.DIRECTORY_SEPARATOR.$filename;
			}
			return ExportHelper::tempDir().DIRECTORY_SEPARATOR.$filename;
		}
		if ($dir) {
			return tempnam($dir, "php_we");
		}
		return tempnam(ExportHelper::tempDir(), "php_we");
	}

	public static function isPathAbsolute(string $path): bool
	{
		return Str::startsWith($path, [
			'/', '\\',
			'C:', 'D:', 'E:', 'F:', 'G:', 'H:', 'I:', 'J:', 'K:', 'L:', 'M:',
			'N:', 'O:', 'P:', 'Q:', 'R:', 'S:', 'T:', 'U:', 'V:', 'W:', 'X:', 'Y:', 'Z:'
		]);
	}

	/**
	 * @throws TempFolderException
	 */
	public static function tempDir(): string
	{
		//create all folders in path if not exists
		$path = config('exporter.temp_folder');
		//explode String with DIRECTORY_SEPARATOR and / or \
		$pathParts = preg_split('/[\/\\\\]/', $path);
		$folderPath = '';

		foreach ($pathParts as $folder) {
			if (empty($folder)) {
				continue;
			}
			if (Str::contains($folder, ':')) {
				$folderPath = $folder;
			} elseif (empty($folderPath)) {
				$folderPath = $folder;
			} else {
				$folderPath = $folderPath.DIRECTORY_SEPARATOR.$folder;
			}

			if (!is_dir($folderPath)) {
				if (!mkdir($folderPath, 0700)) {
					throw new TempFolderException('Folder couldn\'t be created');
				}
			}
		}

		return $path;
	}

	public static function convertForRunningInConsole(string $path): string
	{
		if (self::isPathAbsolute($path)) {
			return $path;
		}
		if (Str::startsWith($path, '/')) {
			return $path;
		}
		if (app()->runningInConsole()) {
			return $path;
		}
		return '..'.DIRECTORY_SEPARATOR.$path;
	}

	/**
	 * @return array{0: string, 1: string, 2: string}
	 * @throws TempFolderException
	 */
	public static function tempFileName(string $prefix = ''): array
	{
		//Based on https://wiki.documentfoundation.org/Faq/General/150
		//The converter can not handle big chunks, therefore the batch size gets reduced to 200
		$tempDir = ExportHelper::tempDir();
		self::$subBatchCalls++;
		if (self::$subBatchCalls > config('exporter.batch_size', 200)) {
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

		return [tempnam($batchNameFolder, "php_we"), $newTempDir, $batchName];
	}

	/**
	 * @return array<string>
	 * @throws \Exception
	 */
	public static function processWordToPdf(string $folder): array
	{
		$files = [];
		foreach (self::getSubDirs($folder) as $dir) {
			if (is_string($dir)) {
				$files = array_merge($files, self::processWordToPdfFolder($dir));
			} else {
				$files = array_merge($files, self::processWordToPdfFolder($folder.DIRECTORY_SEPARATOR.$dir->getFilename()));
			}
		}
		self::garbageCollector($folder);
		return $files;
	}

	/**
	 * @return array<string>
	 */
	public static function processWordToPdfFolder(string $subfolder): array
	{
		PDFExporter::docxToPdf($subfolder.DIRECTORY_SEPARATOR.'*', $subfolder);
		$subFiles = glob($subfolder.DIRECTORY_SEPARATOR.'*.pdf');
		if (false !== $subFiles) {
			return $subFiles;
		}
		return [];
	}

	/**
	 * @return \Generator<string>
	 */
	public static function getSubDirs(string $folder): \Generator
	{
		$dirs = new DirectoryIterator($folder);
		foreach ($dirs as $dir) {
			if ($dir->isDot()) {
				continue;
			}
			if ($dir->isDir()) {
				$subfolder = $folder.DIRECTORY_SEPARATOR.$dir->getFilename();
				yield $subfolder;
			}
		}
	}

	public static function garbageCollector(string $folder): void
	{
		self::$garbage[] = $folder;
	}

	public static function garbageCollectorFiles(string $file): void
	{
		self::$garbage[] = $file;
	}

	public static function cleanGarbage(): void
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