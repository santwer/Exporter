<?php

namespace Santwer\Exporter\Exportables;

use Santwer\Exporter\Processor\BatchProcessor;

class Exportable
{
	use BatchProcessor;
	protected object $export;
	protected string $filePath;
	protected string $name;
	protected ?string $disk = null;
	protected ?string $writerType = null;
	protected array $diskOptions = [];

	public function __construct(
		object  $export,
		string  $filePath,
		string  $name,
		?string $disk = null,
		?string $writerType = null,
		array   $diskOptions = []
	) {
		$this->export = $export;
		$this->filePath = $filePath;
		$this->name = $name;
		$this->disk = $disk;
		$this->writerType = $writerType;
		$this->diskOptions = $diskOptions;
	}

	public static function create(
		object $export,
		string $filePath,
		string $name,
		string $disk = null,
		string $writerType = null,
		array  $diskOptions = []
	) {
		return new self($export, $filePath, $name, $disk, $writerType, $diskOptions);
	}
}