<?php

namespace Santwer\Exporter\Exceptions;

use Santwer\Exporter\Helpers\DocxXmlDiagnostics;

class PDFConversionException extends \RuntimeException
{
	/**
	 * @param  list<array<string, mixed>>  $docxFindings
	 */
	public function __construct(
		string $message,
		int $code = 0,
		?\Throwable $previous = null,
		public readonly ?string $docxPath = null,
		public readonly array $docxFindings = [],
		public readonly string $sofficeOutput = '',
		public readonly string $sofficeError = '',
	) {
		parent::__construct($message, $code, $previous);
	}

	public static function fromProcess(string $output, string $error): self
	{
		return new self(
			message: trim("PDF conversion failed.\n\nLibreOffice error:\n{$error}\n\nLibreOffice output:\n{$output}"),
			sofficeOutput: $output,
			sofficeError: $error,
		);
	}

	public static function fromDocxConversion(string $docxPath, string $output, string $error): self
	{
		$findings = DocxXmlDiagnostics::analyze($docxPath);

		return new self(
			message: self::buildDocxConversionMessage($findings, $output, $error),
			docxPath: $docxPath,
			docxFindings: $findings,
			sofficeOutput: $output,
			sofficeError: $error,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function context(): array
	{
		return [
			'docx_path' => $this->docxPath,
			'finding_count' => count($this->docxFindings),
			'findings' => $this->docxFindings,
			'soffice_output' => $this->sofficeOutput,
			'soffice_error' => $this->sofficeError,
		];
	}

	public function report(): bool
	{
		if (! app()->bound('log')) {
			return true;
		}

		logger()->error($this->formatForLog(), $this->context());

		return false;
	}

	public function summary(): string
	{
		if ($this->docxFindings !== []) {
			$count = count($this->docxFindings);

			return sprintf(
				'%d DOCX XML %s detected — likely invalid placeholder content.',
				$count,
				$count === 1 ? 'issue' : 'issues'
			);
		}

		if ($this->sofficeError !== '' || $this->sofficeOutput !== '') {
			return 'LibreOffice could not convert the document. No obvious XML issues were found in the DOCX.';
		}

		return 'The document could not be converted to PDF.';
	}

	/**
	 * @param  list<array<string, mixed>>  $findings
	 */
	private static function buildDocxConversionMessage(array $findings, string $output, string $error): string
	{
		$count = count($findings);
		$summary = $count > 0
			? sprintf(
				'%d DOCX XML %s detected — likely invalid placeholder content.',
				$count,
				$count === 1 ? 'issue' : 'issues'
			)
			: 'LibreOffice could not convert the document. No obvious XML issues were found in the DOCX.';

		$sections = array_filter([
			'PDF conversion failed',
			$summary,
			DocxXmlDiagnostics::formatReport($findings),
			$error !== '' ? "LibreOffice error:\n{$error}" : null,
			$output !== '' ? "LibreOffice output:\n{$output}" : null,
		]);

		return implode("\n\n", $sections);
	}

	private function formatForLog(): string
	{
		$lines = [
			'PDF conversion failed',
			$this->summary(),
		];

		if ($this->docxPath !== null) {
			$lines[] = "DOCX file: {$this->docxPath}";
		}

		if ($this->sofficeError !== '') {
			$lines[] = "LibreOffice error: {$this->sofficeError}";
		}

		if ($this->sofficeOutput !== '') {
			$lines[] = "LibreOffice output: {$this->sofficeOutput}";
		}

		foreach ($this->docxFindings as $index => $finding) {
			$lines[] = '';
			$lines[] = sprintf('--- Issue %d: %s ---', $index + 1, $finding['part']);
			$lines[] = (string) $finding['message'];

			if (! empty($finding['line'])) {
				$lines[] = sprintf(
					'Location: line %d, column %d',
					(int) $finding['line'],
					(int) ($finding['column'] ?? 0)
				);
			}

			if (! empty($finding['text'])) {
				$lines[] = 'Suspicious content: '.$finding['text'];
			}

			if (! empty($finding['snippet'])) {
				$lines[] = 'XML context: '.$finding['snippet'];
			}
		}

		if ($this->docxFindings !== []) {
			$lines[] = '';
			$lines[] = 'Hint: escape special characters in placeholder values unless allowTags is enabled.';
		}

		return implode("\n", $lines);
	}
}
