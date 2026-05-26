--TEST--
otr-report slowest reports an error for a logfile without tests
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'slowest';
$_SERVER['argv'][] = __DIR__ . '/../fixture/empty.xml';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

No tests found in %sempty.xml
Exit code: 1
