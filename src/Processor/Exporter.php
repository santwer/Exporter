<?php

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;
use Santwer\Exporter\Eloquent\Builder;

class Exporter implements \Santwer\Exporter\Interfaces\ExporterInterface
{
    protected $wordfile;

    protected $values = [];

    protected $blocks = [];

    protected $templateProcessor;

    /**
     * @var Builder $builder
     */
    protected $builder;


    public function __construct(string $wordfile)
    {
        $this->wordfile = $wordfile;
    }

    public function getTemplateVariables()
    {
       return $this->getTemplateProcessor()->getVariables();
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
        if ($format === 'pdf') {
            return PDFExporter::docxToPdf($savepath,
                $savepath ? pathinfo($savepath,
                    PATHINFO_DIRNAME) : null);
        }
        if ($format === 'pdf') {
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

    private function addEmptyValues($blocks)
    {
        $variables = $this->getTemplateVariables();
        foreach ($variables as $variable) {
            [$sp] = explode('.', $variable);
            if(in_array('/'.$sp, $variables)
                || Str::contains(':', $variable) || Str::startsWith( $variable, '/')) {
                continue;
            } else {
                foreach ($blocks as $b => $block) {
                    foreach ($block as $e => $entry) {
                        if (isset($entry[$variable]) ) {
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