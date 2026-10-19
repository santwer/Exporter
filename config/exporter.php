<?php
return [
	'batch_size'            => env('EXPORTER_BATCH_SIZE', 200),
	'removeRelations'       => true,
	'relationsFromTemplate' => false,
	'temp_folder'			=> env('EXPORTER_TEMP_FOLDER', sys_get_temp_dir()),
	'temp_folder_relative'   => env('EXPORTER_TEMP_FOLDER_RELATIVE', false),
	// Check the processed XML before saving and log a warning if Word could not open the file
	'validate_xml'          => env('EXPORTER_VALIDATE_XML', true),
	'word2pdf'              => [
		'soffice_prefix' => env('SOFFICE_PATH', ''),
		'command'        => env('SOFFICE_COMMAND_PATH', 'soffice --convert-to pdf --outdir ? ? --headless'),
		// Every conversion gets its own LibreOffice user profile, so parallel processes don't share one instance
		'isolate_instances' => env('SOFFICE_ISOLATE_INSTANCES', true),
		// Seconds per converted file, null or 0 disables the timeout
		'timeout'        => env('SOFFICE_TIMEOUT', 120),
	],
];