--TEST--
otr-report results reports an error for a logfile that does not exist
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'results';
$_SERVER['argv'][] = '/does/not/exist.xml';
$_SERVER['argv'][] = '/tmp/otr-report-results-nonexistent.html';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

Cannot read /does/not/exist.xml
Exit code: 1
