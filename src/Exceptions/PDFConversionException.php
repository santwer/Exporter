<?php

namespace Santwer\Exporter\Exceptions;

class PDFConversionException extends \RuntimeException
{
	public static function fromProcess(string $output, string $error): self
	{
		return new self("PDF conversion failed: {$error}\nOutput: {$output}");
	}
}
