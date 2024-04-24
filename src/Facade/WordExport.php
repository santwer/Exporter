<?php

namespace Santwer\Exporter\Facade;

use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\Bus\PendingDispatch;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @method static BinaryFileResponse download(object $export, string $fileName, string $writerType = null, array $headers = [])
 * @method static false|string store(object $export, string $filePath,string $fileName, string $disk = null, string $writerType = null, $diskOptions = [])
 * @method static PendingDispatch queue(object $export, string $filePath, string $disk = null, string $writerType = null, $diskOptions = [])
 */
class WordExport extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'wordexport';
	}
}