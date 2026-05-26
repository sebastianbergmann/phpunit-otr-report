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
use const STDERR;
use function assert;
use function dirname;
use function fwrite;
use function sprintf;
use SebastianBergmann\Version;

final class Application
{
    private const string VERSION       = '1.0';
    private static string $pharVersion = '';

    /**
     * @return non-empty-string
     */
    public static function version(): string
    {
        if (self::$pharVersion !== '') {
            // @codeCoverageIgnoreStart
            return self::$pharVersion;
            // @codeCoverageIgnoreEnd
        }

        $directory = dirname(__DIR__);

        assert($directory !== '');

        return new Version(self::VERSION, $directory)->asString();
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        $this->printVersion();

        try {
            $arguments = (new ArgumentsBuilder)->build($argv);
        } catch (Exception $e) {
            fwrite(STDERR, PHP_EOL . $e->getMessage() . PHP_EOL);

            return 255;
        }

        if ($arguments->version()) {
            return 0;
        }

        fwrite(STDERR, PHP_EOL);

        if ($arguments->help()) {
            return (new HelpCommand)->run($arguments);
        }

        if ($arguments->command() === null) {
            (new HelpCommand)->run($arguments);

            return 255;
        }

        return match ($arguments->command()) {
            'slowest' => (new SlowestCommand)->run($arguments),
            'trends'  => (new TrendsCommand)->run($arguments),
            default   => (new HelpCommand)->run($arguments),
        };
    }

    private function printVersion(): void
    {
        fwrite(
            STDERR,
            sprintf(
                'otr-report %s by Sebastian Bergmann.' . PHP_EOL,
                self::version(),
            ),
        );
    }
}
