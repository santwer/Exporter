<?php

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;
use Santwer\Exporter\Writer;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Storage;
use Santwer\Exporter\Helpers\ExportHelper;
use Illuminate\Foundation\Events\Dispatchable;


trait BatchProcessor
{
	protected string $batch = '';
	protected string $file = '';
	protected ?string $filePDF = null;
	protected string $format = '';
	protected string $folder = '';
	protected string $subBatch = '';
	protected $callableDone = null;
	protected $callablePDFDone = null;

	public function preProcess(string $batch)
	{
		$this->batch = $batch;
		$this->format = ExportHelper::getFormat($this->name, $this->writerType);
		[$this->file, $this->folder, $this->subBatch] = ExportHelper::tempFileName($batch);

		$this->filePDF = pathinfo($this->file, PATHINFO_FILENAME).'.pdf';
	}

	public function subProcess(WordTemplateExporter $exporter, bool $returnFile = false)
	{
		$exporter
			->processFile($this->export)
			->getProcessedConvertedFile(Writer::DOCX, $this->file);

		if($this->format === Writer::PDF) {
			$this->callDone(Writer::PDF);
			if($returnFile) {
				return $this->file;
			}
			return $this->folder;
		}

		$putFileAs = Storage::disk($this->disk)
			->putFileAs($this->filePath, $this->file, $this->name,
				$this->diskOptions);
		if($putFileAs) {
			unlink($this->file);
		}
		$this->callDone($putFileAs);
		return $putFileAs;
	}
	public function process(WordTemplateExporter $exporter, string $batch,bool $returnFile = false)
	{
		$this->preProcess($batch);

		return $this->subProcess($exporter, $returnFile);
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

	public function getFolder() : string
	{
		return $this->folder;
	}

	public function getSubBatch() : string
	{
		return $this->subBatch;
	}

	/**
	 * Gets fired when the Templating is Done. If the Process needs to use PDF on a Batch, it will be fired before the PDF convert
	 * @param  callable|null|object|string  $callable
	 * @return void
	 */
	public function whenDone(null|callable|object|string $callable, ...$args)
	{
		if (
			is_object($callable)
			&& !is_callable($callable)
			&& !in_array(Queueable::class, class_uses_recursive($callable))
			&& in_array(Dispatchable::class, class_uses_recursive($callable))
		) {
			$callable = fn () => $callable->dispatch();
		}
		if(is_string($callable)) {
			$callable = fn () => call_user_func_array([$callable, 'dispatch'], $args);
		}
		$this->callableDone = $callable;
	}

	public function getClosures() : array
	{
		return [$this->callableDone, $this->callablePDFDone];
	}

	/**
	 * @param  callable|null|object|string $callable
	 * @return void
	 */
	public function whenPDFDone(null|callable|object|string $callable, ...$args)
	{
		if (
			is_object($callable)
			&& !is_callable($callable)
			&& !in_array(Queueable::class, class_uses_recursive($callable))
			&& in_array(Dispatchable::class, class_uses_recursive($callable))
		) {
			$callable = fn () => $callable->dispatch();
		}
		if(is_string($callable)) {
			$callable = fn () => call_user_func_array([$callable, 'dispatch'], $args);
		}
		$this->callablePDFDone = $callable;
	}

	/**
	 * @param  array  $files
	 * @return false|string
	 */
	public function copyOwnFileOfArray(array $files, bool $withDone = true)
	{
		foreach ($files as $file) {
			if(Str::contains($file, $this->filePDF)) {
				$putFileAs = Storage::disk($this->disk)
					->putFileAs($this->filePath, $file, $this->name,
						$this->diskOptions);
				if ($withDone) {
					$this->callPDFDone($putFileAs);
				}
				return $putFileAs;
			}
		}
		return false;
	}
}