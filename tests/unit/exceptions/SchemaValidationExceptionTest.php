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

use LibXMLError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchemaValidationException::class)]
#[TestDox('SchemaValidationException')]
#[Small]
final class SchemaValidationExceptionTest extends TestCase
{
    public function testListsTheFileAndEachValidationError(): void
    {
        $first          = new LibXMLError;
        $first->message = "first problem\n";

        $second          = new LibXMLError;
        $second->message = "second problem\n";

        $exception = new SchemaValidationException('/path/to/logfile.xml', [$first, $second]);

        $this->assertSame(
            "/path/to/logfile.xml is not a valid OTR XML logfile\n  - first problem\n  - second problem",
            $exception->getMessage(),
        );
    }
}
