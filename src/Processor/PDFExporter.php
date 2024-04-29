<?php

namespace Santwer\Exporter\Processor;

use Illuminate\Support\Str;

class PDFExporter
{
    protected static $baseCommenad = 'soffice';
    protected static $outPutFile = '';


    public static function html2Pdf(string $html, ?string $path = null)
    {
        $htmlfile = tempnam(sys_get_temp_dir(), 'php_we_pdf');

        $handler = fopen($htmlfile, "w");
        fwrite($handler, $html);
        fclose($handler);

        if($path !== null) {
            $return = exec(self::getCommand('html2pdfPath', $path, $htmlfile));
        } else {
            $return = exec(self::getCommand('html2pdf', $htmlfile));
        }
        if(!self::checkReturnValue($return)) {
            throw new \Exception($return);
        }
        return self::$outPutFile;
    }

    /**
     * @param $docX
     * @param $path
     * @throws \Exception
     */
    public static function docxToPdf($docX, $path = null)
    {
        if($path !== null) {
            $return = exec(self::getCommand('docx2pdfPath', $path, $docX));
        } else {
            $return = exec(self::getCommand('docx2pdf', $docX));
        }
        if(!self::checkReturnValue($return)) {
            throw new \Exception($return);
        }
		if($path !== null) {
			//get file extension
			$fileext = pathinfo($docX, PATHINFO_EXTENSION);
			return Str::replace('.'.$fileext, '.pdf', $docX);
		}

        return $path . pathinfo($docX, PATHINFO_FILENAME) . '.pdf';
    }

    /**
     * @param $type
     * @param mixed ...$args
     * @return string
     */
    private static function getCommand($type, ...$args)
    {
        $query = str_replace(['?'], ['"%s"'], self::commands($type));
        $query = vsprintf($query, $args);
        return self::$baseCommenad . ' ' . $query;
    }


    /**
     * @param $type
     * @return string
     */
    private static function commands($type)
    {
        switch ($type) {
            case 'docx2pdf':
            case 'html2pdf':
                return '--convert-to pdf ? --headless';
            case 'docx2pdfPath':
            case 'html2pdfPath':
                return '--convert-to pdf --outdir ? ? --headless';
            default:
                return $type;
        }
    }


    private static function checkReturnValue($value) {
        $checkConvert = explode(' ', $value);
        if(isset($checkConvert[3])) {
            self::$outPutFile = $checkConvert[3];
        }
        if($checkConvert[0] === 'convert') {
            return true;
        } else {
            return false;
        }
    }
}