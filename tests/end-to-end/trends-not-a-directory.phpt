--TEST--
otr-report trends reports an error when the directory argument is not a directory
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'trends';
$_SERVER['argv'][] = __DIR__ . '/../fixture/raytracer.xml';
$_SERVER['argv'][] = 'report.html';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

%s is not a directory
Exit code: 1
