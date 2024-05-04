<?php

namespace Santwer\Exporter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Santwer\Exporter\Helpers\ExportHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Santwer\Exporter\Exportables\Exportable;

class WordToPDF implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected Exportable $export;
	protected string $folder;
	public function __construct(Exportable $export)
	{
		$this->export = $export;
		$this->folder = $export->getFolder();
	}

	public function handle(): void
	{
		$files = ExportHelper::processWordToPdf($this->folder);
		$this->export->copyOwnFileOfArray($files);
	}
}
