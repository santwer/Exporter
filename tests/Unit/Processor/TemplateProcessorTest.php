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
}
