<?php

namespace Santwer\Exporter\Interfaces;

use Santwer\Exporter\Eloquent\Builder;
use Santwer\Exporter\Processor\TemplateProcessor;

interface ExporterInterface
{
    public function __construct(string $wordfile);

    public function setArrayValues(array $values): void;

    public function setBlockValues(string $block, array $values): void;

    public function setValue(string $name, mixed $value): void;


    /**
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     */
    public function getProcessedFile(?string $savepath = null): string;

    /**
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     */
    public function getProcessedConvertedFile(string $format, ?string $savepath = null): string;

    /**
     * @return TemplateProcessor
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     */
    public function process(): TemplateProcessor;

    public function getTempFileName(?string $ext = null, bool $withoutPath = false): string;
}