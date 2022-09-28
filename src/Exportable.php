<?php

namespace Santwer\Exporter;

use Illuminate\Database\Eloquent\Model;
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

    public function getExportAttributes(array $conditionVariables = []): array
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
                $blockName = null;
                $primary = null;
                $attrs = [];
                /**
                 * @var Model $model
                 */
                foreach ($relation as $model) {
                    $mattr = [];
                    foreach ($model->getExportAttributes() as $attr => $val) {
                        $attrs[] = $attr;
                        $mattr[$model->getExportBlockValue($key).'.'.$attr] = $val;
                    }
                    $attributes[$model->getExportBlockValue($key)][]
                        = $mattr;
                    if ($blockName == null) {
                        $blockName = $model->getExportBlockValue($key);
                    }
                    if ($primary == null) {
                        $primary = $model->getKeyName();
                    }
                }
                if(!empty($conditionVariables) && $blockName !== null) {
                    if(isset($conditionVariables[$blockName])) {
                        [$whereKey, $whereCond, $whereValue, $field] = $conditionVariables[$blockName];
                        if($whereKey === '$primary') {
                            $whereKey = $primary;
                        }
                        $model = $relation->where($whereKey, $whereCond, $whereValue)->first();
                        if($model === null) {
                            foreach ($attrs as $attr) {
                                $attributes[$field.'.'.$attr] = null;
                            }
                        } else {
                            foreach ($model->getExportAttributes() as $attr => $val) {
                                $attributes[$field.'.'.$attr] = $val;
                            }
                        }
                    }
                }

            } else {

                if (!ModelProcessor::checkForExportable($relation)) {
                    $attributes[$key] = null;
                    continue;
                }

                foreach ($relation->getExportAttributes() as $attr => $val) {
                    $attributes[$relation->getExportBlockValue($key).'.'.$attr]
                        = $val;
                }
            }
        }

        return $attributes;
    }

    public function getExportBlockValue(string $key = null)
    {
        if (isset($this->exportBlock) && !empty($this->exportBlock)) {
            return $this->exportBlock;
        }
        if ($key === null) {
            return $this->getTable();
        }

        return $key;
    }
}