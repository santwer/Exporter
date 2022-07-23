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


    /**
     * Identify all relationships for a given model
     *
     * @param   object  $model  Model
     * @param   string  $heritage   A flag that indicates whether parent and/or child relationships should be included
     * @return  array
     */
    public static function getAllRelations(\Illuminate\Database\Eloquent\Model $model, $heritage = 'all')
    {
        $modelName = get_class($model);
        $types = ['children' => 'Has', 'parents' => 'Belongs', 'all' => ''];
        $heritage = in_array($heritage, array_keys($types)) ? $heritage : 'all';
        if (\Illuminate\Support\Facades\Cache::has($modelName."_{$heritage}_relations") && !env('APP_DEBUG', false)) {
            return \Illuminate\Support\Facades\Cache::get($modelName."_{$heritage}_relations");
        }

        $reflectionClass = new \ReflectionClass($model);
        $traits = $reflectionClass->getTraits();    // Use this to omit trait methods
        $traitMethodNames = [];
        foreach ($traits as $name => $trait) {
            $traitMethods = $trait->getMethods();
            foreach ($traitMethods as $traitMethod) {
                $traitMethodNames[] = $traitMethod->getName();
            }
        }

        // Checking the return value actually requires executing the method.  So use this to avoid infinite recursion.
        $currentMethod = collect(explode('::', __METHOD__))->last();
        $filter = $types[$heritage];
        $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);  // The method must be public
        $methods = collect($methods)->filter(function ($method) use ($modelName, $traitMethodNames, $currentMethod) {
            $methodName = $method->getName();
            if (!in_array($methodName, $traitMethodNames)   //The method must not originate in a trait
                && strpos($methodName, '__') !== 0  //It must not be a magic method
                && $method->class === $modelName    //It must be in the self scope and not inherited
                && !$method->isStatic() //It must be in the this scope and not static
                && $methodName != $currentMethod    //It must not be an override of this one
            ) {
                $parameters = (new \ReflectionMethod($modelName, $methodName))->getParameters();
                return collect($parameters)->filter(function ($parameter) {
                    return !$parameter->isOptional();   // The method must have no required parameters
                })->isEmpty();  // If required parameters exist, this will be false and omit this method
            }
            return false;
        })->mapWithKeys(function ($method) use ($model, $filter) {
            $methodName = $method->getName();
            $relation = $model->$methodName();  //Must return a Relation child. This is why we only want to do this once
            if (is_subclass_of($relation, \Illuminate\Database\Eloquent\Relations\Relation::class)) {
                $type = (new \ReflectionClass($relation))->getShortName();  //If relation is of the desired heritage
                if (!$filter || strpos($type, $filter) === 0) {
                    $relationClass = $relation->getRelated();
                    $relationTemplateName = method_exists($relationClass, 'getExportBlockValue') ?  $relationClass->getExportBlockValue() : $methodName;
                    return [$methodName => [$relationTemplateName, $relationClass]]; // ['relationName'=>'relatedModelClass']
                }
            }
            return [];   // Remove elements reflecting methods that do not have the desired return type
        })->toArray();

        \Illuminate\Support\Facades\Cache::forever($modelName."_{$heritage}_relations", $methods);
        return $methods;
    }
}