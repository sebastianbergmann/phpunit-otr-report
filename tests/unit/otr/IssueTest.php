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

#[CoversClass(Issue::class)]
#[TestDox('Issue')]
#[Small]
final class IssueTest extends TestCase
{
    public function testExposesTheValuesItWasCreatedFrom(): void
    {
        $issue = new Issue('risky', 'This test did not perform any assertions');

        $this->assertSame('risky', $issue->type());
        $this->assertSame('This test did not perform any assertions', $issue->message());
    }
}
