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

#[CoversClass(Throwable::class)]
#[TestDox('Throwable')]
#[Small]
final class ThrowableTest extends TestCase
{
    public function testExposesTheValuesItWasCreatedFrom(): void
    {
        $throwable = new Throwable('RuntimeException', false, 'Something went wrong');

        $this->assertSame('RuntimeException', $throwable->type());
        $this->assertFalse($throwable->assertionError());
        $this->assertSame('Something went wrong', $throwable->message());
    }

    public function testRecognizesAssertionErrors(): void
    {
        $throwable = new Throwable('PHPUnit\Framework\ExpectationFailedException', true, 'Failed asserting that false is true.');

        $this->assertTrue($throwable->assertionError());
    }
}
