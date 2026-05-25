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
use function fwrite;
use function is_file;
use function is_readable;

final class SlowestCommand implements Command
{
    public function run(Arguments $arguments): int
    {
        $positional = $arguments->arguments();

        assert(isset($positional[0]));

        $file = $positional[0];

        if (!is_file($file) || !is_readable($file)) {
            fwrite(STDERR, 'Cannot read ' . $file . PHP_EOL);

            return 1;
        }

        $run = (new OtrReader)->read($file);

        if ($run->isEmpty()) {
            fwrite(STDERR, 'No tests found in ' . $file . PHP_EOL);

            return 1;
        }

        print (new TextReport)->render($run, $arguments->mean());

        return 0;
    }
}
