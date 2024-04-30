<?php

namespace Santwer\Exporter\Concerns;

use Santwer\Exporter\Processor\Exporter;

interface WithWordProcessor
{
	public function wordProcessor(Exporter $exporter): void;
}