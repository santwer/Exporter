<?php

declare(strict_types=1);

namespace Santwer\Exporter;

final class Writer
{
	public const string DOCX = 'docx';
	public const string HTML = 'html';
	public const string PDF = 'pdf';

	/**
	 * @return array<string>
	 */
	public static function formats(): array
	{
		return [self::DOCX, self::HTML, self::PDF];
	}
}