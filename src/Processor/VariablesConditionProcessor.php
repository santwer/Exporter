<?php

declare(strict_types=1);

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;

final class VariablesConditionProcessor
{
	/**
	 * Adds Block Relations if needed
	 *
	 * @param  array<string>  $variables
	 * @return array<string>
	 */
	public static function getReducedForRelations(array $variables): array
	{
		foreach ($variables as $key => $variable) {
			if (is_string($variable) && Str::contains($variable, ':')) {
				$array = collect(explode('.', $variable))
					->map(fn ($x) => Str::beforeLast($x, ':'));
				if (!in_array($array->first(), $variables)) {
					$variables[] = $array->first();
				}

				$variables[$key] = $array->implode('.');
			}
		}
		return $variables;
	}

	/**
	 * @param  array<string>  $variables
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
	 */
	public static function getRelatedConditions(array $variables): array
	{
		$conditions = [];
		foreach ($variables as $key => $variable) {
			if (is_string($variable) && Str::contains($variable, ':')) {
				$condition = collect(explode('.', $variable))
					->filter(fn ($x) => Str::contains($x, ':'))
					->first();
				$cond = self::getConditions($condition);
				if ($cond === null) {
					continue;
				}
				$conditions[Str::beforeLast($condition, ':')] = array_merge($cond, [$condition]);
			}
		}
		return $conditions;
	}

	/**
	 * @return array{0: string, 1: string, 2: string}|null
	 */
	private static function getConditions(string $condition): ?array
	{
		if (!is_string($condition) || empty($condition)) {
			return null;
		}
		[$relation, $cond] = explode(':', $condition);
		if (!is_string($cond) || empty($cond)) {
			return null;
		}
		$condArray = explode(',', $cond);
		if (!isset($condArray[1])) {
			$key = '$primary';
			$operator = '=';
			$value = $condArray[0];
		} elseif (!isset($condArray[2])) {
			$key = $condArray[0];
			$operator = '=';
			$value = $condArray[1];
		} else {
			$key = $condArray[0];
			$operator = $condArray[1];
			$value = $condArray[2];
		}

		return [$key, $operator, $value];
	}
}