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
use function file_put_contents;
use function fwrite;
use function is_dir;

final class TrendsCommand implements Command
{
    public function run(Arguments $arguments): int
    {
        $positional = $arguments->arguments();

        assert(isset($positional[0], $positional[1]));

        $directory = $positional[0];
        $output    = $positional[1];

        if (!is_dir($directory)) {
            fwrite(STDERR, $directory . ' is not a directory' . PHP_EOL);

            return 1;
        }

        $runs = (new OtrReader)->readDirectory($directory);

        if ($runs->isEmpty()) {
            fwrite(STDERR, 'No OTR XML files found in ' . $directory . PHP_EOL);

            return 1;
        }

        file_put_contents($output, (new HtmlReport)->render($runs));

        return 0;
    }
}
