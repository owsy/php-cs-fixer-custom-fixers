<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixerCustomFixers\Fixer\PhpdocNoIncorrectVarAnnotationFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\PhpdocVarAnnotationCorrectOrderFixer
 */
final class PhpdocVarAnnotationCorrectOrderFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new PhpdocNoIncorrectVarAnnotationFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    public function testDeprecatingPullRequest(): void
    {
        static::assertSame(3881, $this->fixer->getPullRequestId());
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
        yield [ // It's @param, we care only about @var
            '<?php
/** @param $foo Foo */
',
        ];

        yield [ // This is already fine
            '<?php
/** @var Foo $foo */
',
        ];

        yield [ // What? Two variables, I'm not touching this
            '<?php
/** @var $foo $bar */
',
        ];

        yield [
            '<?php
/**
 * @var Foo $foo
 * @var Bar $bar
 */
',
            '<?php
/**
 * @var $foo Foo
 * @var $bar Bar
 */
',
        ];

        yield [
            '<?php
/**
 * @var Foo $foo Some description
 */
',
            '<?php
/**
 * @var $foo Foo Some description
 */
',
        ];

        yield [
            '<?php
/** @var Foo $foo */
',
            '<?php
/** @var $foo Foo */
',
        ];

        yield [
            '<?php
/** @var Foo $foo*/
',
            '<?php
/** @var $foo Foo*/
',
        ];

        yield [
            '<?php
/** @var Foo[] $foos */
',
            '<?php
/** @var $foos Foo[] */
',
        ];

        yield [
            '<?php
/** @Var Foo $foo */
',
            '<?php
/** @Var $foo Foo */
',
        ];

        yield [
            '<?php
/** @var Foo|Bar|mixed|int $someWeirdLongNAME__123 */
',
            '<?php
/** @var $someWeirdLongNAME__123 Foo|Bar|mixed|int */
',
        ];

        yield [
            '<?php
/** @var array<int, int> $foo */
',
            '<?php
/** @var $foo array<int, int> */
',
        ];

        yield [
            '<?php
/** @var array<int, int> $foo Array of something */
',
            '<?php
/** @var $foo array<int, int> Array of something */
',
        ];

        yield [
            '<?php
/** @var Foo|array<int, int>|null $foo */
',
            '<?php
/** @var $foo Foo|array<int, int>|null */
',
        ];
    }
}
