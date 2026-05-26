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

final class HelpCommand implements Command
{
    public function run(Arguments $arguments): int
    {
        print <<<'EOT'
Usage:
  otr-report slowest [--above-mean] [--limit <n>] [--sort <metric>] <file>
  otr-report trends <directory> <output>

Arguments for "otr-report slowest":
  <file>          OTR XML logfile of a single test-suite run

Options for "otr-report slowest":
  --above-mean    Only list tests beyond the mean value (with x-mean factor)
  --limit <n>     Number of tests to list (default: 10)
  --sort <metric> Metric to sort by: time, cpu, or memory (default: time)

Arguments for "otr-report trends":
  <directory>   Directory containing OTR XML logfiles
  <output>      HTML file the trend report is written to

EOT;

        return 0;
    }
}
