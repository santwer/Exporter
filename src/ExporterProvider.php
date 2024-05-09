<?php

namespace Santwer\Exporter;

use Santwer\Exporter\Facade\WordExport;
use \Illuminate\Support\ServiceProvider;
use Laravel\Tinker\Console\TinkerCommand;
use Santwer\Exporter\Commands\MakeExportCommand;
use Santwer\Exporter\Processor\ExportClassExporter;
use Illuminate\Contracts\Support\DeferrableProvider;
use Santwer\Exporter\Processor\WordTemplateExporter;
use Illuminate\Foundation\Application as LaravelApplication;

class ExporterProvider extends ServiceProvider
{
	/**
	 * Boot the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->commands([
			MakeExportCommand::class,
		]);

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() : void
	{
		$this->app->bind('wordexport', function ($app) {
			return new ExportClassExporter(
				$app->make(WordTemplateExporter::class)
			);
		});
		$this->app->alias('wordexport', WordExport::class);

		$this->mergeConfigFrom(__DIR__.'/../config/exporter.php', 'exporter');

	}


}