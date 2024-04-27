<?php

namespace Santwer\Exporter\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

interface TokensFromModel
{

	public function model() : Model;
}