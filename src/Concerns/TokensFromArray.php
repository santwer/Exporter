<?php

namespace Santwer\Exporter\Concerns;

use Illuminate\Database\Eloquent\Collection;

interface TokensFromArray
{
	public function items():array;

	public function itemTokens($item) : array;
}