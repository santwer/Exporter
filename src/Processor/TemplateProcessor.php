<?php

declare(strict_types=1);

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;
use PhpOffice\PhpWord\Shared\Text;
use Illuminate\Support\Collection;

class TemplateProcessor extends \PhpOffice\PhpWord\TemplateProcessor
{
	public function setValue(
		$search,
		$replace,
		$limit = \PhpOffice\PhpWord\TemplateProcessor::MAXIMUM_REPLACEMENTS_DEFAULT,
		bool $allowTags = false
	): void {
		parent::setValue($search, $this->replace($replace, $allowTags), $limit);
	}

	public function replace(mixed $replace, bool $allowTags = false): string
	{
		// Handle array format [value, allowTags]
		if (is_array($replace)) {
			[$replace, $allowTags] = array_pad($replace, 2, false);
		}

		// Convert non-string types to string
		if ($replace === null) {
			return '';
		}
		if (!is_string($replace)) {
			$replace = (string) $replace;
		}

		// Normalize to UTF-8
		$replace = static::ensureUtf8Encoded($replace);

		// Escape XML-relevant characters
		if (!$allowTags) {
			// Full XML escaping: & < > " '
			$replace = htmlspecialchars($replace, ENT_XML1 | ENT_QUOTES, 'UTF-8', false);
		} else {
			// Only escape & " ' (preserve < > for tags)
			// First escape & (but not existing entities)
			if (method_exists(Str::class, 'replaceMatches')) {
				$replace = Str::replaceMatches(['/&(?![a-zA-Z0-9]+;)/'], '&amp;', $replace);
			} else {
				$replace = preg_replace('/&(?![a-zA-Z0-9]+;)/', '&amp;', $replace);
			}
			// Escape quotes
			$replace = str_replace('"', '&quot;', $replace);
			$replace = str_replace("'", '&#039;', $replace);
		}

		return $replace;
	}

	protected static function ensureUtf8Encoded(mixed $subject): string
	{
		return is_string($subject) || $subject ? Text::toUTF8($subject) : '';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getTokenFontStyle(string $search): array
	{
		$macro = static::ensureMacroCompleted($search);
		$runWhere = $this->findContainingXmlBlockForMacro($macro, 'w:r');
		if (is_array($runWhere)) {
			$runXml = $this->getSlice($runWhere['start'], $runWhere['end']);
			if (preg_match('/<w:rPr>(.*?)<\/w:rPr>/s', $runXml, $matches) && ! empty($matches[1])) {
				return $this->parseRPrToFontStyle($matches[1]);
			}
		}

		$paraWhere = $this->findContainingXmlBlockForMacro($macro, 'w:p');
		if (is_array($paraWhere)) {
			$paraXml = $this->getSlice($paraWhere['start'], $paraWhere['end']);
			if (preg_match('/<w:pPr>.*?<w:rPr>(.*?)<\/w:rPr>.*?<\/w:pPr>/s', $paraXml, $matches) && ! empty($matches[1])) {
				return $this->parseRPrToFontStyle($matches[1]);
			}
		}

		return [];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function parseRPrToFontStyle(string $rPr): array
	{
		$style = [];

		if (preg_match('/<w:rFonts[^>]*\bw:ascii="([^"]+)"/', $rPr, $matches)) {
			$style['name'] = $matches[1];
		}

		if (preg_match('/<w:sz\s+w:val="(\d+)"/', $rPr, $matches)) {
			$style['size'] = (int) $matches[1] / 2;
		}

		if (preg_match('/<w:b(?:\s+w:val="([^"]*)")?(?:\s*\/)?>/s', $rPr, $matches)
			&& ($matches[1] ?? '1') !== '0' && ($matches[1] ?? '1') !== 'false') {
			$style['bold'] = true;
		}

		if (preg_match('/<w:i(?:\s+w:val="([^"]*)")?(?:\s*\/)?>/s', $rPr, $matches)
			&& ($matches[1] ?? '1') !== '0' && ($matches[1] ?? '1') !== 'false') {
			$style['italic'] = true;
		}

		if (preg_match('/<w:color\s+w:val="([^"]+)"/', $rPr, $matches) && $matches[1] !== 'auto') {
			$style['color'] = $matches[1];
		}

		return $style;
	}

	/**
	 * Clone a block.
	 *
	 * @param  string       $blockname
	 * @param  int          $clones                How many time the block should be cloned
	 * @param  bool         $replace
	 * @param  bool         $indexVariables        If true, any variables inside the block will be indexed (postfixed with #1, #2, ...)
	 * @param  array<int, array<string, mixed>>|null  $variableReplacements  Array containing replacements for macros found inside the block to clone
	 *
	 * @return string|null
	 */
	public function cloneRecursiveBlocks(
		string $blockname,
		int $clones = 1,
		bool $replace = true,
		bool $indexVariables = false,
		?array $variableReplacements = null
	): ?string {
		return $this->cloneRecursiveBlock(
			blockname: $blockname,
			clones: $clones,
			replace: $replace,
			indexVariables: $indexVariables,
			variableReplacements: $variableReplacements,
			refXmlBlock: $this->tempDocumentMainPart
		);
	}

	/** @deprecated Use cloneRecursiveBlocks() */
	public function cloneRecrusiveBlocks(
		string $blockname,
		int $clones = 1,
		bool $replace = true,
		bool $indexVariables = false,
		?array $variableReplacements = null
	): ?string {
		return $this->cloneRecursiveBlocks($blockname, $clones, $replace, $indexVariables, $variableReplacements);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function collectListRecursive(Collection $collection, ?string $prekey = null): array
	{
		return $collection->mapWithKeys(function ($value, $key) use ($prekey) {
			$newkey = $prekey ? $prekey.'.'.$key : $key;
			if (!is_array($value) || array_is_list($value)) {
				return [$newkey => $value];
			}

			return array_merge([$key => $value], $this->collectListRecursive(collect($value), $newkey));
		})->toArray();
	}

	/**
	 * @param array<int, mixed> $array
	 * @return array<int, mixed>
	 */
	public function arrayListRecursive(array $array): array
	{
		return array_map(function ($x) {
			if (!is_array($x)) {
				return $x;
			}

			return collect($x)
				->mapWithKeys(function ($value, $key) {
					if (!is_array($value) || array_is_list($value)) {
						return [$key => $value];
					}

					return array_merge([$key => $value], $this->collectListRecursive(collect($value), $key));
				})
				->toArray();
		}, $array);
	}

	/** @deprecated Use arrayListRecursive() */
	public function arrayListRecusive(array $array): array
	{
		return $this->arrayListRecursive($array);
	}

	/**
	 * Clone a block.
	 *
	 * @param  string       $blockname
	 * @param  int          $clones                How many time the block should be cloned
	 * @param  bool         $replace
	 * @param  bool         $indexVariables        If true, any variables inside the block will be indexed (postfixed with #1, #2, ...)
	 * @param  array<int, array<string, mixed>>|null  $variableReplacements  Array containing replacements for macros found inside the block to clone
	 *
	 * @return string|null
	 */
	private function cloneRecursiveBlock(
		string $blockname,
		int $clones = 1,
		bool $replace = true,
		bool $indexVariables = false,
		?array $variableReplacements = null,
		?string &$refXmlBlock = null
	): ?string {
		$xmlBlock = null;
		$matches = [];
		preg_match(
			'/(.*((?s)<w:p\b(?:(?!<w:p\b).)*?\${'.$blockname.'}<\/w:.*?p>))(.*)((?s)<w:p\b(?:(?!<w:p\b).)[^$]*?\${\/'.$blockname.'}<\/w:.*?p>)/is',
			$refXmlBlock,
			$matches
		);

		if (isset($matches[3])) {
			$xmlBlock = $matches[3];
			if ($indexVariables) {
				$cloned = $this->indexClonedVariables($clones, $xmlBlock);
			} elseif ($variableReplacements !== null && is_array($variableReplacements)) {
				$variableReplacementsFirst = $this->filterVariableReplacements($variableReplacements);

				$t = collect($variableReplacementsFirst)->map(fn ($a) => collect($a)->map(function ($x) {
					if (is_array($x)) {
						if (isset($x[1]) && is_bool($x[1])) {
							return $this->replace(...$x);
						}
						return null;
					}
					return $this->replace($x);
				})->toArray())->toArray();

				$cloned = $this->replaceClonedVariables($t, $xmlBlock);

				$variableReplacementsRecrusive = array_map(
					fn ($x) => array_filter($x, fn ($y) => is_array($y)),
					$variableReplacements
				);

				foreach ($cloned as $index => $clone) {
					if (!isset($variableReplacementsRecrusive[$index])) {
						continue;
					}
					$clonedBlockVaribles = $variableReplacementsRecrusive[$index];
					foreach ($clonedBlockVaribles as $block => $variableReplacementsR) {
						$this->cloneRecursiveBlock(
							blockname: $block,
							clones: $clones,
							replace: $replace,
							indexVariables: $indexVariables,
							variableReplacements: $variableReplacementsR,
							refXmlBlock: $cloned[$index]
						);
					}
				}
			} else {
				$cloned = [];
				for ($i = 1; $i <= $clones; $i++) {
					$cloned[] = $xmlBlock;
				}
			}

			if ($replace) {
				$refXmlBlock = str_replace(
					$matches[2].$matches[3].$matches[4],
					implode('', $cloned),
					$refXmlBlock
				);
			}
		}

		return $xmlBlock;
	}

	/**
	 * @param array<int, array<string, mixed>> $variableReplacements
	 * @return array<int, array<string, mixed>>
	 */
	private function filterVariableReplacements(array $variableReplacements): array
	{
		return array_map(
			fn ($x) => array_filter(
				$x,
				fn ($y) => !is_array($y) || (isset($y[1]) && is_bool($y[1]))
			),
			$variableReplacements
		);
	}
}
