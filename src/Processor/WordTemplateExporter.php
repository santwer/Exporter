<?php

namespace Santwer\Exporter\Processor;

use Santwer\Exporter\Concerns\WithCharts;
use Santwer\Exporter\Concerns\WithImages;
use Santwer\Exporter\Concerns\WithTables;
use Santwer\Exporter\Concerns\TokensArray;
use Santwer\Exporter\Concerns\GlobalTokens;
use Santwer\Exporter\Concerns\WithCheckboxes;
use Santwer\Exporter\Concerns\TokensFromArray;
use Santwer\Exporter\Concerns\TokensFromModel;
use Santwer\Exporter\Concerns\FromWordTemplate;
use Santwer\Exporter\Concerns\TokensFromObject;
use Santwer\Exporter\Concerns\WithWordProcessor;
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
		$file = $this->getFilePath();

		$exporter = new Exporter($file);
		$this->setValues($exporter);

		return $exporter;
	}

	private function getFilePath(): string
	{
		$file = $this->export->wordTemplateFile();
		if (!file_exists($file)) {
			$file = storage_path($file);
		}
		if (!file_exists($file)) {
			$file = storage_path('app/'.$this->export->wordTemplateFile());
		}

		return $file;
	}

	private function setValues(Exporter $exporter): void
	{
		if ($this->hasConcern(TokensFromCollection::class) || $this->hasConcern(TokensFromArray::class)) {
			$exporter->setBlockValues($this->export->blockName(), $this->formatData());
		}

		if ($this->hasConcern(TokensArray::class)) {
			$exporter->setArray($this->export->tokens());
		} elseif ($this->hasConcern(TokensFromObject::class)) {
			$exporter->setObject($this->export->tokens());
		}

		if ($this->hasConcern(TokensFromModel::class)) {
			$exporter->setObject($this->export->model());
		}

		$this->setGlobalTokens($exporter);
		$this->setCheckboxes($exporter);
		$this->setCharts($exporter);
		$this->setImages($exporter);
		$this->setTables($exporter);

		if ($this->hasConcern(WithWordProcessor::class)) {
			$this->export->wordProcessor($exporter);
		}
	}

	private function setGlobalTokens(Exporter $exporter): void
	{
		if ($this->hasConcern(GlobalTokens::class)) {
			foreach ($this->export->values() as $key => $value) {
				$exporter->setValue($key, $value);
			}
		}
	}

	private function setCheckboxes(Exporter $exporter): void
	{
		if ($this->hasConcern(WithCheckboxes::class)) {
			foreach ($this->export->checkboxes() as $key => $value) {
				$exporter->setCheckbox($key, (bool)$value);
			}
		}
	}

	private function setCharts(Exporter $exporter): void
	{
		if ($this->hasConcern(WithCharts::class)) {
			foreach ($this->export->charts() as $key => $value) {
				if(is_callable($value)) {
					$value = $value();
				}
				$exporter->setChart($key, $value);
			}
		}
	}

	private function setImages(Exporter $exporter): void
	{
		if ($this->hasConcern(WithImages::class)) {
			foreach ($this->export->images() as $key => $value) {
				$exporter->setImage($key, $value);
			}
		}
	}

	private function setTables(Exporter $exporter): void
	{
		if ($this->hasConcern(WithTables::class)) {
			$exporter->setTables($this->export->tables());
		}
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