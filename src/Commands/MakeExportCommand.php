<?php

namespace Santwer\Exporter\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'make:word')]
class MakeExportCommand extends GeneratorCommand
{
	protected $name = 'make:word';

	protected $description = 'Create a new WordExport command';

	protected $type = 'Word Export';

	/**
	 * Replace the class name for the given stub.
	 *
	 * @param  string  $stub
	 * @param  string  $name
	 * @return string
	 */
	protected function replaceClass($stub, $name)
	{
		$stub = parent::replaceClass($stub, $name);

		$command = $this->option('command') ?: 'app:'.Str::of($name)->classBasename()->kebab()->value();

		return str_replace(['dummy:command', '{{ command }}'], $command, $stub);
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		$relativePath = '/stubs/wordexport.stub';

		return file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
			? $customPath
			: __DIR__.'/..'.$relativePath;
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return $rootNamespace.'\Http\Export';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['name', InputArgument::REQUIRED, 'The name of the word export'],
		];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the word export already exists'],
			['command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that will be used to invoke the class'],
		];
	}
}
