<?php

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;
use Santwer\Exporter\Writer;
use Illuminate\Support\Facades\Bus;
use Santwer\Exporter\Jobs\WordToPDF;
use Illuminate\Support\Facades\Storage;
use Santwer\Exporter\Helpers\ExportHelper;
use Santwer\Exporter\Jobs\WordProcessorJob;
use Illuminate\Foundation\Bus\PendingChain;
use Santwer\Exporter\Exportables\Exportable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportClassExporter
{
	protected WordTemplateExporter $exporter;

	public function __construct(WordTemplateExporter $exporter)
	{
		$this->exporter = $exporter;
	}

	/**
	 * @param  object       $export
	 * @param  string       $fileName
	 * @param  string|null  $writerType
	 * @param  array        $headers
	 * @return BinaryFileResponse
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	public function download(
		object $export,
		string $fileName,
		string $writerType = null,
		array  $headers = []
	): BinaryFileResponse {
		$tmpfname = ExportHelper::tempFile();

		$format = ExportHelper::getFormat($fileName, $writerType);


		$file = $this->exporter
			->processFile($export)
			->getProcessedConvertedFile($format, $tmpfname);

		if ($format === Writer::PDF) {
			$tmpfname = $file;
		}
		if ($format === Writer::PDF && !isset($headers['Content-Type'])) {
			$headers['Content-Type'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
		}

		return response()
			->download($tmpfname, $fileName, $headers);
	}

	/**
	 * @param  object       $export
	 * @param  string       $folderPath
	 * @param  string|null  $disk
	 * @param  string|null  $writerType
	 * @param  array        $diskOptions
	 * @return false|string
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	public function store(
		object $export,
		string $folderPath,
		string $disk = null,
		string $writerType = null,
		array  $diskOptions = []
	) {
		if(ExportHelper::hasSupportedFormats($folderPath)) {
			$fileName = pathinfo($folderPath, PATHINFO_FILENAME)
				.'.'.pathinfo($folderPath, PATHINFO_EXTENSION);
			return self::storeAs(
				$export,
				Str::replace($fileName, '', $folderPath),
				$fileName,
				$disk,
				$writerType,
				$diskOptions
			);
		}

		$format = ExportHelper::getFormat($folderPath, $writerType);

		$tmpfname = ExportHelper::tempFile();
		$file = $this->exporter
			->processFile($export)
			->getProcessedConvertedFile($format, $tmpfname);
		if ($format === Writer::PDF) {
			$tmpfname = $file;
		}

		return Storage::disk($disk)
			->putFile($folderPath, $tmpfname,
				$diskOptions);
	}

	public function storeAs(
		object $export,
		string $filePath,
		string $name,
		string $disk = null,
		string $writerType = null,
		array  $diskOptions = []
	) {
		$format = ExportHelper::getFormat($name, $writerType);
		$tmpfname = ExportHelper::tempFile();
		$file = $this->exporter
			->processFile($export)
			->getProcessedConvertedFile($format, $tmpfname);

		if ($format === Writer::PDF) {
			$tmpfname = $file;
		}

		return Storage::disk($disk)
			->putFileAs($filePath, $tmpfname, $name, $diskOptions);
	}

	/**
	 * @param  Exportable  ...$exports
	 * @return void
	 */
	public function batchStore(
		Exportable ...$exports
	): bool {
		$batch = ExportHelper::generateRandomString();
		$folder = null;
		foreach ($exports as $export) {
			$folder = $export->process(clone $this->exporter, $batch);
		}
		$files = ExportHelper::processWordToPdf($folder);
		foreach ($exports as $export) {
			$export->copyOwnFileOfArray($files);
		}
		ExportHelper::cleanGarbage();

		return true;
	}

	/**
	 * @param  Exportable  ...$exports
	 */
	public function batchQueue(Exportable ...$exports)
	{
		return array_map(fn($x) => $x->dispatch(), $this->batch(...$exports));
	}

	/**
	 * @param  Exportable  ...$exports
	 * @return ...PendingChain
	 */
	public function batch(Exportable ...$exports) : array
	{
		$batch = ExportHelper::generateRandomString();
		$hasPDF = false;
		$folder = null;
		$pending = [];
		$pdfDones = [];
		foreach ($exports as $export) {
			[$callableDone, $callablePDFDone] = $export->getClosures();
			$export->whenPDFDone(null);
			$export->whenDone(null);
			$export->preProcess($batch);
			$folder = $export->getFolder();
			$subBatch = $export->getSubBatch();
			if(!isset($pending[$subBatch])) {
				$pending[$subBatch] = [];
			}
			$pending[$subBatch][] = new WordProcessorJob($this->exporter, $export);

			if ($callableDone) {
				$pending[$subBatch][] = $callableDone;
			}
			if ($export->getFormat() === Writer::PDF) {
				$hasPDF = true;
				if(!isset($pdfDones[$subBatch])) {
					$pdfDones[$subBatch] = [];
				}
				$pdfDones[$subBatch][] = $callablePDFDone;
			}
		}
		if($hasPDF) {
			foreach (ExportHelper::getSubDirs($folder) as $directory) {
				//get current direcotory name
				$currentFolder = basename($directory);
				$pending[$currentFolder][] = function () use ($exports, $directory,$currentFolder) {
					$files = ExportHelper::processWordToPdfFolder($directory);
					foreach ($exports as $export) {
						$export->copyOwnFileOfArray($files, false);
					}
					ExportHelper::garbageCollector($directory);
					ExportHelper::cleanGarbage();
				};

				if(isset($pdfDones[$currentFolder])) {
					$pending[$currentFolder] = array_merge($pending[$currentFolder], $pdfDones[$currentFolder]);
				}
			}

		}

		return collect($pending)
			->filter()
			->map(fn(array $x) => Bus::chain(collect($x)->filter()->toArray()))
			->toArray();
	}


}