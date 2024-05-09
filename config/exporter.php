<?php
return [
	'batch_size'            => env('EXPORTER_BATCH_SIZE', 200),
	'removeRelations'       => true,
	'relationsFromTemplate' => false,
	'word2pdf'              => [
		'soffice_prefix' => env('SOFFICE_PATH', ''),
		'command'        => env('SOFFICE_COMMAND_PATH', 'soffice --convert-to pdf --outdir ? ? --headless'),
	],
];