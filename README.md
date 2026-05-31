# otr-report

This tool generates reports from test result data in the Open Test Reporting (OTR) format produced by PHPUnit >= 13.2.

> **Note:** [Open Test Reporting](https://github.com/ota4j-team/open-test-reporting) is language-agnostic and tool-agnostic. This tool, however, is specialised for the OTR XML produced by PHPUnit 13.2 and newer, which contains additional information specific to PHP and PHPUnit.

## Installation

This tool is distributed as a [PHP Archive (PHAR)](https://php.net/phar):

```bash
$ wget https://phar.phpunit.de/otr-report-X.Y.phar

$ php otr-report-X.Y.phar --version
```

Please replace `X.Y` with the version of otr-report you are interested in.

Using [Phive](https://phar.io/) is the recommended way for managing the tool dependencies of your project:

```
$ phive install otr-report

$ ./tools/otr-report --version
```

**[It is not recommended to use Composer to download and install this tool.](https://phpunit.readthedocs.io/en/13.1/installation.html#phar-or-composer)**

## Generating OTR data

This tool operates on test result data that PHPUnit writes in the Open Test Reporting (OTR) XML format.

Use PHPUnit's `--log-otr` option to write an OTR XML logfile for a test-suite run:

```
$ phpunit --log-otr /tmp/otr/run.xml
```

The logfile records, among other things, how long each test took to run.

## Usage

### Listing the slowest tests

The `otr-report slowest` command reads the OTR XML logfile of a single test-suite run and prints the ten slowest tests, ordered from slowest to fastest.

This is useful for finding the tests that dominate the runtime of your test suite.

Use the `--limit` option to list a different number of tests; it defaults to `10`.

#### Example

```
$ otr-report slowest /tmp/otr/run.xml
otr-report 1.0 by Sebastian Bergmann.

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
```

#### The `--above-mean` option

With the `--above-mean` option, the report first calculates the mean runtime across all tests and then lists only the tests that are slower than the mean. Each listed test is annotated with a factor that shows how many times slower than the mean it is.

```
$ otr-report slowest --above-mean /tmp/otr/run.xml
otr-report 1.0 by Sebastian Bergmann.

Mean test runtime: 0.059520 s (177 tests, 4 slower than mean)

Time(s)   x mean    Test
-------   ------    ----
4.441529    74.62x  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_8
3.771325    63.36x  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_6
1.375265    23.11x  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_5
0.845473    14.20x  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_10
```

#### The `--limit` option

By default, the ten slowest tests are listed. Use the `--limit` option to list a different number of tests:

```
$ otr-report slowest --limit 3 /tmp/otr/run.xml
otr-report 1.0 by Sebastian Bergmann.

Time(s)   Test
-------   ----
4.441529  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_8
3.771325  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_6
1.375265  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_5
```

The option also applies to `--above-mean`. The value must be a positive integer.

#### The `--sort` option

By default, tests are sorted by their wall-clock runtime. Use the `--sort` option to sort by a different metric:

* `time` (default) sorts by wall-clock runtime (the `time` attribute), in seconds
* `cpu` sorts by CPU time (the `cpuTime` attribute, i.e. user plus system CPU time), in seconds
* `memory` sorts by peak memory usage (the `peakMemoryUsage` attribute), in bytes

```
$ otr-report slowest --sort memory --limit 3 /tmp/otr/run.xml
otr-report 1.0 by Sebastian Bergmann.

Memory    Test
-------   ----
23489616  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_6
22907872  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_4
22044384  SebastianBergmann\Raytracer\PuttingItTogetherTest::test_chapter_5
```

The option combines with `--above-mean`, which then lists the tests beyond the mean of the chosen metric.

### Generating a trend report

The `otr-report trends` command reads all OTR XML logfiles in a directory and generates a single HTML report that visualizes how your test suite evolves across runs.

This is useful for tracking the runtime and the number of tests of your test suite over time, for example by archiving one OTR XML logfile per CI run into a shared directory.

The generated HTML report includes:

* The total runtime of the test suite across all runs, as a chart
* The number of tests across all runs, as a chart
* The ten slowest tests of the most recent run, each with a sparkline of its runtime across all runs and the change relative to its first recorded runtime
* A table of all runs, with their start time, total runtime, and number of tests

The runs are ordered by their start time, which is read from the OTR XML logfiles.

#### Example

Collect one OTR XML logfile per run into a directory:

```
$ phpunit --log-otr /tmp/otr/2026-02-02.xml
$ phpunit --log-otr /tmp/otr/2026-02-09.xml
$ phpunit --log-otr /tmp/otr/2026-02-16.xml
```

Then generate the trend report:

```
$ otr-report trends /tmp/otr /tmp/trends.html
otr-report 1.0 by Sebastian Bergmann.

Wrote trends report to /tmp/trends.html
```

The first argument is the directory containing the OTR XML logfiles, the second argument is the HTML file the trend report is written to.

### Generating a test results report

The `otr-report results` command reads the OTR XML logfile of a single test-suite run and generates a self-contained HTML report that visualises the outcome of every test.

The generated HTML report includes:

* A summary of the run (total tests, status counts, total runtime, and the number of reported issues)
* A sticky sidebar with a collapsible tree that groups tests by namespace, then class, then method, with a status indicator at every level
* One collapsible section per test class, headed by the worst status in the class
* For each test: its status (`SUCCESSFUL`, `FAILED`, `ERRORED`, `ABORTED`, or `SKIPPED`), runtime, reason, throwable, and any reported issues such as `risky`

Tests that did not pass are expanded by default; successful tests are collapsed. Clicking a class or method in the sidebar scrolls to its entry in the main pane.

#### Example

```
$ otr-report results /tmp/otr/run.xml /tmp/results.html
otr-report 1.0 by Sebastian Bergmann.

Wrote test results report to /tmp/results.html
```

The first argument is the OTR XML logfile, the second argument is the HTML file the report is written to.

#### The `--testdox` option

By default, the report identifies each test class and each test method by its PHP name. With the `--testdox` option, the report uses the TestDox information (the prettified class and method names that PHPUnit records in the OTR XML logfile) instead. When a test does not have TestDox information, the original class or method name is used as a fallback.

```
$ otr-report results --testdox /tmp/otr/run.xml /tmp/results.html
```
