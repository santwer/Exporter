<?php

namespace Santwer\Exporter\Tests\Unit\Processor;

use Illuminate\Database\Eloquent\Model;
use Santwer\Exporter\Exportable as ExportableTrait;
use Santwer\Exporter\Processor\ModelProcessor;
use Santwer\Exporter\Tests\TestCase;

class ModelProcessorTest extends TestCase
{
    public function test_check_for_exportable_returns_false_for_null(): void
    {
        $this->assertFalse(ModelProcessor::checkForExportable(null));
    }

    public function test_check_for_exportable_returns_false_for_class_without_trait(): void
    {
        $model = new class extends Model {
            protected $table = 'users';
        };
        $this->assertFalse(ModelProcessor::checkForExportable($model));
    }

    public function test_check_for_exportable_returns_true_for_class_with_exportable_trait(): void
    {
        $model = new class extends Model {
            use ExportableTrait;
            protected $table = 'users';
        };
        $this->assertTrue(ModelProcessor::checkForExportable($model));
    }

    public function test_get_all_relations_returns_empty_for_model_without_relations(): void
    {
        $model = new class extends Model {
            use ExportableTrait;
            protected $table = 'items';
        };
        $relations = ModelProcessor::getAllRelations($model, 'all');
        $this->assertIsArray($relations);
    }
}
