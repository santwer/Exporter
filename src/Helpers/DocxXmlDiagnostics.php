<?php

declare(strict_types=1);

namespace Santwer\Exporter\Helpers;

use DOMDocument;
use ZipArchive;

final class DocxXmlDiagnostics
{
	private const XML_PART_PATTERN = '/^word\/(document|header\d+|footer\d+|footnotes|endnotes)\.xml$/';

	/**
	 * @return list<array{
	 *     part: string,
	 *     type: 'parse_error'|'suspicious_text',
	 *     message: string,
	 *     line?: int,
	 *     column?: int,
	 *     snippet?: string,
	 *     text?: string,
	 * }>
	 */
	public static function analyze(string $docxPath): array
	{
		if (! is_file($docxPath)) {
			return [[
				'part' => $docxPath,
				'type' => 'parse_error',
				'message' => 'DOCX file not found.',
			]];
		}

		$zip = new ZipArchive();
		if ($zip->open($docxPath) !== true) {
			return [[
				'part' => $docxPath,
				'type' => 'parse_error',
				'message' => 'Could not open DOCX as ZIP archive.',
			]];
		}

		$findings = [];

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = $zip->getNameIndex($i);
			if (! is_string($name) || ! preg_match(self::XML_PART_PATTERN, $name)) {
				continue;
			}

			$xml = $zip->getFromIndex($i);
			if (! is_string($xml) || $xml === '') {
				continue;
			}

			$findings = array_merge($findings, self::analyzePart($name, $xml));
		}

		$zip->close();

		return self::deduplicateFindings($findings);
	}

	/**
	 * @param  list<array<string, mixed>>  $findings
	 */
	public static function formatReport(array $findings): string
	{
		if ($findings === []) {
			return 'No obvious XML issues found in the DOCX. LibreOffice did not return a detailed error — check temp directory write permissions and SOFFICE_PATH.';
		}

		$lines = ['DOCX XML analysis (likely cause of the failed PDF export):'];

		foreach ($findings as $index => $finding) {
			$lines[] = sprintf('%d) [%s] %s', $index + 1, $finding['part'], $finding['message']);

			if (! empty($finding['line'])) {
				$lines[] = sprintf(
					'   Line %d, column %d',
					(int) $finding['line'],
					(int) ($finding['column'] ?? 0)
				);
			}

			if (! empty($finding['text'])) {
				$lines[] = '   Suspicious token/content: '.$finding['text'];
			}

			if (! empty($finding['snippet'])) {
				$lines[] = '   XML context: '.$finding['snippet'];
			}
		}

		$lines[] = 'Hint: special characters (<, >, &, quotes) must be escaped in placeholder values unless allowTags is enabled.';

		return implode("\n", $lines);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private static function analyzePart(string $part, string $xml): array
	{
		$findings = [];

		libxml_use_internal_errors(true);
		libxml_clear_errors();

		$document = new DOMDocument();
		$document->preserveWhiteSpace = true;
		$valid = @$document->loadXML($xml, LIBXML_NONET);

		foreach (libxml_get_errors() as $error) {
			if ($error->level === LIBXML_ERR_WARNING && $valid) {
				continue;
			}

			$line = (int) $error->line;
			$column = (int) $error->column;

			$findings[] = [
				'part' => $part,
				'type' => 'parse_error',
				'message' => trim($error->message),
				'line' => $line,
				'column' => $column,
				'snippet' => self::snippetAtLine($xml, $line),
			];
		}

		libxml_clear_errors();

		foreach (self::findSuspiciousTextRuns($xml) as $text) {
			$findings[] = [
				'part' => $part,
				'type' => 'suspicious_text',
				'message' => self::describeSuspiciousText($text),
				'text' => self::truncate($text, 220),
				'snippet' => self::snippetContaining($xml, $text),
			];
		}

		return $findings;
	}

	/**
	 * @return list<string>
	 */
	private static function findSuspiciousTextRuns(string $xml): array
	{
		if (! preg_match_all('/<w:t(?:\s[^>]*)?>(.*?)<\/w:t>/s', $xml, $matches)) {
			return [];
		}

		$suspicious = [];

		foreach ($matches[1] as $rawText) {
			if (! self::isSuspiciousRunContent($rawText)) {
				continue;
			}

			$suspicious[] = html_entity_decode($rawText, ENT_XML1 | ENT_QUOTES, 'UTF-8');
		}

		return array_values(array_unique($suspicious));
	}

	private static function isSuspiciousRunContent(string $rawText): bool
	{
		if ($rawText === '') {
			return false;
		}

		if (preg_match('/[<>]/', html_entity_decode($rawText, ENT_XML1 | ENT_QUOTES, 'UTF-8'))) {
			return true;
		}

		return (bool) preg_match('/&(?!(?:amp|lt|gt|quot|apos|#\\d+|#x[0-9a-fA-F]+);)/', $rawText);
	}

	private static function describeSuspiciousText(string $text): string
	{
		$issues = [];

		if (preg_match('/[<>]/', $text)) {
			$issues[] = 'unescaped angle brackets (< or >)';
		}

		if (preg_match('/&/', $text)) {
			$issues[] = 'unescaped ampersand (&)';
		}

		if ($issues === []) {
			return 'Suspicious text found in w:t run.';
		}

		return 'Suspicious text: '.implode(', ', $issues);
	}

	private static function snippetAtLine(string $xml, int $line): string
	{
		$lines = preg_split("/\r\n|\n|\r/", $xml) ?: [];

		return self::truncate(trim($lines[$line - 1] ?? ''), 260);
	}

	private static function snippetContaining(string $xml, string $text): string
	{
		$needle = self::truncate($text, 80);
		$position = mb_strpos($xml, $needle);

		if ($position === false) {
			$encodedNeedle = htmlspecialchars($needle, ENT_XML1 | ENT_QUOTES, 'UTF-8', false);
			$position = mb_strpos($xml, $encodedNeedle);
		}

		if ($position === false) {
			return self::truncate($text, 260);
		}

		$start = max(0, $position - 90);
		$length = min(mb_strlen($xml) - $start, 220);

		return self::truncate(trim(mb_substr($xml, $start, $length)), 260);
	}

	private static function truncate(string $value, int $maxLength): string
	{
		$value = preg_replace('/\s+/u', ' ', $value) ?? $value;

		if (mb_strlen($value) <= $maxLength) {
			return $value;
		}

		return mb_substr($value, 0, $maxLength - 1).'…';
	}

	/**
	 * @param  list<array<string, mixed>>  $findings
	 * @return list<array<string, mixed>>
	 */
	private static function deduplicateFindings(array $findings): array
	{
		$unique = [];

		foreach ($findings as $finding) {
			$key = implode('|', [
				$finding['part'],
				$finding['type'],
				$finding['message'],
				(string) ($finding['text'] ?? ''),
				(string) ($finding['line'] ?? ''),
			]);

			$unique[$key] = $finding;
		}

		return array_values($unique);
	}
}
