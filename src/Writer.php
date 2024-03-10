<?php

namespace Santwer\Exporter;

class Writer
{
	const DOCX = 'docx';
	const HTML = 'html';
	const PDF = 'pdf';

	public static function formats() : array
	{
		return [self::DOCX, self::HTML, self::PDF];
	}
}