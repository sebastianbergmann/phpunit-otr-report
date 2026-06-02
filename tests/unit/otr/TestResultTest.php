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

#[CoversClass(TestResult::class)]
#[UsesClass(Issue::class)]
#[UsesClass(TestStatus::class)]
#[UsesClass(Throwable::class)]
#[TestDox('TestResult')]
#[Small]
final class TestResultTest extends TestCase
{
    public function testExposesTheStatusReasonThrowableIssuesAndTimeItWasCreatedFrom(): void
    {
        $throwable = new Throwable('RuntimeException', false, 'boom');
        $issue     = new Issue('risky', 'no assertions');

        $result = $this->createResult(TestStatus::Errored, 'something went wrong', $throwable, [$issue], 0.125);

        $this->assertSame(TestStatus::Errored, $result->status());
        $this->assertSame('something went wrong', $result->reason());
        $this->assertTrue($result->hasReason());
        $this->assertSame($throwable, $result->throwable());
        $this->assertSame([$issue], $result->issues());
        $this->assertTrue($result->hasIssues());
        $this->assertSame(0.125, $result->time());
    }

    public function testKnowsWhenItHasNoReasonOrIssues(): void
    {
        $result = $this->createResult(TestStatus::Successful, '', null, [], 0.0);

        $this->assertFalse($result->hasReason());
        $this->assertFalse($result->hasIssues());
        $this->assertNull($result->throwable());
    }

    public function testAllowsTheTimeToBeUnknown(): void
    {
        $result = $this->createResult(TestStatus::Skipped, 'skipped by metadata', null, [], null);

        $this->assertNull($result->time());
    }

    public function testKnowsWhenItBelongsToAConfiguredTestSuite(): void
    {
        $result = $this->createResult(TestStatus::Successful, '', null, [], 0.0, 'unit');

        $this->assertSame('unit', $result->suite());
        $this->assertTrue($result->hasSuite());
    }

    public function testKnowsWhenItDoesNotBelongToAConfiguredTestSuite(): void
    {
        $result = $this->createResult(TestStatus::Successful, '', null, [], 0.0);

        $this->assertNull($result->suite());
        $this->assertFalse($result->hasSuite());
    }

    public function testIsNotATestMethodOrPhptResultByDefault(): void
    {
        $result = $this->createResult(TestStatus::Successful, '', null, [], 0.0);

        $this->assertFalse($result->isTestMethod());
        $this->assertFalse($result->isPhpt());
    }

    /**
     * @param list<Issue>       $issues
     * @param ?non-empty-string $suite
     */
    private function createResult(TestStatus $status, string $reason, ?Throwable $throwable, array $issues, ?float $time, ?string $suite = null): TestResult
    {
        return new readonly class($status, $reason, $throwable, $issues, $time, $suite) extends TestResult
        {};
    }
}
