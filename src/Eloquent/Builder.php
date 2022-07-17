<?php

namespace Santwer\Exporter\Eloquent;

use \Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Str;
use Santwer\Exporter\Exceptions\NoFileException;
use Santwer\Exporter\Traits\BuilderExportPdf;
use Santwer\Exporter\Traits\ExportOptions;

class Builder extends EloquentBuilder
{
    use ExportOptions, BuilderExportPdf;

    protected static $exportdata;

    /**
     * @param  array  $options
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws NoFileException
     */
    public function export(?string $name = null, array $options = [])
    {
        if($this::$exportdata === null && !empty($this->getModel()->getAttributes())) {
            $data = collect([$this->getModel()]) ?? $this->get();
        } else {
            $data = $this::$exportdata ? collect([$this::$exportdata]) : $this->get();
        }

        return $this->exportDataCollection(
            $data, $name, $options
        );
    }

    public function exportFirst(?string $name = null, $columns = ['*'], array $options = [])
    {
        $data = collect([$this->first($columns)]);

        return $this->exportDataCollection(
            $data, $name, $options
        );
    }

    /**
     * @param  string  $templatePath
     * @return $this
     * @throws NoFileException
     */
    public function template(string $templatePath): Builder
    {
        $storagePath = $templatePath;
        if (!file_exists($templatePath)) {
            $storagePath = storage_path($templatePath);
            if (!file_exists($storagePath)) {
                $storagePath = storage_path('app/'.$templatePath);
                if (!file_exists($storagePath)) {
                    throw new NoFileException($templatePath);
                }
            }
        }
        $this->template = $storagePath;

        return $this;
    }


    public function store(string $path, array $options = [])
    {
        return $this->storeAs($path, $this->hashName($options), $options);
    }

    public function storeAs(string $filePath, string $name, $options = [])
    {
        if($this::$exportdata === null && !empty($this->getModel()->getAttributes())) {
            $data = collect([$this->getModel()]) ?? $this->get();
        } else {
            $data = $this::$exportdata ? collect([$this::$exportdata]) : $this->get();
        }

        return $this->exportDataCollection(
            $data, $name, $options, $filePath
        );
    }


    public function first($columns = ['*'])
    {
        return self::$exportdata = $this->take(1)->get($columns)->first();
    }


    /**
     * Get a filename for the file.
     *
     * @param  string|null  $path
     * @return string
     */
    private function hashName(array $options = [])
    {

        $hash = $this->hashName ?: $this->hashName = Str::random(40);
        if(!isset($options['format'])) {
            $options['format'] = 'docx';
        }

        $extension = '.'.$options['format'];


        return $hash.$extension;
    }
}