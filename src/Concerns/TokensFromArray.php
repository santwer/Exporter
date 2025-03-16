<?php

namespace Santwer\Exporter\Concerns;

use Illuminate\Database\Eloquent\Collection;

interface TokensFromArray
{
	public function blockName():string|array;
	public function items():array;

	public function itemTokens($item) : array;
}