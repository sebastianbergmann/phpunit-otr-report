<?php declare(strict_types=1);
/*
 * This file is part of phpunit/otr-report.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\OtrReport;

use function assert;
use function glob;
use function usort;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;

final class OtrReader
{
    public function read(string $file): TestRun
    {
        $dom = new DOMDocument;
        $dom->load($file);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('e', 'https://schemas.opentest4j.org/reporting/events/0.2.0');
        $xpath->registerNamespace('phpunit', 'https://schema.phpunit.de/otr/phpunit/0.2.0');

        $rootStarted = $this->queryFirst($xpath, '/e:events/e:started[@id="1"]');

        assert($rootStarted instanceof DOMElement);

        $startedAt = new DateTimeImmutable($rootStarted->getAttribute('time'));

        $tests = [];

        $startedNodes = $xpath->query('//e:started[.//phpunit:methodSource]');

        assert($startedNodes !== false);

        foreach ($startedNodes as $started) {
            assert($started instanceof DOMElement);

            $id     = $started->getAttribute('id');
            $source = $this->queryFirst($xpath, './/phpunit:methodSource', $started);

            assert($source instanceof DOMElement);

            $tests[$id] = $source->getAttribute('className') . '::' . $started->getAttribute('name');
        }

        $times      = [];
        $cpuTimes   = [];
        $peakMemory = [];
        $totalTime  = 0.0;

        $finishedNodes = $xpath->query('//e:finished');

        assert($finishedNodes !== false);

        foreach ($finishedNodes as $finished) {
            assert($finished instanceof DOMElement);

            $id    = $finished->getAttribute('id');
            $usage = $this->queryFirst($xpath, './/phpunit:resourceUsage', $finished);

            if ($id === '1') {
                if ($usage instanceof DOMElement) {
                    $totalTime = (float) $usage->getAttribute('time');
                }

                continue;
            }

            if (!isset($tests[$id]) || !($usage instanceof DOMElement)) {
                continue;
            }

            $name = $tests[$id];

            $times[$name]      = (float) $usage->getAttribute('time');
            $cpuTimes[$name]   = (float) $usage->getAttribute('cpuTime');
            $peakMemory[$name] = (float) $usage->getAttribute('peakMemoryUsage');
        }

        return new TestRun($file, $startedAt, $totalTime, $times, $cpuTimes, $peakMemory);
    }

    public function readDirectory(string $directory): TestRunCollection
    {
        $files = glob($directory . '/*.xml');

        if ($files === false) {
            // @codeCoverageIgnoreStart
            $files = [];
            // @codeCoverageIgnoreEnd
        }

        $runs = [];

        foreach ($files as $file) {
            $runs[] = $this->read($file);
        }

        usort(
            $runs,
            static fn (TestRun $a, TestRun $b): int => $a->startedAt() <=> $b->startedAt(),
        );

        return TestRunCollection::fromArray($runs);
    }

    private function queryFirst(DOMXPath $xpath, string $expression, ?DOMElement $context = null): ?DOMElement
    {
        $nodes = $xpath->query($expression, $context);

        if ($nodes === false) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        $node = $nodes->item(0);

        if (!($node instanceof DOMElement)) {
            return null;
        }

        return $node;
    }
}
