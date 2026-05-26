--TEST--
otr-report slowest --limit lists the requested number of tests
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'slowest';
$_SERVER['argv'][] = '--limit';
$_SERVER['argv'][] = '3';
$_SERVER['argv'][] = __DIR__ . '/../fixture/raytracer.xml';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

Time(s)   Test
-------   ----
4.441529  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_8
3.771325  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_6
1.375265  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_5
Exit code: 0
