<?php

namespace Santwer\Exporter\Processor;

class GlobalVariables
{
    protected static $globalVars = [];

    public static function getGlobalVariables(): array
    {
        $vars = [

            __('new_page') => ['<w:p><w:r><w:br w:type="page"/></w:r></w:p>', true],

        ];

        return array_merge($vars, self::$globalVars);
    }

    /**
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    public static function setVariable(string $key, string $value)
    {
        self::$globalVars[$key] = $value;
    }

    public static function setVariables(array $values)
    {
        foreach($values as $key => $value) {
            self::setVariable($key, $value);
        }
    }

}