<?php

namespace Tests\Unit\Services;

use App\Services\FormulaEvaluator;
use Tests\TestCase;

class FormulaEvaluatorTest extends TestCase
{
    private FormulaEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new FormulaEvaluator;
    }

    public function test_evaluates_simple_linear_formula()
    {
        $this->assertEquals(50.0, $this->evaluator->evaluate('x * 2', ['x' => 25, 'max_items' => 100]));
    }

    public function test_evaluates_formula_with_max_items()
    {
        $this->assertEquals(80.0, $this->evaluator->evaluate('(x / max_items) * 100', ['x' => 40, 'max_items' => 50]));
    }

    public function test_evaluates_formula_with_pi()
    {
        $result = $this->evaluator->evaluate('pi * 2', ['x' => 1, 'max_items' => 100]);
        $this->assertEqualsWithDelta(6.28, $result, 0.01);
    }

    public function test_evaluates_power_operator()
    {
        $this->assertEquals(9.0, $this->evaluator->evaluate('x ** 2', ['x' => 3, 'max_items' => 100]));
    }

    public function test_returns_null_for_invalid_syntax()
    {
        $this->assertNull($this->evaluator->evaluate('x + unknown', ['x' => 1, 'max_items' => 100]));
    }

    public function test_returns_null_for_division_by_zero()
    {
        $this->assertNull($this->evaluator->evaluate('x / 0', ['x' => 1, 'max_items' => 100]));
    }

    public function test_validate_returns_true_for_valid_formula()
    {
        $this->assertTrue($this->evaluator->validate('(x / max_items) * 100'));
    }

    public function test_validate_returns_false_for_empty_formula()
    {
        $this->assertFalse($this->evaluator->validate(''));
    }

    public function test_validate_returns_false_for_malicious_input()
    {
        $this->assertFalse($this->evaluator->validate('system("ls")'));
    }

    public function test_rounds_to_two_decimals()
    {
        $this->assertEquals(33.33, $this->evaluator->evaluate('100 / 3', ['x' => 1, 'max_items' => 100]));
    }
}
