<?php

declare(strict_types=1);

namespace Santwer\Exporter\Processor;

final class GlobalVariables
{
	/** @var array<string, mixed> */
	protected static array $globalVars = [];

	/**
	 * @return array<string, mixed>
	 */
	public static function getGlobalVariables(): array
	{
		$vars = [
			__('new_page') => ['<w:p><w:r><w:br w:type="page"/></w:r></w:p>', true],
		];

		return array_merge($vars, self::$globalVars);
	}

	public static function setVariable(string $key, mixed $value): void
	{
		self::$globalVars[$key] = $value;
	}

	/**
	 * @param array<string, mixed> $values
	 */
	public static function setVariables(array $values): void
	{
		foreach ($values as $key => $value) {
			self::setVariable($key, $value);
		}
	}

	public static function clear(): void
	{
		self::$globalVars = [];
	}
}