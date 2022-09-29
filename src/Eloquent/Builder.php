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
     * @param  array|string|null  $name
     * @param  array        $options
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
     * @throws NoFileException
     */
    public function export($name = null, array $options = [])
    {
        if(is_array($name)) {
            $options = array_merge($name, $options);
            $name = isset($options['name']) ? $options['name'] : null;
        }
        $this->beginnProcess($options);
        if($this::$exportdata === null && !empty($this->getModel()->getAttributes())) {
            $data = collect([$this->getModel()]) ?? $this->get();
        } elseif(isset($options['with'])) {
            $data = $this->with($options['with'])->get();
        }
        else {

            $data = $this::$exportdata ? collect([$this::$exportdata]) : $this->get();
        }

        return $this->exportDataCollection(
            $data, $name
        );
    }

    /**
     * @param  array|string|null  $name
     * @param               $columns
     * @param  array        $options
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
     * @throws NoFileException
     */
    public function exportFirst($name = null, $columns = ['*'], array $options = [])
    {
        if(is_array($name)) {
            $options = array_merge($name, $options);
            $name = isset($options['name']) ? $options['name'] : null;
        }
        $this->beginnProcess($options);
        $data = collect([$this->first($columns)]);

        return $this->exportDataCollection(
            $data, $name
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
        $this->beginnProcess($options);
        if($this::$exportdata === null && !empty($this->getModel()->getAttributes())) {
            $data = collect([$this->getModel()]) ?? $this->get();
        } else {
            $data = $this::$exportdata ? collect([$this::$exportdata]) : $this->get();
        }

        return $this->exportDataCollection(
            $data, $name, $filePath
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