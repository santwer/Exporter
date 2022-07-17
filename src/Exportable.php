<?php

namespace Santwer\Exporter;

use Illuminate\Support\Collection;
use Santwer\Exporter\Eloquent\Builder;
use Santwer\Exporter\Processor\ModelProcessor;

/**
 * @method static export(?string $name, array $options = [])
 * @method static exportFirst(?string $name = null, $columns = ['*'], array $options = [])
 * @method static exportPdf(?string $name, array $options = [])
 * @method static exportFirstPdf(?string $name = null, $columns = ['*'], array $options = [])
 */
trait Exportable
{
    public function newEloquentBuilder($builder)
    {
        return new Builder($builder);
    }

    public function getExportAttributes(): array
    {
        if (method_exists($this, 'exportTokens')) {
            $attributes = $this->exportTokens();
        } else {
            $attributes = $this->attributesToArray();
        }
        foreach ($this->getRelations() as $key => $relation) {
            if ($relation instanceof Collection) {
                if (($first = $relation->first()) === null) {
                    $attributes[$key] = [];
                    continue;
                }
                if (!ModelProcessor::checkForExportable($first)) {
                    $attributes[$key] = [];
                    continue;
                }
                $attributes[$key] = [];
                foreach ($relation as $model) {
                    $mattr = [];
                    foreach ($model->getExportAttributes() as $attr => $val) {
                        $mattr[$model->getExportBlockValue().'.'.$attr] = $val;
                    }
                    $attributes[$model->getExportBlockValue()][]
                        = $mattr;
                }
            } else {
                if (!ModelProcessor::checkForExportable($relation)) {
                    $attributes[$key] = null;
                    continue;
                }

                foreach ($relation->getExportAttributes() as $attr => $val) {
                    $attributes[$model->getExportBlockValue().'.'.$attr]
                        = $val;
                }
            }
        }

        return $attributes;
    }

    public function getExportBlockValue()
    {
        if (isset($this->exportBlock)) {
            return $this->exportBlock;
        }

        return $this->getTable();
    }
}