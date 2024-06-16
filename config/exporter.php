<?php
return [
	'batch_size'            => env('EXPORTER_BATCH_SIZE', 200),
	'removeRelations'       => true,
	'relationsFromTemplate' => false,
	'temp_folder'			=> env('EXPORTER_TEMP_FOLDER', sys_get_temp_dir()),
	'temp_folder_relative'   => env('EXPORTER_TEMP_FOLDER_RELATIVE', false),
	'word2pdf'              => [
		'soffice_prefix' => env('SOFFICE_PATH', ''),
		'command'        => env('SOFFICE_COMMAND_PATH', 'soffice --convert-to pdf --outdir ? ? --headless'),
	],
];