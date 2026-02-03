<?php

namespace Santwer\Exporter\Tests\Unit;

use Santwer\Exporter\Tests\TestCase;
use Santwer\Exporter\Writer;

class WriterTest extends TestCase
{
    public function test_formats_returns_docx_html_pdf(): void
    {
        $formats = Writer::formats();
        $this->assertSame([Writer::DOCX, Writer::HTML, Writer::PDF], $formats);
        $this->assertSame('docx', Writer::DOCX);
        $this->assertSame('html', Writer::HTML);
        $this->assertSame('pdf', Writer::PDF);
    }
}
