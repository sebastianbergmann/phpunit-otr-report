--TEST--
otr-report slowest --above-mean lists only tests slower than the mean
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'slowest';
$_SERVER['argv'][] = '--above-mean';
$_SERVER['argv'][] = __DIR__ . '/../fixture/raytracer.xml';

require __DIR__ . '/../../vendor/autoload.php';

$exitCode = (new PHPUnit\OtrReport\Application)->run($_SERVER['argv']);

print 'Exit code: ' . $exitCode . PHP_EOL;
--EXPECTF--
otr-report %s by Sebastian Bergmann.

Mean test runtime: 0.059520 s (177 tests, 4 slower than mean)

Time(s)   x mean    Test
-------   ------    ----
4.441529    74.62x  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_8
3.771325    63.36x  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_6
1.375265    23.11x  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_5
0.845473    14.20x  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_10
Exit code: 0
