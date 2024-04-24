<?php

namespace Santwer\Exporter\Processor;

use Santwer\Exporter\Writer;
use Illuminate\Support\Facades\Storage;
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

		$format = $this->getFormat($fileName, $writerType);


		$this->exporter
			->processFile($export)
			->getProcessedConvertedFile($format, $tmpfname);
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
		$format = $this->getFormat($filePath, $writerType);
		$tmpfname = tempnam(sys_get_temp_dir(), "php_we");
		$this->exporter
			->processFile($export)
			->getProcessedConvertedFile($format, $tmpfname);


		return Storage::disk($disk)
			->putFileAs($filePath, $tmpfname,
				$diskOptions);
	}

	/**
	 * @param  string       $fileName
	 * @param  string|null  $writerType
	 * @return string
	 * @throws \Exception
	 */
	private function getFormat(
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

		return $this->getFormat($fileName, $ext);
	}
}