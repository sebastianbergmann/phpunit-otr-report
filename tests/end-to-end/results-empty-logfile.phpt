--TEST--
otr-report results reports an error for a logfile without test results
--FILE--
<?php declare(strict_types=1);
$output = tempnam(sys_get_temp_dir(), 'otr-report-results-');

$_SERVER['argv'][] = 'results';
$_SERVER['argv'][] = __DIR__ . '/../fixture/empty.xml';
$_SERVER['argv'][] = $output;

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;

unlink($output);
--EXPECTF--
otr-report %s by Sebastian Bergmann.

No test results found in %sempty.xml
Exit code: 1
