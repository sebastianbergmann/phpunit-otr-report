--TEST--
otr-report --version prints the version
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = '--version';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.
Exit code: 0
