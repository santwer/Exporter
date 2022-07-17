<?php

namespace Santwer\Exporter\Exceptions;

class NoFileException extends \Exception
{
    public function __construct(?string $filePath = "")
    {
        if (null === $filePath) {
            $message = __('No File is set for Export.');
        } else {
            $message = __('The File on FilePath :filepath does not exists.', ['filepath' => $filePath]);
        }
        parent::__construct($message, 0, null);
    }
}