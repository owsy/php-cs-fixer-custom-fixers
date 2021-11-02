<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArgumentAnalysis;
use PhpCsFixerCustomFixers\Analyzer\FunctionAnalyzer;

final class PhpUnitAssertArgumentsOrderFixer extends AbstractFixer
{
    private const ASSERTIONS = [
        'assertEquals' => true,
        'assertNotEquals' => true,
        'assertEqualsCanonicalizing' => true,
        'assertNotEqualsCanonicalizing' => true,
        'assertEqualsIgnoringCase' => true,
        'assertNotEqualsIgnoringCase' => true,
        'assertEqualsWithDelta' => true,
        'assertNotEqualsWithDelta' => true,
        'assertSame' => true,
        'assertNotSame' => true,
        'assertGreaterThan' => 'assertLessThan',
        'assertGreaterThanOrEqual' => 'assertLessThanOrEqual',
        'assertLessThan' => 'assertGreaterThan',
        'assertLessThanOrEqual' => 'assertGreaterThanOrEqual',
    ];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'PHPUnit assertions must have expected argument before actual one.',
            [new CodeSample('<?php
class FooTest extends TestCase {
    public function testFoo() {
        self::assertSame($value, 10);
    }
}
')],
            null,
            'when original PHPUnit methods are overwritten'
        );
    }

    /**
     * Must run before PhpUnitConstructFixer, PhpUnitDedicatedAssertFixer.
     */
    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([\T_CLASS, \T_EXTENDS, \T_FUNCTION]);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();

        /** @var array<int> $indexes */
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $this->fixArgumentsOrder($tokens, $indexes[0], $indexes[1]);
        }
    }

    private function fixArgumentsOrder(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        for ($index = $startIndex; $index < $endIndex; $index++) {
            $newAssertion = self::getNewAssertion($tokens, $index);
            if ($newAssertion === null) {
                continue;
            }

            $arguments = FunctionAnalyzer::getFunctionArguments($tokens, $index);

            if (!self::shouldArgumentsBeSwapped($arguments)) {
                continue;
            }

            if ($newAssertion !== $tokens[$index]->getContent()) {
                $tokens[$index] = new Token([\T_STRING, $newAssertion]);
            }

            self::swapArguments($tokens, $arguments);
        }
    }

    private static function getNewAssertion(Tokens $tokens, int $index): ?string
    {
        /** @var null|array<string, bool|string> $assertions */
        static $assertions;

        if ($assertions === null) {
            $assertions = [];
            foreach (self::ASSERTIONS as $old => $new) {
                $assertions[\strtolower($old)] = $new;
            }
        }

        $oldAssertion = $tokens[$index]->getContent();

        if (!isset($assertions[\strtolower($oldAssertion)])) {
            return null;
        }

        $newAssertion = $assertions[\strtolower($oldAssertion)];

        /** @var int $openingBraceIndex */
        $openingBraceIndex = $tokens->getNextMeaningfulToken($index);

        if (!$tokens[$openingBraceIndex]->equals('(')) {
            return null;
        }

        if (!(new FunctionsAnalyzer())->isTheSameClassCall($tokens, $index)) {
            return null;
        }

        if (!\is_string($newAssertion)) {
            return $oldAssertion;
        }

        return $newAssertion;
    }

    /**
     * @param array<ArgumentAnalysis> $arguments
     */
    private static function shouldArgumentsBeSwapped(array $arguments): bool
    {
        if (\count($arguments) < 2) {
            return false;
        }

        if ($arguments[0]->isConstant()) {
            return false;
        }

        return $arguments[1]->isConstant();
    }

    /**
     * @param array<ArgumentAnalysis> $arguments
     */
    private static function swapArguments(Tokens $tokens, array $arguments): void
    {
        $expectedArgumentTokens = []; // these will be 1st argument
        for ($index = $arguments[1]->getStartIndex(); $index <= $arguments[1]->getEndIndex(); $index++) {
            $expectedArgumentTokens[] = $tokens[$index];
        }

        $actualArgumentTokens = []; // these will be 2nd argument
        for ($index = $arguments[0]->getStartIndex(); $index <= $arguments[0]->getEndIndex(); $index++) {
            $actualArgumentTokens[] = $tokens[$index];
        }

        $tokens->overrideRange($arguments[1]->getStartIndex(), $arguments[1]->getEndIndex(), $actualArgumentTokens);
        $tokens->overrideRange($arguments[0]->getStartIndex(), $arguments[0]->getEndIndex(), $expectedArgumentTokens);
    }
}