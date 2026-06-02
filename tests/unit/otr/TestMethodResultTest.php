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

#[CoversClass(TestMethodResult::class)]
#[UsesClass(Issue::class)]
#[UsesClass(TestResult::class)]
#[UsesClass(TestStatus::class)]
#[UsesClass(Throwable::class)]
#[TestDox('TestMethodResult')]
#[Small]
final class TestMethodResultTest extends TestCase
{
    public function testExposesTheValuesItWasCreatedFrom(): void
    {
        $throwable = new Throwable('RuntimeException', false, 'boom');
        $issue     = new Issue('risky', 'no assertions');

        $result = new TestMethodResult(
            'Vendor\ExampleTest',
            'test_one',
            'test_one',
            TestStatus::Errored,
            'something went wrong',
            $throwable,
            [$issue],
            0.125,
        );

        $this->assertSame('Vendor\ExampleTest', $result->className());
        $this->assertSame('test_one', $result->methodName());
        $this->assertSame('test_one', $result->displayName());
        $this->assertSame('Vendor\ExampleTest::test_one', $result->name());
        $this->assertSame(TestStatus::Errored, $result->status());
        $this->assertSame('something went wrong', $result->reason());
        $this->assertSame($throwable, $result->throwable());
        $this->assertSame([$issue], $result->issues());
        $this->assertSame(0.125, $result->time());
    }

    public function testPreservesParameterizedDisplayNamesInItsName(): void
    {
        $result = new TestMethodResult(
            'Vendor\ExampleTest',
            'test_one',
            'test_one with data set #2',
            TestStatus::Successful,
            '',
            null,
            [],
            0.001,
        );

        $this->assertSame('Vendor\ExampleTest::test_one with data set #2', $result->name());
        $this->assertSame('test_one', $result->methodName());
        $this->assertSame('test_one with data set #2', $result->displayName());
    }

    public function testHasNoTestDoxNamesByDefault(): void
    {
        $result = new TestMethodResult(
            'Vendor\ExampleTest',
            'test_one',
            'test_one',
            TestStatus::Successful,
            '',
            null,
            [],
            0.0,
        );

        $this->assertNull($result->prettifiedClassName());
        $this->assertNull($result->prettifiedMethodName());
    }

    public function testExposesTestDoxPrettifiedClassAndMethodNames(): void
    {
        $result = new TestMethodResult(
            'Vendor\ExampleTest',
            'test_one',
            'test_one',
            TestStatus::Successful,
            '',
            null,
            [],
            0.0,
            'Example behavior',
            'Does the thing',
        );

        $this->assertSame('Example behavior', $result->prettifiedClassName());
        $this->assertSame('Does the thing', $result->prettifiedMethodName());
    }

    public function testIdentifiesAsTestMethod(): void
    {
        $result = new TestMethodResult(
            'Vendor\ExampleTest',
            'test_one',
            'test_one',
            TestStatus::Successful,
            '',
            null,
            [],
            0.0,
        );

        /** @phpstan-ignore method.alreadyNarrowedType */
        $this->assertTrue(
            /** @phpstan-ignore method.alreadyNarrowedType */
            $result->isTestMethod(),
        );
    }
}
