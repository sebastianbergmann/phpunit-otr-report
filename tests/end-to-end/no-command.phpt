--TEST--
otr-report without a command prints the usage information and fails
--FILE--
<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

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
Exit code: 255
