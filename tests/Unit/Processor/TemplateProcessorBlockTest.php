<?php

namespace Santwer\Exporter\Tests\Unit\Processor;

use DOMDocument;
use DOMXPath;
use Santwer\Exporter\Processor\TemplateProcessor;
use Santwer\Exporter\Tests\TestCase;
use ZipArchive;

class TemplateProcessorBlockTest extends TestCase
{
	private const NAMESPACES = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
		.' xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"'
		.' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
		.' xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"'
		.' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
		.' xmlns:v="urn:schemas-microsoft-com:vml"'
		.' mc:Ignorable="wps"';

	public function test_block_is_cloned_for_each_item(): void
	{
		$xml = $this->processBlock(
			$this->p('Kopf').$this->p('${teilnehmer}').$this->p('Name: ${name}').$this->p('${/teilnehmer}').$this->p('Fuss'),
			[['name' => 'Anna'], ['name' => 'Bob']]
		);

		$this->assertSame(['Kopf', 'Name: Anna', 'Name: Bob', 'Fuss'], $this->paragraphTexts($xml));
	}

	public function test_block_without_items_is_removed(): void
	{
		$xml = $this->processBlock(
			$this->p('Kopf').$this->p('${teilnehmer}').$this->p('Name: ${name}').$this->p('${/teilnehmer}').$this->p('Fuss'),
			[]
		);

		$this->assertSame(['Kopf', 'Fuss'], $this->paragraphTexts($xml));
	}

	public function test_block_markers_with_formatting_are_removed(): void
	{
		$marker = fn (string $text) => '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>'.$text.'</w:t></w:r></w:p>';
		$xml = $this->processBlock(
			$marker('${teilnehmer}').$this->p('${name}').$marker('${/teilnehmer}'),
			[['name' => 'Anna']]
		);

		$this->assertSame(['Anna'], $this->paragraphTexts($xml));
	}

	public function test_nested_blocks_are_cloned(): void
	{
		$xml = $this->processBlock(
			$this->p('${teilnehmer}').$this->p('${name}').$this->p('${kurse}').$this->p('- ${titel}').$this->p('${/kurse}').$this->p('${/teilnehmer}'),
			[
				['name' => 'Anna', 'kurse' => [['titel' => 'A1'], ['titel' => 'A2']]],
				['name' => 'Bob', 'kurse' => [['titel' => 'B1']]],
			]
		);

		$this->assertSame(['Anna', '- A1', '- A2', 'Bob', '- B1'], $this->paragraphTexts($xml));
	}

	/**
	 * PNXII-19242: a shape anchored in the paragraph of the start marker.
	 */
	public function test_shape_anchored_after_start_marker_keeps_document_valid(): void
	{
		$marker = '<w:p><w:r><w:t>${teilnehmer}</w:t></w:r>'.$this->shapeRun().'</w:p>';
		$xml = $this->processBlock(
			$this->p('Kopf').$marker.$this->p('Name: ${name}').$this->p('${/teilnehmer}'),
			[['name' => 'Anna'], ['name' => 'Bob']]
		);

		$this->assertShapePreserved($xml);
		$this->assertSame(['Kopf', '', 'Name: Anna', 'Name: Bob'], $this->paragraphTexts($xml));
	}

	/**
	 * PNXII-19242: a text box (with its own w:p) anchored before the start marker.
	 */
	public function test_text_box_anchored_before_start_marker_keeps_document_valid(): void
	{
		$marker = '<w:p>'.$this->shapeRun(withTextBox: true).'<w:r><w:t>${teilnehmer}</w:t></w:r></w:p>';
		$xml = $this->processBlock(
			$this->p('Kopf').$marker.$this->p('Name: ${name}').$this->p('${/teilnehmer}'),
			[['name' => 'Anna']]
		);

		$this->assertShapePreserved($xml);
		$this->assertSame(['Kopf', '', 'Name: Anna'], $this->paragraphTexts($xml));
	}

	/**
	 * PNXII-19242: a shape anchored in the paragraph of the end marker.
	 */
	public function test_shape_anchored_at_end_marker_keeps_document_valid(): void
	{
		$marker = '<w:p><w:r><w:t>${/teilnehmer}</w:t></w:r>'.$this->shapeRun().'</w:p>';
		$xml = $this->processBlock(
			$this->p('${teilnehmer}').$this->p('Name: ${name}').$marker.$this->p('Fuss'),
			[['name' => 'Anna']]
		);

		$this->assertShapePreserved($xml);
		$this->assertSame(['Name: Anna', '', 'Fuss'], $this->paragraphTexts($xml));
	}

	/**
	 * @param  array<int, array<string, mixed>>  $items
	 */
	private function processBlock(string $body, array $items): string
	{
		$processor = new TemplateProcessor($this->createDocx($body));
		$processor->cloneRecursiveBlocks('teilnehmer', 0, true, false, $items);

		$out = sys_get_temp_dir().'/exporter-block-out-'.uniqid().'.docx';
		$processor->saveAs($out);

		$zip = new ZipArchive();
		$zip->open($out);
		$xml = (string) $zip->getFromName('word/document.xml');
		$zip->close();
		unlink($out);

		return $xml;
	}

	private function createDocx(string $body): string
	{
		$path = $this->createMinimalDocx();
		$zip = new ZipArchive();
		$zip->open($path);
		$zip->addFromString(
			'word/document.xml',
			'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			.'<w:document '.self::NAMESPACES.'><w:body>'.$body.'<w:sectPr/></w:body></w:document>'
		);
		$zip->close();

		return $path;
	}

	private function p(string $text): string
	{
		return '<w:p><w:r><w:t xml:space="preserve">'.$text.'</w:t></w:r></w:p>';
	}

	/**
	 * Word's markup for a floating rectangle (the "left stripe"), incl. VML fallback.
	 */
	private function shapeRun(bool $withTextBox = false): string
	{
		$textBox = $withTextBox
			? '<wps:txbx><w:txbxContent><w:p><w:r><w:t>Streifen</w:t></w:r></w:p></w:txbxContent></wps:txbx>'
			: '';

		return '<w:r><mc:AlternateContent><mc:Choice Requires="wps"><w:drawing>'
			.'<wp:anchor distT="0" distB="0" distL="0" distR="0" simplePos="0" relativeHeight="1" behindDoc="1" locked="0" layoutInCell="1" allowOverlap="1">'
			.'<wp:simplePos x="0" y="0"/><wp:positionH relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionH>'
			.'<wp:positionV relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionV>'
			.'<wp:extent cx="360000" cy="10692000"/><wp:effectExtent l="0" t="0" r="0" b="0"/><wp:wrapNone/>'
			.'<wp:docPr id="1" name="Streifen"/><wp:cNvGraphicFramePr/>'
			.'<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
			.'<a:graphicData uri="http://schemas.microsoft.com/office/word/2010/wordprocessingShape">'
			.'<wps:wsp><wps:cNvSpPr/><wps:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="360000" cy="10692000"/></a:xfrm>'
			.'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:solidFill><a:srgbClr val="003366"/></a:solidFill></wps:spPr>'
			.$textBox
			.'<wps:bodyPr/></wps:wsp></a:graphicData></a:graphic></wp:anchor></w:drawing></mc:Choice>'
			.'<mc:Fallback><w:pict><v:rect style="position:absolute;width:28.35pt;height:841.9pt" fillcolor="#036" stroked="f"/></w:pict></mc:Fallback>'
			.'</mc:AlternateContent></w:r>';
	}

	private function assertShapePreserved(string $xml): void
	{
		$dom = new DOMDocument();
		$previous = libxml_use_internal_errors(true);
		libxml_clear_errors();
		$loaded = $dom->loadXML($xml);
		$errors = array_map(fn ($e) => trim($e->message), libxml_get_errors());
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		$this->assertTrue($loaded, 'document.xml is not well-formed');
		$this->assertSame([], $errors, 'document.xml has XML/namespace errors');

		$xpath = $this->xpath($dom);
		$this->assertSame(1, $xpath->query('//mc:AlternateContent/mc:Choice//a:xfrm')->length, 'shape geometry must stay inside mc:Choice');
		$this->assertSame(0, $xpath->query('//a:xfrm[not(ancestor::mc:Choice)]')->length);
		$this->assertSame(1, $xpath->query('//mc:Fallback/w:pict')->length, 'VML fallback must be preserved');
	}

	/**
	 * Texts of the top-level body paragraphs.
	 *
	 * @return list<string>
	 */
	private function paragraphTexts(string $xml): array
	{
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$xpath = $this->xpath($dom);

		$texts = [];
		foreach ($xpath->query('/w:document/w:body/w:p') as $paragraph) {
			$text = '';
			foreach ($xpath->query('./w:r/w:t', $paragraph) as $t) {
				$text .= $t->textContent;
			}
			$texts[] = $text;
		}

		return $texts;
	}

	private function xpath(DOMDocument $dom): DOMXPath
	{
		$xpath = new DOMXPath($dom);
		$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
		$xpath->registerNamespace('mc', 'http://schemas.openxmlformats.org/markup-compatibility/2006');
		$xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

		return $xpath;
	}
}
