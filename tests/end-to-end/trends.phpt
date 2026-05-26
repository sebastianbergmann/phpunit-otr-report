--TEST--
otr-report trends writes an HTML report for a directory of runs
--FILE--
<?php declare(strict_types=1);
$output = tempnam(sys_get_temp_dir(), 'otr-report-trends-');

$_SERVER['argv'][] = 'trends';
$_SERVER['argv'][] = __DIR__ . '/../fixture/runs';
$_SERVER['argv'][] = $output;

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
print 'Report contains title: ';
print str_contains((string) file_get_contents($output), '<title>Test Suite Trends</title>') ? 'yes' : 'no';
print PHP_EOL;

unlink($output);
--EXPECTF--
otr-report %s by Sebastian Bergmann.

Wrote trends report to %s
Exit code: 0
Report contains title: yes
