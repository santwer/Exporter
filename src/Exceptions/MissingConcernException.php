<?php

namespace Santwer\Exporter\Exceptions;

class MissingConcernException extends \Exception
{
	public function __construct(?array $missing = [])
	{
		if (null === $missing) {
			$message = __('Missing concerns for Export.');
		} else {
			$message = __('The Export misses Concerns :missing.', ['missing' => implode(', ', $missing)]);
		}
		parent::__construct($message, 0, null);
	}
}