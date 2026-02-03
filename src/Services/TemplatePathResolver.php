<?php

namespace Santwer\Exporter\Services;

use Santwer\Exporter\Exceptions\NoFileException;

class TemplatePathResolver
{
	public function resolve(string $path): string
	{
		$candidates = [
			$path,
			storage_path($path),
			storage_path('app/'.$path),
		];

		foreach ($candidates as $candidate) {
			if (file_exists($candidate)) {
				return $candidate;
			}
		}

		throw new NoFileException($path);
	}
}
