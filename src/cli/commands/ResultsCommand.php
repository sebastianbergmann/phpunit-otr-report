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
use function is_file;
use function is_readable;
use function printf;

final readonly class ResultsCommand implements Command
{
    public function run(Arguments $arguments): int
    {
        $positional = $arguments->arguments();

        assert(isset($positional[0], $positional[1]));

        $file   = $positional[0];
        $output = $positional[1];

        if (!is_file($file) || !is_readable($file)) {
            fwrite(STDERR, 'Cannot read ' . $file . PHP_EOL);

            return 1;
        }

        try {
            $run = (new OtrReader)->read($file);
        } catch (Exception $e) {
            fwrite(STDERR, $e->getMessage() . PHP_EOL);

            return 1;
        }

        if (!$run->hasResults()) {
            fwrite(STDERR, 'No test results found in ' . $file . PHP_EOL);

            return 1;
        }

        file_put_contents($output, (new HtmlTestReport)->render($run, $arguments->testdox()));

        printf(
            'Wrote test results report to %s' . PHP_EOL,
            $output,
        );

        return 0;
    }
}
