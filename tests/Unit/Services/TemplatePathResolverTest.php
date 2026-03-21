<?php

namespace Santwer\Exporter\Tests\Unit\Services;

use Santwer\Exporter\Exceptions\NoFileException;
use Santwer\Exporter\Services\TemplatePathResolver;
use Santwer\Exporter\Tests\TestCase;

class TemplatePathResolverTest extends TestCase
{
	private array $cleanup = [];

	protected function tearDown(): void
	{
		foreach ($this->cleanup as $file) {
			@unlink($file);
		}
		parent::tearDown();
	}

	public function test_resolve_with_absolute_path_returns_path(): void
	{
		$path = $this->createMinimalDocx();
		$resolver = new TemplatePathResolver();
		$resolved = $resolver->resolve($path);
		$this->assertSame($path, $resolved);
	}

	public function test_resolve_with_storage_path(): void
	{
		$fileName = 'template-'.uniqid().'.docx';
		$path = storage_path($fileName);
		file_put_contents($path, 'test');
		$this->cleanup[] = $path;

		$resolver = new TemplatePathResolver();
		$resolved = $resolver->resolve($fileName);
		$this->assertSame($path, $resolved);
	}

	public function test_resolve_with_storage_app_path(): void
	{
		$fileName = 'template-'.uniqid().'.docx';
		$path = storage_path('app/'.$fileName);
		file_put_contents($path, 'test');
		$this->cleanup[] = $path;

		$resolver = new TemplatePathResolver();
		$resolved = $resolver->resolve($fileName);
		$this->assertSame($path, $resolved);
	}

	public function test_resolve_throws_exception_when_not_found(): void
	{
		$resolver = new TemplatePathResolver();
		$this->expectException(NoFileException::class);
		$resolver->resolve('non-existent-'.uniqid().'.docx');
	}
}
