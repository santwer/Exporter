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
	protected $callableDone = null;
	protected $callablePDFDone = null;
	public function process(WordTemplateExporter $exporter, string $batch,bool $returnFile = false)
	{
		$this->batch = $batch;
		$this->format = ExportHelper::getFormat($this->name, $this->writerType);
		[$tmpfname, $folder] = ExportHelper::tempFileName($batch);

		$this->file = $tmpfname;
		$this->filePDF = pathinfo($this->file, PATHINFO_FILENAME).'.pdf';

		$exporter
			->processFile($this->export)
			->getProcessedConvertedFile(Writer::DOCX, $tmpfname);

		if($this->format === Writer::PDF) {
			$this->callDone(Writer::PDF);
			if($returnFile) {
				return $tmpfname;
			}
			return $folder;
		}

		$putFileAs = Storage::disk($this->disk)
			->putFileAs($this->filePath, $tmpfname, $this->name,
				$this->diskOptions);
		if($putFileAs) {
			unlink($tmpfname);
		}
		$this->callDone($putFileAs);
		return $putFileAs;

	}

	public function getFormat() : string
	{
		return empty($this->format) ?
			ExportHelper::getFormat($this->name, $this->writerType)
			: $this->format;
	}

	private function callDone($putFileAs)
	{
		if(!is_callable($this->callableDone)) {
			return;
		}
		call_user_func($this->callableDone, $putFileAs);
	}

	private function callPDFDone($putFileAs)
	{
		if(!is_callable($this->callablePDFDone)) {
			return;
		}
		call_user_func($this->callablePDFDone, $putFileAs);
	}

	/**
	 * Gets fired when the Templating is Done. If the Process needs to use PDF on a Batch, it will be fired before the PDF convert
	 * @param  callable  $callable
	 * @return void
	 */
	public function whenDone(callable $callable)
	{
		$this->callableDone = $callable;
	}

	/**
	 *
	 * @param  callable  $callable
	 * @return void
	 */
	public function whenPDFDone(callable $callable)
	{
		$this->callablePDFDone = $callable;
	}

	/**
	 * @param  array  $files
	 * @return false|string
	 */
	public function copyOwnFileOfArray(array $files)
	{
		foreach ($files as $file) {
			if(Str::contains($file, $this->filePDF)) {
				$putFileAs = Storage::disk($this->disk)
					->putFileAs($this->filePath, $file, $this->name,
						$this->diskOptions);
				$this->callPDFDone($putFileAs);
				return $putFileAs;
			}
		}
		return false;
	}
}