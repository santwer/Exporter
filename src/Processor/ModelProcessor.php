<?php

namespace Santwer\Exporter\Processor;

use Illuminate\Database\Eloquent\Model;
use Santwer\Exporter\Exportable;

class ModelProcessor
{

    public static function checkForExportable(?object $class)
    {
        if ($class === null) {
            return false;
        }

        return in_array(
            Exportable::class,
            array_keys((new \ReflectionClass($class))->getTraits())
        );
    }
}