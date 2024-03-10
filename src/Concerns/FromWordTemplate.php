<?php

namespace Santwer\Exporter\Concerns;

interface FromWordTemplate
{
	/**
	 * @return string filepath of file in storage
	 */
	public function wordTemplateFile():string;
}