--TEST--
otr-report slowest reports an error for a logfile that cannot be read
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'slowest';
$_SERVER['argv'][] = __DIR__ . '/../fixture/does-not-exist.xml';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

Cannot read %sdoes-not-exist.xml
Exit code: 1
