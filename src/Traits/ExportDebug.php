<?php

namespace Santwer\Exporter\Traits;

/**
 * @deprecated Query-based export; will be removed in a future version.
 */
trait ExportDebug
{
    /**
     * @deprecated Query-based export; will be removed. Use export classes (FromWordTemplate) instead.
     */
    public function ddExport($name = null, array $options = [])
    {
        $options['debug'] = true;
        return $this->export($name, $options);
    }
}