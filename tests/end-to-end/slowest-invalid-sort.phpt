--TEST--
otr-report slowest rejects an unknown --sort metric
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'slowest';
$_SERVER['argv'][] = '--sort';
$_SERVER['argv'][] = 'bogus';
$_SERVER['argv'][] = __DIR__ . '/../fixture/raytracer.xml';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

Value of --sort must be one of "time", "cpu", "memory", got "bogus"
Exit code: 255
