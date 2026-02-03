<?php

namespace Santwer\Exporter\Tests\Unit\Processor;

use Santwer\Exporter\Processor\GlobalVariables;
use Santwer\Exporter\Tests\TestCase;

class GlobalVariablesTest extends TestCase
{
    protected function tearDown(): void
    {
        GlobalVariables::setVariables([]);
        parent::tearDown();
    }

    public function test_set_variable_and_get_global_variables(): void
    {
        GlobalVariables::setVariable('Date', '2025-01-01');
        $vars = GlobalVariables::getGlobalVariables();
        $this->assertArrayHasKey('Date', $vars);
        $this->assertSame('2025-01-01', $vars['Date']);
        $this->assertArrayHasKey(__('new_page'), $vars);
    }

    public function test_set_variables_sets_multiple(): void
    {
        GlobalVariables::setVariables([
            'Time' => '12:00',
            'User' => 'admin',
        ]);
        $vars = GlobalVariables::getGlobalVariables();
        $this->assertSame('12:00', $vars['Time']);
        $this->assertSame('admin', $vars['User']);
    }
}
