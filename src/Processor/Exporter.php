<?php

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;
use Santwer\Exporter\Writer;
use PhpOffice\PhpWord\Element\Table;
use Santwer\Exporter\Eloquent\Builder;

class Exporter implements \Santwer\Exporter\Interfaces\ExporterInterface
{
	protected $wordfile;

	protected array $values = [];

	protected array $blocks = [];

	protected array $checkboxes = [];

	protected array $charts = [];
	protected array $images = [];
	protected array $tables = [];

	protected $templateProcessor;

	/**
	 * @var Builder $builder
	 */
	protected $builder;


	public function __construct(string $wordfile)
	{
		$this->wordfile = $wordfile;
	}

	public function setTemplateProcessor(callable $templateProcessor): void
	{
		$templateProcessor($this->templateProcessor);
	}


	public function getTemplateVariables()
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
	 * @param  array  $values
	 * @return void
	 */
	public function setArrayValues(array $values)
	{
		$this->values = array_merge($this->values, $values);
	}

	/**
	 * @param  string  $block
	 * @param  array   $values
	 * @return void
	 */
	public function setBlockValues(string $block, array $values)
	{
		$this->blocks[$block] = $values;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return void
	 */
	public function setValue($name, $value)
	{
		$this->values[$name] = $value;
	}

	public function setCheckbox(string $name, bool $value)
	{
		$this->checkboxes[$name] = $value;
	}

	public function setChart(string $name,object $value)
	{
		$this->charts[$name] = $value;
	}
	public function setImage(string $name, $value)
	{
		$this->images[$name] = $value;
	}

	public function setTables(array $tables)
	{
		$this->tables = $tables;
	}


	/**
	 * @param  string|null  $savepath
	 * @return array|false|string|string[]
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	public function getProcessedFile(?string $savepath = null)
	{
		$templateProcessor = $this->process();
		$savepath = $savepath ?? $this->getTempFileName();
		$templateProcessor->saveAs($savepath);

		return $savepath;
	}

	/**
	 * @param  string       $format
	 * @param  string|null  $savepath
	 * @return array|false|string|string[]
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	public function getProcessedConvertedFile(string $format, ?string $savepath = null)
	{
		$templateProcessor = $this->process();
		$savepath = $savepath ?? $this->getTempFileName('docx');
		$templateProcessor->saveAs($savepath);
		if ($format === Writer::PDF) {
			return PDFExporter::docxToPdf($savepath,
				$savepath ? pathinfo($savepath,
					PATHINFO_DIRNAME) : null);
		}
		if ($format === 'html') {
			return PDFExporter::html2Pdf($savepath,
				$savepath ? pathinfo($savepath,
					PATHINFO_DIRNAME) : null);
		}

		return $savepath;
	}

	private function getTemplateProcessor(): TemplateProcessor
	{
		if (null === $this->templateProcessor) {
			$this->templateProcessor = new TemplateProcessor($this->wordfile);
		}

		return $this->templateProcessor;
	}

	/**
	 * @return TemplateProcessor
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	public function process(): TemplateProcessor
	{
		$templateProcessor = $this->getTemplateProcessor();
		$values = collect($this->values);

		$setValues = $values->filter(fn ($x) => !is_array($x));
		$setValues = collect(GlobalVariables::getGlobalVariables())
			->merge($setValues)
			->toArray();

		if (!empty($setValues)) {
			$templateProcessor->setValues($setValues);
		}

		if ($this->builder) {
			$this->builder->checkForRelations($templateProcessor->getVariables());
		}
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

		if(!empty($this->tables)) {
			foreach ($this->tables as $table => $tableData) {
				$templateProcessor->setComplexBlock($table, $this->tableDataToComplexBlock($tableData));
			}
		}

		if (!empty($this->blocks)) {

			foreach ($this->blocks as $block => $replacement) {
				$replacements = collect($replacement)
					->map(function ($y) {
						return collect($y)
							->toArray();
					})->toArray();

				$templateProcessor->cloneRecrusiveBlocks($block, 0,
					true,
					false, $replacements);

			}
		}

		return $templateProcessor;
	}

	/**
	 * transform table data to complex block
	 * @param $tableData
	 * @return Table
	 */
	private function tableDataToComplexBlock($tableData) : Table
	{

		if(is_callable($tableData)) {
			$tableData = $tableData();
		}
		$style = isset($tableData['style']) ? $tableData['style'] : null;
		$table = new Table($style);

		if(isset($tableData['headers'])) {
			$table->addRow();
			foreach ($tableData['headers'] as $header) {
				if(is_array($header)) {
					$table->addCell(
						isset($header['width']) ? $header['width'] : null,
						isset($header['style']) ? $header['style'] : null
					)->addText($header['text']);
				} else {
					$table->addCell()->addText($header);
				}
			}

		}

		if(isset($tableData['rows'])) {
			foreach ($tableData['rows'] as $row) {
				$table->addRow();
				foreach ($row as $column) {
					if(is_array($column)) {
						$table->addCell(
							isset($column['width']) ? $column['width'] : null,
							isset($column['style']) ? $column['style'] : null
						)->addText($column['text']);
					} else {
						$table->addCell()->addText($column);
					}
				}
			}
		}

		return $table;
	}


	private function addEmptyValues($blocks)
	{
		$variables = $this->getTemplateVariables();
		foreach ($variables as $variable) {
			[$sp] = explode('.', $variable);
			if (
				in_array('/'.$sp, $variables)
				|| Str::contains(':', $variable) || Str::startsWith($variable, '/')) {
				continue;
			} else {
				foreach ($blocks as $b => $block) {
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

	/**
	 * @param  string|null  $ext
	 * @param  bool         $withoutPath
	 * @return array|false|string|string[]
	 */
	public function getTempFileName(string $ext = null, bool $withoutPath = false)
	{
		if ($withoutPath) {
			$temp = tempnam('', 'Exp');
			$temp = pathinfo($temp, PATHINFO_BASENAME);
		} else {
			$temp = tempnam(sys_get_temp_dir(), 'Exp');
		}
		if (null === $ext) {
			return $temp;
		}

		return Str::replace('.tmp', '.'.$ext, $temp);
	}

}