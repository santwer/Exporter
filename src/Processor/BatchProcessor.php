<?php

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;
use Santwer\Exporter\Writer;
use Illuminate\Support\Facades\Storage;
use Santwer\Exporter\Helpers\ExportHelper;

trait BatchProcessor
{
	protected string $batch = '';
	protected string $file = '';
	protected ?string $filePDF = null;
	protected string $format = '';
	public function process(WordTemplateExporter $exporter, string $batch) {
		$this->batch = $batch;
		$this->format = ExportHelper::getFormat($this->name, $this->writerType);
		[$tmpfname, $folder] = ExportHelper::tempFileName($batch);

		$this->file = $tmpfname;
		$this->filePDF = pathinfo($this->file, PATHINFO_FILENAME).'.pdf';

		$exporter
			->processFile($this->export)
			->getProcessedConvertedFile(Writer::DOCX, $tmpfname);

		if($this->format === Writer::PDF) {
			return $folder;
		}

		$putFileAs = Storage::disk($this->disk)
			->putFileAs($this->filePath, $tmpfname, $this->name,
				$this->diskOptions);
		if($putFileAs) {
			unlink($tmpfname);
		}
		return $putFileAs;

	}

	/**
	 * @param  array  $files
	 * @return false|string
	 */
	public function copyOwnFileOfArray(array $files)
	{
		foreach ($files as $file) {
			if(Str::contains($file, $this->filePDF)) {
				return Storage::disk($this->disk)
					->putFileAs($this->filePath, $file, $this->name,
						$this->diskOptions);
			}
		}
		return false;
	}
}