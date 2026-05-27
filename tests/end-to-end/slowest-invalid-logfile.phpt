--TEST--
otr-report slowest reports an error for a logfile that does not validate against the OTR schema
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'slowest';
$_SERVER['argv'][] = __DIR__ . '/../fixture/invalid.xml';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

%sinvalid.xml is not a valid OTR XML logfile
%A
Exit code: 1
