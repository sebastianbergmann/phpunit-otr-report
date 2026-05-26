--TEST--
otr-report trends reports an error when the directory contains no OTR XML files
--FILE--
<?php declare(strict_types=1);
$directory = sys_get_temp_dir() . '/otr-report-empty-' . uniqid();

mkdir($directory);

$_SERVER['argv'][] = 'trends';
$_SERVER['argv'][] = $directory;
$_SERVER['argv'][] = $directory . '/report.html';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;

rmdir($directory);
--EXPECTF--
otr-report %s by Sebastian Bergmann.

No OTR XML files found in %s
Exit code: 1
