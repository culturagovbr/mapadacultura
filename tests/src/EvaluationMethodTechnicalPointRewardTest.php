<?php

if (file_exists(__DIR__ . '/bootstrap.php')) {
    require_once __DIR__ . '/bootstrap.php';
} else {
    $root = is_dir(__DIR__ . '/../vendor') ? dirname(__DIR__) : dirname(__DIR__, 2);
    abstract class EvaluationMethodTechnicalPointRewardTestEvaluationMethodStub {}
    class_alias(EvaluationMethodTechnicalPointRewardTestEvaluationMethodStub::class, 'MapasCulturais\EvaluationMethod');
    require_once $root . '/src/modules/EvaluationMethodTechnical/Module.php';
}

class EvaluationMethodTechnicalPointRewardTest extends \PHPUnit\Framework\TestCase
{
    private function getRuleMatch($rule, $registrationValue): array
    {
        $moduleClass = \EvaluationMethodTechnical\Module::class;
        $module = (new \ReflectionClass($moduleClass))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($moduleClass, 'getPointRewardRuleMatch');
        $method->setAccessible(true);

        return $method->invoke($module, $rule, $registrationValue);
    }

    public function testOnlySelectedMultipleChoiceOptionMatchesPointRewardRule(): void
    {
        $moduleClass = \EvaluationMethodTechnical\Module::class;

        $this->assertTrue(
            method_exists($moduleClass, 'getPointRewardRuleMatch'),
            'O método de correspondência do bônus deve existir'
        );

        $selectedValues = ['Opção A'];
        $totalPercent = 0;

        foreach (['Opção A', 'Opção B', 'Opção C', 'Opção D', 'Opção E'] as $option) {
            $rule = (object) [
                'eligibleValues' => [$option],
                'fieldPercent' => 5,
            ];

            $match = $this->getRuleMatch($rule, $selectedValues);

            if ($match['applied']) {
                $totalPercent += $rule->fieldPercent;
            }
        }

        $this->assertSame(5, $totalPercent);
    }

    public function testExistingValueRulesContinueMatching(): void
    {
        $selectRule = (object) ['value' => (object) ['Opção A' => 'true']];
        $booleanRule = (object) ['value' => false];

        $this->assertTrue($this->getRuleMatch($selectRule, 'Opção A')['applied']);
        $this->assertTrue($this->getRuleMatch($booleanRule, false)['applied']);
    }
}
