--TEST--
otr-report slowest --sort cpu sorts by CPU time
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'slowest';
$_SERVER['argv'][] = '--sort';
$_SERVER['argv'][] = 'cpu';
$_SERVER['argv'][] = '--limit';
$_SERVER['argv'][] = '3';
$_SERVER['argv'][] = __DIR__ . '/../fixture/raytracer.xml';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

  CPU(s)  Test
 -------  ----
4.424492  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_8
3.756712  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_6
1.369960  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_5
Exit code: 0
