<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Compiled;

use Ds\Vector;
use Nette\PhpGenerator\Dumper;
use InvalidArgumentException;
use PCF\Addendum\Validation\RequestValidationConstraintCollection;
use PCF\Addendum\Validation\RequestValidationConstraintInterface;
use PCF\Addendum\Validation\RequestValidationRule;
use PCF\Addendum\Validation\RequestValidationRuleCollection;
use RuntimeException;

final readonly class RequestValidationRuleCodeGenerator
{
    /** @var Vector<RequestValidationConstraintExporterInterface> */
    private Vector $exporters;

    /**
     * @param list<RequestValidationConstraintExporterInterface> $exporters
     */
    public function __construct(array $exporters = [])
    {
        if ($exporters === []) {
            $exporters = [new BuiltinRequestValidationConstraintExporter()];
        }

        foreach ($exporters as $exporter) {
            if (!$exporter instanceof RequestValidationConstraintExporterInterface) {
                throw new InvalidArgumentException('Validation rule code generator accepts only validation constraint exporters');
            }
        }

        $this->exporters = new Vector($exporters);
    }

    public function generateCollection(RequestValidationRuleCollection $rules): string
    {
        if ($rules->isEmpty()) {
            return 'new \\' . RequestValidationRuleCollection::class . '()';
        }

        $items = [];

        foreach ($rules->all() as $rule) {
            $items[] = $this->generateRule($rule);
        }

        return sprintf(
            "new \\%s([\n%s,\n])",
            RequestValidationRuleCollection::class,
            $this->indent(implode(",\n", $items))
        );
    }

    private function generateRule(RequestValidationRule $rule): string
    {
        return sprintf(
            'new \\%s(fieldName: %s, source: \\%s::%s, constraints: %s)',
            RequestValidationRule::class,
            new Dumper()->dump($rule->fieldName),
            ltrim($rule->source::class, '\\'),
            $rule->source->name,
            $this->generateConstraints($rule->constraints)
        );
    }

    private function generateConstraints(RequestValidationConstraintCollection $constraints): string
    {
        if ($constraints->count() === 0) {
            return 'new \\' . RequestValidationConstraintCollection::class . '()';
        }

        $items = [];

        foreach ($constraints->all() as $constraint) {
            $items[] = $this->generateConstraint($constraint);
        }

        return sprintf(
            "new \\%s([\n%s,\n])",
            RequestValidationConstraintCollection::class,
            $this->indent(implode(",\n", $items))
        );
    }

    private function generateConstraint(RequestValidationConstraintInterface $constraint): string
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter->supports($constraint)) {
                return $exporter->export($constraint);
            }
        }

        throw new RuntimeException(sprintf(
            'Cannot compile validation constraint %s. Register a RequestValidationConstraintExporterInterface for this constraint.',
            $constraint::class
        ));
    }

    private function indent(string $code): string
    {
        return preg_replace('/^/m', '    ', $code) ?? $code;
    }
}
