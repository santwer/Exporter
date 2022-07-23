<?php

namespace Santwer\Exporter\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Santwer\Exporter\Exceptions\NoFileException;
use Santwer\Exporter\Processor\Exporter;
use Santwer\Exporter\Processor\GlobalVariables;
use Santwer\Exporter\Processor\ModelProcessor;

trait ExportOptions
{
    protected $template;

    protected $processor = Exporter::class;

    protected $relationsFromTemplate = null;

    protected $process;

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
        if (null === $template) {
            throw new NoFileException('[Empty]');
        }
        $this->template($template);

        return $template;
    }

    public function setProcessor(?string $class)
    {
        if(class_exists($class)) {
            $this->processor = $class;
            return $this;
        }
        $this->processor = Exporter::class;
        return $this;
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
        if (isset($options['template'])) {
            $this->template($options['template']);
        }
        if (isset($options['relations'])) {
            $this->loadRelationsFromTemplate($options['relations'] === true || $options['relations'] == 1);
        }
        if (isset($options['processor'])) {
            $this->setProcessor($options['processor']);
        }

    }

    public function loadRelationsFromTemplate(bool $relationsFromTemplate)
    {
        $this->relationsFromTemplate = $relationsFromTemplate;
    }

    private function beginnProcess($options)
    {
        $this->setOptions($options);
        if (null === $this->setModelTemplate()) {
            throw new NoFileException($this->template);
        }

        /**
         * @var Exporter $exporter
         */
        $this->process = new $this->processor($this->template);
        if($this->relationsFromTemplate || $this->relationsFromTemplate === null && GlobalVariables::config('relationsFromTemplate', true)) {
            if (is_array($array = $this->process->getTemplateVariables())) {
                $this->checkForRelations($array);
            }
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
        ?string $savePath = null
    ) {
        $exporter = $this->process;
        if(null === $exporter) {
            throw new \Exception('Error Process not started.');
        }
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

    public function checkForRelations(array $relations)
    {
       $deleteRelations = GlobalVariables::config('removeRelations', true);
       if($deleteRelations) {
           $eagerLoads = $this->getEagerLoads();

           foreach ($eagerLoads as $relation => $closure) {
               if (!in_array($relation, $relations)) {
                   unset($eagerLoads[$relation]);
               }
           }
           $this->setEagerLoads($eagerLoads);
       }
        $this->autoloadRelations($relations, $this->getModel());

    }

    private function autoloadRelations(array $relations, $model, string $prefix = "")
    {
        $eagerLoads = $this->getEagerLoads();
        $modelRelations = ModelProcessor::getAllRelations($model);

        foreach ($modelRelations as $relation => [$blockName, $class]) {
            if(in_array($blockName, $relations) && !isset($eagerLoads[$prefix.$relation])) {
                $this->with($prefix.$relation);
            }
            $subRelations = collect($relations)->filter(fn($x) => Str::startsWith($x, $blockName.'.'));
            if($subRelations->count() > 0) {

                $this->autoloadRelations(
                    $subRelations->map(fn($x) => Str::replace($blockName.'.', '', $x))->toArray(),
                    $class,
                    $prefix.$relation.'.'
                );
            }
        }

    }



}