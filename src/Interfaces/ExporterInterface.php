<?php

namespace Santwer\Exporter\Interfaces;

use Santwer\Exporter\Eloquent\Builder;
use Santwer\Exporter\Processor\TemplateProcessor;

interface ExporterInterface
{
    public function __construct(string $wordfile);

    /**
     * @param  array  $values
     * @return void
     */
    public function setArrayValues(array $values);

    /**
     * @param  string  $block
     * @param  array   $values
     * @return void
     */
    public function setBlockValues(string $block, array $values);

    /**
     * @param $name
     * @param $value
     * @return void
     */
    public function setValue($name, $value);


    /**
     * @param  string|null  $savepath
     * @return array|false|string|string[]
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     */
    public function getProcessedFile(?string $savepath = null);

    /**
     * @param  string       $format
     * @param  string|null  $savepath
     * @return array|false|string|string[]
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     */
    public function getProcessedConvertedFile(string $format, ?string $savepath = null);

    /**
     * @return TemplateProcessor
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     */
    public function process(): TemplateProcessor;

    /**
     * @param  string|null  $ext
     * @param  bool         $withoutPath
     * @return array|false|string|string[]
     */
    public function getTempFileName(string $ext = null, bool $withoutPath = false);
}