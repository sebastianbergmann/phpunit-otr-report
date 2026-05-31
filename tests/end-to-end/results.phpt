--TEST--
otr-report results writes an HTML report for a single run
--FILE--
<?php declare(strict_types=1);
$output = tempnam(sys_get_temp_dir(), 'otr-report-results-');

$_SERVER['argv'][] = 'results';
$_SERVER['argv'][] = __DIR__ . '/../fixture/status.xml';
$_SERVER['argv'][] = $output;

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

$html = (string) file_get_contents($output);

print 'Exit code: ' . $exitCode . PHP_EOL;
print 'Report contains title: ' . (str_contains($html, '<title>Test Results</title>') ? 'yes' : 'no') . PHP_EOL;
print 'Report contains all status pills: ' . (
    str_contains($html, 'pill successful') &&
    str_contains($html, 'pill failed') &&
    str_contains($html, 'pill errored') &&
    str_contains($html, 'pill aborted') &&
    str_contains($html, 'pill skipped') &&
    str_contains($html, 'pill issue')
        ? 'yes' : 'no'
) . PHP_EOL;
print 'Report contains the test class: ' . (str_contains($html, 'PHPUnit\TestFixture\Basic\StatusTest') ? 'yes' : 'no') . PHP_EOL;
print 'Report contains a throwable: ' . (str_contains($html, 'RuntimeException') ? 'yes' : 'no') . PHP_EOL;

unlink($output);
--EXPECTF--
otr-report %s by Sebastian Bergmann.

Wrote test results report to %s
Exit code: 0
Report contains title: yes
Report contains all status pills: yes
Report contains the test class: yes
Report contains a throwable: yes
