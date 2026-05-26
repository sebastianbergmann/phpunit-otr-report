--TEST--
otr-report slowest --sort memory sorts by peak memory usage
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'slowest';
$_SERVER['argv'][] = '--sort';
$_SERVER['argv'][] = 'memory';
$_SERVER['argv'][] = '--limit';
$_SERVER['argv'][] = '3';
$_SERVER['argv'][] = __DIR__ . '/../fixture/raytracer.xml';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

Memory    Test
-------   ----
23489616  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_6
22907872  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_4
22044384  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_5
Exit code: 0
