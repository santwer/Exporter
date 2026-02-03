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

    public static function setVariable(string $key, string $value): void
    {
        self::$globalVars[$key] = $value;
    }

    public static function setVariables(array $values): void
    {
        foreach($values as $key => $value) {
            self::setVariable($key, $value);
        }
    }

    public static function clear(): void
    {
        self::$globalVars = [];
    }
}