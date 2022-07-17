<?php

namespace Santwer\Exporter\Processor;

class GlobalVariables
{
    public static function getGlobalVariables(): array
    {
        return [

            __('new_page') => '<w:p><w:r><w:br w:type="page"/></w:r></w:p>',

        ];
    }
}