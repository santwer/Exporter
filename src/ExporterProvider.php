<?php

declare(strict_types=1);

namespace Santwer\Exporter;

use Santwer\Exporter\Facade\WordExport;
use Illuminate\Support\ServiceProvider;
use Santwer\Exporter\Commands\MakeExportCommand;
use Santwer\Exporter\Processor\ExportClassExporter;
use Santwer\Exporter\Processor\WordTemplateExporter;

class ExporterProvider extends ServiceProvider
{
	/**
	 * Boot the service provider.
	 */
	public function boot(): void
	{
		$this->commands([
			MakeExportCommand::class,
		]);

		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__.'/../config/exporter.php' => config_path('exporter.php'),
			], 'exporter-config');
		}
	}

	/**
	 * Register the service provider.
	 */
	public function register(): void
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