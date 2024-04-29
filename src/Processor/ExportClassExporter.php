<?php

namespace Santwer\Exporter\Processor;

use Santwer\Exporter\Writer;
use Illuminate\Support\Facades\Storage;
use Santwer\Exporter\Helpers\ExportHelper;
use Santwer\Exporter\Exportables\Exportable;
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
		$tmpfname = tempnam(sys_get_temp_dir(), "php_we");

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
	 * @param  string       $filePath
	 * @param  string|null  $disk
	 * @param  string|null  $writerType
	 * @param  array        $diskOptions
	 * @return false|string
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	public function store(
		object $export,
		string $filePath,
		string $disk = null,
		string $writerType = null,
		array  $diskOptions = []
	) {
		$format = ExportHelper::getFormat($filePath, $writerType);
		$tmpfname = tempnam(sys_get_temp_dir(), "php_we");
		$file = $this->exporter
			->processFile($export)
			->getProcessedConvertedFile($format, $tmpfname);
		if ($format === Writer::PDF) {
			$tmpfname = $file;
		}

		return Storage::disk($disk)
			->putFile($filePath, $tmpfname,
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
		$tmpfname = tempnam(sys_get_temp_dir(), "php_we");
		$file = $this->exporter
			->processFile($export)
			->getProcessedConvertedFile($format, $tmpfname);
		if ($format === Writer::PDF) {
			$tmpfname = $file;
		}

		return Storage::disk($disk)
			->putFileAs($filePath, $tmpfname, $name,
				$diskOptions);
	}

	/**
	 * @param  Exportable  ...$exports
	 * @return void
	 */
	public function batchStore(
		Exportable ...$exports
	): bool
	{
		$batch = ExportHelper::generateRandomString();
		$folder = null;
		foreach ($exports as $export) {
			$folder = $export->process(clone $this->exporter, $batch);
		}
		$files = ExportHelper::processWordToPdf($folder);
		foreach ($exports as $export) {
			$export->copyOwnFileOfArray($files);
		}
		return true;
	}


}