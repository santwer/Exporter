<?php

namespace Santwer\Exporter\Traits;

trait BuilderExportPdf
{
    /**
     * @param  string|null  $name
     * @param  array        $options
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Santwer\Exporter\Exceptions\NoFileException
     */
    public function exportPdf(?string $name = null, array $options = [])
    {
        $options['format'] = 'pdf';
        return $this->export($name, $options);
    }

    /**
     * @param  string|null  $name
     * @param               $columns
     * @param  array        $options
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportFirstPdf(?string $name = null, $columns = ['*'], array $options = [])
    {
        $options['format'] = 'pdf';
        return $this->exportFirst($name, $columns, $options);
    }




}