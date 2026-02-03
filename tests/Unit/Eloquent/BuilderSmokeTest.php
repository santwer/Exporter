<?php

namespace Santwer\Exporter\Tests\Unit\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Santwer\Exporter\Eloquent\Builder;
use Santwer\Exporter\Exportable as ExportableTrait;
use Santwer\Exporter\Tests\TestCase;

class BuilderSmokeTest extends TestCase
{
    public function test_model_with_exportable_returns_custom_builder_from_new_query(): void
    {
        $model = new class extends Model {
            use ExportableTrait;
            protected $table = 'users';
        };
        $builder = $model->newQuery();
        $this->assertInstanceOf(Builder::class, $builder);
    }

    public function test_builder_template_returns_self(): void
    {
        $templatePath = $this->createMinimalDocx();
        $model = new class extends Model {
            use ExportableTrait;
            protected $table = 'users';
        };
        $builder = $model->newQuery();
        $result = $builder->template($templatePath);
        $this->assertInstanceOf(Builder::class, $result);
        $this->assertSame($builder, $result);
    }
}
