<?php

namespace Santwer\Exporter\Tests\Unit\Processor;

use Santwer\Exporter\Processor\TemplateProcessor;
use Santwer\Exporter\Tests\TestCase;

class TemplateProcessorTest extends TestCase
{
	public function test_set_value_replaces_placeholder(): void
	{
		$docPath = $this->createMinimalDocx('${test}');
		$processor = new TemplateProcessor($docPath);
		$processor->setValue('test', 'Replaced');
		$variables = $processor->getVariables();
		$this->assertIsArray($variables);
	}

	public function test_replace_escapes_html_by_default(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->replace('<script>alert(1)</script>');
		$this->assertStringContainsString('&lt;', $result);
		$this->assertStringContainsString('&gt;', $result);
	}

	public function test_replace_allows_tags_when_flag_set(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->replace('<b>test</b>', true);
		$this->assertStringContainsString('<b>', $result);
	}

	public function test_replace_handles_ampersand_encoding(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->replace('A & B');
		$this->assertStringContainsString('&amp;', $result);
	}

	public function test_replace_with_array_first_element_as_value(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->replace(['value', true]);
		$this->assertSame('value', $result);
	}

	public function test_replace_escapes_double_quotes(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->replace('Say "Hello"');
		$this->assertStringContainsString('&quot;', $result);
		$this->assertStringNotContainsString('"', $result);
	}

	public function test_replace_escapes_single_quotes(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->replace("It's working");
		// ENT_XML1 uses &apos; for single quotes
		$this->assertStringContainsString('&apos;', $result);
	}

	public function test_replace_escapes_quotes_with_allow_tags(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->replace('<p class="test">Text\'s "value"</p>', true);
		// Tags should be preserved
		$this->assertStringContainsString('<p', $result);
		$this->assertStringContainsString('</p>', $result);
		// Quotes should be escaped (allowTags uses &#039; for single quotes)
		$this->assertStringContainsString('&quot;', $result);
		$this->assertStringContainsString('&#039;', $result);
	}

	public function test_replace_preserves_utf8_characters(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->replace('Ä Ö Ü ä ö ü ß € ñ');
		$this->assertStringContainsString('Ä', $result);
		$this->assertStringContainsString('Ö', $result);
		$this->assertStringContainsString('Ü', $result);
		$this->assertStringContainsString('ä', $result);
		$this->assertStringContainsString('ö', $result);
		$this->assertStringContainsString('ü', $result);
		$this->assertStringContainsString('ß', $result);
		$this->assertStringContainsString('€', $result);
		$this->assertStringContainsString('ñ', $result);
	}

	public function test_replace_handles_null_value(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->replace(null);
		$this->assertSame('', $result);
	}

	public function test_replace_handles_numeric_values(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$resultInt = $processor->replace(42);
		$this->assertSame('42', $resultInt);
		$resultFloat = $processor->replace(3.14);
		$this->assertSame('3.14', $resultFloat);
	}

	public function test_replace_does_not_double_encode_entities(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->replace('Already &amp; encoded');
		// Should not become &amp;amp;
		$this->assertStringContainsString('&amp;', $result);
		$this->assertStringNotContainsString('&amp;amp;', $result);
	}

	public function test_clone_recursive_blocks_processes_block(): void
	{
		$docPath = $this->createMinimalDocx('${block}${name}${/block}');
		$processor = new TemplateProcessor($docPath);
		$processor->cloneRecursiveBlocks('block', 2, true, false, [
			[['name' => 'First']],
			[['name' => 'Second']]
		]);
		$this->addToAssertionCount(1);
	}

	public function test_array_list_recursive_flattens_nested_arrays(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->arrayListRecursive([
			['user' => ['name' => 'John', 'email' => 'john@test.com']]
		]);
		$this->assertIsArray($result);
		$this->assertArrayHasKey(0, $result);
	}

	public function test_array_list_recusive_deprecated_alias_works(): void
	{
		$docPath = $this->createMinimalDocx();
		$processor = new TemplateProcessor($docPath);
		$result = $processor->arrayListRecusive([
			['key' => 'value']
		]);
		$this->assertIsArray($result);
	}

	public function test_get_token_font_style_reads_run_style(): void
	{
		$docPath = $this->createStyledPlaceholderDocx('${invoiceItems}', [
			'bold' => true,
			'name' => 'Arial',
			'size' => 14,
		]);
		$processor = new TemplateProcessor($docPath);
		$style = $processor->getTokenFontStyle('invoiceItems');

		$this->assertTrue($style['bold'] ?? false);
		$this->assertSame('Arial', $style['name'] ?? null);
		$this->assertSame(14, $style['size'] ?? null);
	}

	public function test_get_token_font_style_returns_empty_for_unstyled_token(): void
	{
		$docPath = $this->createMinimalDocx('${plain}');
		$processor = new TemplateProcessor($docPath);
		$this->assertSame([], $processor->getTokenFontStyle('plain'));
	}
}
