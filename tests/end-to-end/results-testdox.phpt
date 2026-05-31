--TEST--
otr-report results --testdox uses TestDox names instead of class and method names
--FILE--
<?php declare(strict_types=1);
$output = tempnam(sys_get_temp_dir(), 'otr-report-results-');

$_SERVER['argv'][] = 'results';
$_SERVER['argv'][] = '--testdox';
$_SERVER['argv'][] = __DIR__ . '/../fixture/status.xml';
$_SERVER['argv'][] = $output;

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

$html = (string) file_get_contents($output);

print 'Exit code: ' . $exitCode . PHP_EOL;
print 'Report contains the prettified class name: ' . (str_contains($html, 'Test result status with and without message') ? 'yes' : 'no') . PHP_EOL;
print 'Report contains a prettified method name: ' . (str_contains($html, 'Error with message') ? 'yes' : 'no') . PHP_EOL;
print 'Report omits the original class name: ' . (str_contains($html, 'PHPUnit\TestFixture\Basic\StatusTest') ? 'no' : 'yes') . PHP_EOL;
print 'Report omits the original method name: ' . (str_contains($html, 'testErrorWithMessage') ? 'no' : 'yes') . PHP_EOL;

unlink($output);
--EXPECTF--
otr-report %s by Sebastian Bergmann.

Wrote test results report to %s
Exit code: 0
Report contains the prettified class name: yes
Report contains a prettified method name: yes
Report omits the original class name: yes
Report omits the original method name: yes
