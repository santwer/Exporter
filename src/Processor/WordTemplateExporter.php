<?php

namespace Santwer\Exporter\Processor;

use Santwer\Exporter\Concerns\TokensArray;
use Santwer\Exporter\Concerns\GlobalTokens;
use Santwer\Exporter\Concerns\TokensFromArray;
use Santwer\Exporter\Concerns\TokensFromModel;
use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\TokensFromObject;
use Santwer\Exporter\Concerns\TokensFromCollection;
use Santwer\Exporter\Exceptions\MissingConcernException;

class WordTemplateExporter
{
	protected object $export;

	protected array $concerns = [];

	public function processFile(object $export): Exporter
	{
		$this->export = $export;
		$this->implementsMinumum();
		$file = $export->wordTemplateFile();
		if (!file_exists($file)) {
			$file = storage_path($file);
		}
		if (!file_exists($file)) {
			$file = storage_path('app/'.$export->wordTemplateFile());
		}

		$exporter = new Exporter($file);
		if ($this->hasConcern(TokensFromCollection::class) || $this->hasConcern(TokensFromArray::class)) {
			$exporter->setBlockValues($this->export->blockName(), $this->formatData());
		}

		if ($this->hasConcern(TokensArray::class)) {
			$exporter->setArray($this->export->tokens());
		}
		else if ($this->hasConcern(TokensFromObject::class)) {
			$exporter->setObject($this->export->tokens());
		}

		if ($this->hasConcern(TokensFromModel::class)) {
			$exporter->setObject($this->export->model());
		}

		if ($this->hasConcern(GlobalTokens::class)) {
			foreach ($this->export->values() as $key => $value) {
				$exporter->setValue($key, $value);
			}
		}

		return $exporter;
	}

	private function formatData()
	{
		if ($this->hasConcern(TokensFromCollection::class)) {
			return $this->export->items()->map(fn ($x) => $this->export->itemTokens($x))->toArray();
		}
		if ($this->hasConcern(TokensFromArray::class)) {
			return array_map(fn ($x) => $this->export->itemTokens($x), $this->export->items());
		}
		throw new MissingConcernException();
	}

	private function implementsMinumum()
	{
		$this->concerns = class_implements($this->export);
		$implementsMissing = array_diff([FromWordTemplate::class], $this->concerns);
		if (empty($implementsMissing)) {
			return;
		}

		throw new MissingConcernException($implementsMissing);
	}

	private function hasConcern(string $concern): bool
	{
		return in_array($concern, $this->concerns);
	}
}