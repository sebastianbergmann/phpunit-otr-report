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

use const PHP_EOL;
use const PSFS_PASS_ON;
use const STDERR;
use const STREAM_FILTER_WRITE;
use function assert;
use function copy;
use function file_exists;
use function file_get_contents;
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
#[UsesClass(HtmlReport::class)]
#[UsesClass(Chart::class)]
#[UsesClass(DataSeries::class)]
#[UsesClass(NumberFormatter::class)]
#[UsesClass(Sparkline::class)]
#[UsesClass(Issue::class)]
#[UsesClass(Metric::class)]
#[UsesClass(OtrReader::class)]
#[UsesClass(PhptResult::class)]
#[UsesClass(SchemaValidationException::class)]
#[UsesClass(TestMethodResult::class)]
#[UsesClass(TestResult::class)]
#[UsesClass(TestRun::class)]
#[UsesClass(TestRunCollection::class)]
#[UsesClass(TestRunCollectionIterator::class)]
#[UsesClass(TestStatus::class)]
#[UsesClass(Throwable::class)]
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

    public function testReturnsAnErrorExitCodeWhenFewerThanTwoPositionalArgumentsAreProvided(): void
    {
        $exitCode = (new TrendsCommand)->run(
            new Arguments(
                'trends',
                ['only-directory'],
                false,
                false,
                10,
                Metric::Time,
                false,
                false,
            ),
        );

        $this->assertSame(1, $exitCode);
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

            $captured = TrendsCommandTestStderrSuppressor::captured();

            $this->assertSame(1, $exitCode);
            $this->assertStringStartsWith($directory . '/invalid.xml is not a valid OTR XML logfile', $captured);
            $this->assertStringEndsWith(PHP_EOL, $captured);
        } finally {
            unlink($directory . '/invalid.xml');
            rmdir($directory);
        }
    }

    public function testWritesTheReportToTheOutputPathWhenTheDirectoryContainsValidLogfiles(): void
    {
        $directory = __DIR__ . '/../../../fixture/runs';
        $output    = sys_get_temp_dir() . '/otr-trends-report-' . uniqid() . '.html';

        $this->expectOutputString('Wrote trends report to ' . $output . PHP_EOL);

        try {
            $exitCode = (new TrendsCommand)->run(
                new Arguments(
                    'trends',
                    [$directory, $output],
                    false,
                    false,
                    10,
                    Metric::Time,
                    false,
                    false,
                ),
            );

            $this->assertSame(0, $exitCode);
            $this->assertTrue(file_exists($output));
            $this->assertStringContainsString('<title>Test Suite Trends</title>', (string) file_get_contents($output));
        } finally {
            if (file_exists($output)) {
                unlink($output);
            }
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
