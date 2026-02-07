<?php

declare(strict_types=1);

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;
use Santwer\Exporter\Writer;
use PhpOffice\PhpWord\Element\Table;
use Santwer\Exporter\Eloquent\Builder;
use Santwer\Exporter\Helpers\ExportHelper;

class Exporter implements \Santwer\Exporter\Interfaces\ExporterInterface
{
	/** @var array<string, mixed> */
	protected array $values = [];

	/** @var array<string, array<int, array<string, mixed>>> */
	protected array $blocks = [];

	/** @var array<string, bool> */
	protected array $checkboxes = [];

	/** @var array<string, object> */
	protected array $charts = [];

	/** @var array<string, string|array> */
	protected array $images = [];

	/** @var array<string, array|callable> */
	protected array $tables = [];

	protected ?TemplateProcessor $templateProcessor = null;

	protected ?Builder $builder = null;

	public function __construct(
		protected readonly string $wordfile
	) {}

	public function setTemplateProcessor(callable $templateProcessor): void
	{
		$templateProcessor($this->templateProcessor);
	}

	/**
	 * @return array<string>
	 */
	public function getTemplateVariables(): array
	{
		return $this->getTemplateProcessor()->getVariables();
	}

	public function setObject(object $object): void
	{
		if (method_exists($object, 'toArray')) {
			$this->setArray($object->toArray());

			return;
		}
		$array = json_decode(json_encode($object), true);

		$this->setArray($array);
	}

	public function setArray(array $array, string $prefix = ''): void
	{
		$returnArray = [];
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$this->setArray($value, (empty($prefix) ? '' : $prefix.'.').$key);
				continue;
			}
			$returnArray[(empty($prefix) ? '' : $prefix.'.').$key] = $value;
		}
		$this->setArrayValues($returnArray);
	}

	/**
	 * @param array<string, mixed> $values
	 */
	public function setArrayValues(array $values): void
	{
		$this->values = array_merge($this->values, $values);
	}

	/**
	 * @param array<int, array<string, mixed>> $values
	 */
	public function setBlockValues(string $block, array $values): void
	{
		$this->blocks[$block] = $values;
	}

	public function setValue(string $name, mixed $value): void
	{
		$this->values[$name] = $value;
	}

	public function setCheckbox(string $name, bool $value): void
	{
		$this->checkboxes[$name] = $value;
	}

	public function setChart(string $name, object $value): void
	{
		$this->charts[$name] = $value;
	}

	public function setImage(string $name, string|array $value): void
	{
		$this->images[$name] = $value;
	}

	/**
	 * @param array<string, array|callable> $tables
	 */
	public function setTables(array $tables): void
	{
		$this->tables = $tables;
	}

	/**
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	public function getProcessedFile(?string $savepath = null): string
	{
		$templateProcessor = $this->process();
		$savepath = $savepath ?? $this->getTempFileName();
		$templateProcessor->saveAs($savepath);

		return $savepath;
	}

	/**
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	public function getProcessedConvertedFile(string $format, ?string $savepath = null): string
	{
		$templateProcessor = $this->process();
		$savepath = $savepath ?? $this->getTempFileName('docx');

		$templateProcessor->saveAs(
			ExportHelper::convertForRunningInConsole($savepath)
		);

		return match ($format) {
			Writer::PDF, 'pdf' => PDFExporter::docxToPdf(
				$savepath,
				$savepath ? pathinfo($savepath, PATHINFO_DIRNAME) : null
			),
			'html' => PDFExporter::html2Pdf(
				$savepath,
				$savepath ? pathinfo($savepath, PATHINFO_DIRNAME) : null
			),
			default => ExportHelper::convertForRunningInConsole($savepath),
		};
	}

	public function getTemplateProcessor(): TemplateProcessor
	{
		return $this->templateProcessor ??= new TemplateProcessor($this->wordfile);
	}

	/**
	 * @return TemplateProcessor
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	public function process(): TemplateProcessor
	{
		// Disable PHPWord output escaping to avoid double-escaping
		// (our TemplateProcessor::replace() handles all XML escaping)
		\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(false);

		$templateProcessor = $this->getTemplateProcessor();
		$values = collect($this->values);

		$setValues = $values->filter(fn ($x) => !is_array($x));
		$setValues = collect(GlobalVariables::getGlobalVariables())
			->merge($setValues)
			->toArray();

		if (!empty($setValues)) {
			$templateProcessor->setValues($setValues);
		}

		$this->builder?->checkForRelations($templateProcessor->getVariables());

		$this->blocks = $this->addEmptyValues($this->blocks);
		if (!empty($this->checkboxes)) {
			foreach ($this->checkboxes as $checkbox => $value) {
				$templateProcessor->setCheckbox($checkbox, $value);
			}
		}
		if (!empty($this->charts)) {
			foreach ($this->charts as $chartName => $value) {
				$templateProcessor->setChart($chartName, $value);
			}
		}
		if (!empty($this->images)) {
			foreach ($this->images as $image => $value) {
				$templateProcessor->setImageValue($image, $value);
			}
		}

		if (!empty($this->tables)) {
			foreach ($this->tables as $table => $tableData) {
				$templateProcessor->setComplexBlock($table, $this->tableDataToComplexBlock($tableData));
			}
		}

		if (!empty($this->blocks)) {
			foreach ($this->blocks as $block => $replacement) {
				$replacements = collect($replacement)
					->map(fn ($y) => collect($y)->toArray())
					->toArray();

				$templateProcessor->cloneRecursiveBlocks(
					blockname: $block,
					clones: 0,
					replace: true,
					indexVariables: false,
					variableReplacements: $replacements
				);
			}
		}

		return $templateProcessor;
	}

	private function tableDataToComplexBlock(array|callable $tableData): Table
	{
		if (is_callable($tableData)) {
			$tableData = $tableData();
		}
		$style = $tableData['style'] ?? null;
		$table = new Table($style);

		if (isset($tableData['headers'])) {
			$table->addRow();
			foreach ($tableData['headers'] as $header) {
				if (is_array($header)) {
					$table->addCell(
						$header['width'] ?? null,
						$header['style'] ?? null
					)->addText($this->templateProcessor->replace($header['text']));
				} else {
					$table->addCell()->addText($this->templateProcessor->replace($header));
				}
			}
		}

		if (isset($tableData['rows'])) {
			foreach ($tableData['rows'] as $row) {
				$table->addRow();
				foreach ($row as $column) {
					if (is_array($column)) {
						$table->addCell(
							$column['width'] ?? null,
							$column['style'] ?? null
						)->addText($this->templateProcessor->replace($column['text']));
					} else {
						$table->addCell()->addText($this->templateProcessor->replace($column));
					}
				}
			}
		}

		return $table;
	}

	/**
	 * @param array<string, array<int, array<string, mixed>>> $blocks
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function addEmptyValues(array $blocks): array
	{
		$variables = $this->getTemplateVariables();
		foreach ($variables as $variable) {
			[$sp] = explode('.', $variable);

			if (
				in_array('/'.$sp, $variables)
				|| Str::contains(':', $variable) || Str::startsWith($variable, '/')
			) {
				continue;
			} else {
				foreach ($blocks as $b => $block) {
					$blocks[$b] = $this->templateProcessor->arrayListRecursive($block);
					foreach ($block as $e => $entry) {
						if (isset($entry[$variable])) {
							continue;
						}
						$blocks[$b][$e][$variable] = null;
					}
				}
			}
		}

		return $blocks;
	}

	public function getTempFileName(?string $ext = null, bool $withoutPath = false): string
	{
		if ($withoutPath) {
			$temp = tempnam('', 'Exp');
			$temp = pathinfo($temp, PATHINFO_BASENAME);
		} else {
			$temp = ExportHelper::tempFile();
		}
		if (null === $ext) {
			return $temp;
		}

		return Str::replace('.tmp', '.'.$ext, $temp);
	}
}