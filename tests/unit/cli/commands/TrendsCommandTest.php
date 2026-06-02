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

use const PSFS_PASS_ON;
use const STDERR;
use const STREAM_FILTER_WRITE;
use function assert;
use function copy;
use function is_resource;
use function mkdir;
use function rmdir;
use function stream_bucket_make_writeable;
use function stream_filter_append;
use function stream_filter_register;
use function stream_filter_remove;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use php_user_filter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrendsCommand::class)]
#[UsesClass(Arguments::class)]
#[UsesClass(OtrReader::class)]
#[UsesClass(SchemaValidationException::class)]
#[TestDox('TrendsCommand')]
#[Small]
final class TrendsCommandTest extends TestCase
{
    private static bool $filterRegistered = false;

    public static function setUpBeforeClass(): void
    {
        if (!self::$filterRegistered) {
            stream_filter_register('trends-command-test.suppress', TrendsCommandTestStderrSuppressor::class);

            self::$filterRegistered = true;
        }
    }

    public function testReportsAnErrorWhenTheDirectoryContainsAnInvalidLogfile(): void
    {
        $directory = sys_get_temp_dir() . '/otr-trends-' . uniqid();

        mkdir($directory);
        copy(__DIR__ . '/../../../fixture/invalid.xml', $directory . '/invalid.xml');

        try {
            TrendsCommandTestStderrSuppressor::reset();

            $filter = stream_filter_append(STDERR, 'trends-command-test.suppress', STREAM_FILTER_WRITE);

            assert(is_resource($filter));

            try {
                $exitCode = (new TrendsCommand)->run(
                    new Arguments(
                        'trends',
                        [$directory, $directory . '/report.html'],
                        false,
                        false,
                        10,
                        Metric::Time,
                        false,
                        false,
                    ),
                );
            } finally {
                stream_filter_remove($filter);
            }

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('is not a valid OTR XML logfile', TrendsCommandTestStderrSuppressor::captured());
        } finally {
            unlink($directory . '/invalid.xml');
            rmdir($directory);
        }
    }
}

final class TrendsCommandTestStderrSuppressor extends php_user_filter
{
    private static string $captured = '';

    public static function reset(): void
    {
        self::$captured = '';
    }

    public static function captured(): string
    {
        return self::$captured;
    }

    public function filter($in, $out, &$consumed, $closing): int
    {
        while (($bucket = stream_bucket_make_writeable($in)) !== null) {
            self::$captured .= $bucket->data;
            $consumed += $bucket->datalen;
        }

        return PSFS_PASS_ON;
    }
}
