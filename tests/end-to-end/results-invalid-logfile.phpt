--TEST--
otr-report results reports an error for a logfile that does not validate against the OTR schema
--FILE--
<?php declare(strict_types=1);
$output = tempnam(sys_get_temp_dir(), 'otr-report-results-');

$_SERVER['argv'][] = 'results';
$_SERVER['argv'][] = __DIR__ . '/../fixture/invalid.xml';
$_SERVER['argv'][] = $output;

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;

unlink($output);
--EXPECTF--
otr-report %s by Sebastian Bergmann.

%sinvalid.xml is not a valid OTR XML logfile
%A
Exit code: 1
