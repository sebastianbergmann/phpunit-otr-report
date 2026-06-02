<?php declare(strict_types=1);
/*
 * This file is part of phpunit/otr-report.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\OtrReport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhptResult::class)]
#[UsesClass(Issue::class)]
#[UsesClass(TestResult::class)]
#[UsesClass(TestStatus::class)]
#[UsesClass(Throwable::class)]
#[TestDox('PhptResult')]
#[Small]
final class PhptResultTest extends TestCase
{
    public function testExposesTheValuesItWasCreatedFrom(): void
    {
        $throwable = new Throwable('PHPUnit\Framework\PhptAssertionFailedError', true, 'boom');
        $issue     = new Issue('deprecation', 'deprecated thing');

        $result = new PhptResult(
            '/path/to/tests/end-to-end/diff.phpt',
            TestStatus::Failed,
            'output does not match',
            $throwable,
            [$issue],
            0.5,
        );

        $this->assertSame('/path/to/tests/end-to-end/diff.phpt', $result->path());
        $this->assertSame(TestStatus::Failed, $result->status());
        $this->assertSame('output does not match', $result->reason());
        $this->assertSame($throwable, $result->throwable());
        $this->assertSame([$issue], $result->issues());
        $this->assertSame(0.5, $result->time());
    }

    public function testIdentifiesAsPhptResult(): void
    {
        $result = new PhptResult(
            '/path/to/tests/end-to-end/diff.phpt',
            TestStatus::Successful,
            '',
            null,
            [],
            0.1,
        );

        /** @phpstan-ignore method.alreadyNarrowedType */
        $this->assertTrue(
            /** @phpstan-ignore method.alreadyNarrowedType */
            $result->isPhpt(),
        );
    }
}
