<?php

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
		if (is_array($replace)) {
			[$replace, $allowTags] = array_pad($replace, 2, false);
		}

		if(method_exists(Str::class, 'replaceMatches')) {
			$replace = Str::replaceMatches(['/&(?![a-zA-Z0-9]+;)/'], '&amp;', $replace);
		} else {
			$replace = preg_replace('/&(?![a-zA-Z0-9]+;)/', '&amp;', $replace);
		}
		if (!$allowTags) {
			$replace = Str::replace(['<'], '&lt;', $replace);
			$replace = Str::replace(['>'], '&gt;', $replace);
		}
		parent::setValue($search, $replace, $limit);
	}


	/**
	 * @param ?string  $subject
	 *
	 * @return string
	 */
	protected static function ensureUtf8Encoded($subject)
	{
		return is_string($subject) || $subject ? Text::toUTF8($subject) : '';
	}

	/**
	 * Clone a block.
	 *
	 * @param  string  $blockname
	 * @param  int     $clones                How many time the block should be cloned
	 * @param  bool    $replace
	 * @param  bool    $indexVariables        If true, any variables inside the block will be indexed (postfixed with #1, #2, ...)
	 * @param  array   $variableReplacements  Array containing replacements for macros found inside the block to clone
	 *
	 * @return string|null
	 */
	public function cloneRecrusiveBlocks(
		$blockname,
		$clones = 1,
		$replace = true,
		$indexVariables = false,
		$variableReplacements = null
	) {
		return $this->cloneRecrusiveBlock($blockname,
			$clones, $replace, $indexVariables, $variableReplacements,
			$this->tempDocumentMainPart);
	}

	private function collectListRecusive(Collection $collection, $prekey = null): array
	{
		return $collection->mapWithKeys(function ($value, $key) use ($prekey) {
			$newkey = $prekey ? $prekey.'.'.$key : $key;
			if (!is_array($value) || array_is_list($value)) {
				return [$newkey => $value];
			}

			return array_merge([$key => $value], $this->collectListRecusive(collect($value), $newkey));
		})->toArray();
	}

	public function arrayListRecusive(array $array): array
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

					return array_merge([$key => $value], $this->collectListRecusive(collect($value),$key));
				})
				->toArray();
		}, $array);
	}

	/**
	 * Clone a block.
	 *
	 * @param  string  $blockname
	 * @param  int     $clones                How many time the block should be cloned
	 * @param  bool    $replace
	 * @param  bool    $indexVariables        If true, any variables inside the block will be indexed (postfixed with #1, #2, ...)
	 * @param  array   $variableReplacements  Array containing replacements for macros found inside the block to clone
	 *
	 * @return string|null
	 */
	public function cloneRecrusiveBlock(
		$blockname,
		$clones = 1,
		$replace = true,
		$indexVariables = false,
		$variableReplacements = null,
		&$refXmlBlock = null
	) {
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
				$variableReplacementsFirst = array_map(function ($x) {
					return array_filter($x, function ($y) {
						return !is_array($y);
					});
				}, $variableReplacements);

				$cloned = $this->replaceClonedVariables($variableReplacementsFirst, $xmlBlock);

				$variableReplacementsRecrusive = array_map(function ($x) {
					return array_filter($x, function ($y) {
						return is_array($y);
					});
				}, $variableReplacements);

				foreach ($cloned as $index => $clone) {
					if (!isset($variableReplacementsRecrusive[$index])) {
						continue;
					}
					$clonedBlockVaribles = $variableReplacementsRecrusive[$index];
					foreach ($clonedBlockVaribles as $block => $variableReplacementsR) {
						$this->cloneRecrusiveBlock($block,
							$clones,
							$replace,
							$indexVariables,
							$variableReplacementsR,
							$cloned[$index]);
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
}