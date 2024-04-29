<?php

namespace Santwer\Exporter\Exceptions;

use Throwable;

class TempFolderException extends \Exception
{
	public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}