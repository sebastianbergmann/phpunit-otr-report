--TEST--
otr-report slowest lists the ten slowest tests of a run
--FILE--
<?php declare(strict_types=1);
$_SERVER['argv'][] = 'slowest';
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
0.845473  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_10
0.039408  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_4
0.023849  SebastianBergmann\Raytracer\CameraTest::test_rendering_a_world_with_a_camera
0.000839  SebastianBergmann\Raytracer\StripePatternTest::test_a_stripe_pattern_alternates_in_x
0.000744  SebastianBergmann\Raytracer\RingPatternTest::test_a_ring_should_extend_in_both_x_and_z
0.000707  SebastianBergmann\Raytracer\GradientPatternTest::test_a_gradient_linearly_interpolates_between_colors
0.000688  SebastianBergmann\Raytracer\CheckersPatternTest::test_checkers_should_repeat_in_x
Exit code: 0
