<?php

namespace Santwer\Exporter\Tests\Unit\Processor;

use Santwer\Exporter\Processor\VariablesConditionProcessor;
use Santwer\Exporter\Tests\TestCase;

class VariablesConditionProcessorTest extends TestCase
{
    public function test_get_reduced_for_relations_adds_base_relation_when_contains_colon(): void
    {
        $variables = ['orders:15.product_id'];
        $result = VariablesConditionProcessor::getReducedForRelations($variables);
        $this->assertContains('orders', $result);
        $this->assertContains('orders.product_id', $result);
    }

    public function test_get_reduced_for_relations_keeps_variables_without_colon(): void
    {
        $variables = ['name', 'email'];
        $result = VariablesConditionProcessor::getReducedForRelations($variables);
        $this->assertSame($variables, $result);
    }

    public function test_get_related_conditions_extracts_id_condition(): void
    {
        $variables = ['orders:15'];
        $result = VariablesConditionProcessor::getRelatedConditions($variables);
        $this->assertArrayHasKey('orders', $result);
        $this->assertCount(4, $result['orders']);
        $this->assertSame('$primary', $result['orders'][0]);
        $this->assertSame('=', $result['orders'][1]);
        $this->assertSame('15', $result['orders'][2]);
    }

    public function test_get_related_conditions_extracts_field_equals_value(): void
    {
        $variables = ['orders:product_id,4'];
        $result = VariablesConditionProcessor::getRelatedConditions($variables);
        $this->assertArrayHasKey('orders', $result);
        $this->assertSame('product_id', $result['orders'][0]);
        $this->assertSame('=', $result['orders'][1]);
        $this->assertSame('4', $result['orders'][2]);
    }

    public function test_get_related_conditions_extracts_field_operator_value(): void
    {
        $variables = ['orders:status,=,active'];
        $result = VariablesConditionProcessor::getRelatedConditions($variables);
        $this->assertArrayHasKey('orders', $result);
        $this->assertSame('status', $result['orders'][0]);
        $this->assertSame('=', $result['orders'][1]);
        $this->assertSame('active', $result['orders'][2]);
    }

    public function test_get_related_conditions_with_nested_variable(): void
    {
        $variables = ['orders:15.product_id'];
        $result = VariablesConditionProcessor::getRelatedConditions($variables);
        $this->assertArrayHasKey('orders', $result);
    }
}
