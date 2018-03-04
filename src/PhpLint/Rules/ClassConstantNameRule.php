<?php
declare(strict_types=1);

namespace PhpLint\Rules;

use PhpLint\Ast\AstNode;
use PhpLint\Ast\AstNodeType;
use PhpLint\Linter\LintContext;
use PhpLint\Linter\LintResult;

class ClassConstantNameRule extends AbstractRule
{
    const RULE_IDENTIFIER = 'class-constant-name';
    const MESSAGE_ID_CLASS_CONSTANT_NAME_NOT_ALL_UPPER_CASE = 'classConstantNameNotAllUpperCase';

    public function __construct()
    {
        $this->setDescription(
            RuleDescription::forRuleWithIdentifier(self::RULE_IDENTIFIER)
                ->explainedBy('Enforces that all class constants must be declared in all upper case with underscore separators, e.g. \'ALL_UPPER_CASE\'.')
                ->usingMessages([
                    self::MESSAGE_ID_CLASS_CONSTANT_NAME_NOT_ALL_UPPER_CASE => 'Class constants must be declared in all upper case with underscore separators.',
                ])
                ->rejectsExamples([
                    RuleDescription::createPhpCodeExample(
                        'class AnyClass',
                        '{',
                        "\t" . 'const myConst = 0;',
                        '}'
                    ),
                    RuleDescription::createPhpCodeExample(
                        'class AnyClass',
                        '{',
                        "\t" . 'const my_const = 0;',
                        '}'
                    ),
                    RuleDescription::createPhpCodeExample(
                        'class AnyClass',
                        '{',
                        "\t" . 'const MY_cONST = 0;',
                        '}'
                    ),
                ])
                ->acceptsExamples([
                    RuleDescription::createPhpCodeExample(
                        'class AnyClass',
                        '{',
                        "\t" . 'const MY = 0;',
                        '}'
                    ),
                    RuleDescription::createPhpCodeExample(
                        'class AnyClass',
                        '{',
                        "\t" . 'const MY_CONST = 0;',
                        '}'
                    ),
                    RuleDescription::createPhpCodeExample(
                        'class AnyClass',
                        '{',
                        "\t" . 'const _MY_CONST = 0;',
                        '}'
                    ),
                ])
        );
    }

    /**
     * @inheritdoc
     */
    public function getTypes(): array
    {
        return [
            AstNodeType::CLASS_CONST,
        ];
    }

    /**
     * @inheritdoc
     */
    public function validate(AstNode $node, LintContext $context, LintResult $result)
    {
        $constName = $node->get('name');
        if (!$constName || mb_strlen($constName->name) === 0) {
            return;
        }

        $constNamePattern = '/^_?([A-Z]+_?)+$/';
        if (preg_match($constNamePattern, $constName->name) !== 1) {
            $result->reportViolation($node, self::MESSAGE_ID_CLASS_CONSTANT_NAME_NOT_ALL_UPPER_CASE);
        }
    }
}
