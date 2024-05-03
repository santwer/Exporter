<?php

namespace Santwer\Exporter\Jobs;

use Santwer\Exporter\Writer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Santwer\Exporter\Exportables\Exportable;
use Santwer\Exporter\Processor\WordTemplateExporter;

class WordProcessorJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected Exportable $export;
	protected WordTemplateExporter $exporter;
	protected string $batch;
	public function __construct(WordTemplateExporter $exporter, Exportable $export,string $batch)
	{
		$this->exporter = $exporter;
		$this->export = $export;
		$this->batch = $batch;
	}

	public function handle(): void
	{
		$folder = $this->export->process($this->exporter, $this->batch);
		if($this->export->getFormat() === Writer::PDF) {
			WordToPDF::dispatch($this->export, $folder);
		}

	}
}
