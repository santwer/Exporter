<?php

namespace Santwer\Exporter\Concerns;


use Illuminate\Support\Collection;

interface TokensFromCollection
{

	public function blockName():string;
	public function items():Collection;

	public function itemTokens($item) : array;
}