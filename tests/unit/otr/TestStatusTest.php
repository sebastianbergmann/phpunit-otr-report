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
use PHPUnit\Framework\TestCase;

#[CoversClass(TestStatus::class)]
#[TestDox('TestStatus')]
#[Small]
final class TestStatusTest extends TestCase
{
    public function testReportsPassingOnlyForSuccessful(): void
    {
        $this->assertTrue(TestStatus::Successful->isPassing());
        $this->assertFalse(TestStatus::Failed->isPassing());
        $this->assertFalse(TestStatus::Errored->isPassing());
        $this->assertFalse(TestStatus::Aborted->isPassing());
        $this->assertFalse(TestStatus::Skipped->isPassing());
    }

    public function testReportsFailingOrErroredForFailedAndErrored(): void
    {
        $this->assertTrue(TestStatus::Failed->isFailingOrErrored());
        $this->assertTrue(TestStatus::Errored->isFailingOrErrored());
        $this->assertFalse(TestStatus::Successful->isFailingOrErrored());
        $this->assertFalse(TestStatus::Aborted->isFailingOrErrored());
        $this->assertFalse(TestStatus::Skipped->isFailingOrErrored());
    }
}
