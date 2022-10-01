<?php

namespace Santwer\Exporter\Traits;

trait ExportDebug
{
    public function ddExport($name = null, array $options = [])
    {
        $options['debug'] = true;
        return $this->export($name, $options);
    }
}