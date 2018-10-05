<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoNullableBooleanTypeFixer
 */
final class NoNullableBooleanTypeFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertSame(0, $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertTrue($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Generator
    {
        yield [
            '<?php function foo(bool $b) {}',
            '<?php function foo(?bool $b) {}',
        ];

        yield [
            '<?php function foo(Bool $b) {}',
            '<?php function foo(?Bool $b) {}',
        ];

        yield [
            '<?php function foo(boolean $b) {}',
            '<?php function foo(?boolean $b) {}',
        ];

        yield [
            '<?php function foo() : bool {}',
            '<?php function foo() : ?bool {}',
        ];

        yield [
            '<?php function foo(bool $a, bool $b, bool $c, bool $d) {}',
            '<?php function foo(?bool $a, ?bool $b, ?bool $c, ?bool $d) {}',
        ];
        yield [
            '<?php function foo(  bool $b ) {}',
            '<?php function foo( ? bool $b ) {}',
        ];

        yield [
            '<?php function foo(?int $b) : ?string {}',
        ];

        yield [
            '<?php FOO ? BOOL : BAR;',
        ];

        yield [
            '<?php FOO ?: bool;',
        ];
    }
}
