<?php

namespace Santwer\Exporter\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Santwer\Exporter\Exceptions\NoFileException;
use Santwer\Exporter\Processor\Exporter;

trait ExportOptions
{
    protected $template;

    private static $formats = ['docx', 'html', 'pdf'];

    /**
     * @return string|null
     * @throws NoFileException
     */
    private function setModelTemplate(): ?string
    {
        if ($this->template) {
            return $this->template;
        }
        $template = $this->getModelTemplate();
        $this->template($template);

        return $template;
    }

    /**
     * @return string|null
     */
    private function getModelTemplate(): ?string
    {
        if (!$this->model) {
            return null;
        }
        if (!method_exists($this->model, 'exportTemplate')) {
            return null;
        }

        return $this->model->exportTemplate();
    }

    /**
     * @throws NoFileException
     */
    private function setOptions(array $options): void
    {
        if (empty($options)) {
            return;
        }
        if (isset($option['template'])) {
            $this->template($option['template']);
        }
    }

    /**
     * @param  Collection  $collection
     * @param  string|null  $name
     * @param  array  $options
     * @return ?\Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws NoFileException
     */
    private function exportDataCollection(
        Collection $collection,
        ?string $name,
        array $options = [],
        ?string $savePath = null
    ) {
        $this->setOptions($options);
        if (null === $this->setModelTemplate()) {
            throw new NoFileException($this->template);
        }

        $exporter = new Exporter($this->template);
        $exporter->setBlockValues(
            $this->model->getExportBlockValue(),
            $collection->map(fn ($model) => $model->getExportAttributes())->toArray()
        );

        $extTmp = pathinfo($this->template, PATHINFO_EXTENSION);

        if (!$name) {
            $name = implode(' - ',
                    [
                        $this->getModel()->getTable(),
                        now()->format('Y-m-d H i s'),
                    ]).'.'.$extTmp;
        }
        if (isset($options['format']) && in_array($options['format'], self::$formats)) {
            $endfile = $exporter->getProcessedConvertedFile($options['format']);
        } else {
            $endfile = $exporter->getProcessedFile();
        }

        if ($savePath) {
            if ($name) {
                Storage::putFileAs($savePath, $endfile, $name);
            } else {
                Storage::putFile($savePath, $endfile);
            }

            return null;
        }
        if (isset($options['format']) && in_array($options['format'], self::$formats)) {
            return response()
                ->download($endfile, $name);
        }

        return response()
            ->download($endfile, $name);
    }

}