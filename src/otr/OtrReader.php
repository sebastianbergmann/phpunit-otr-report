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

use function array_values;
use function assert;
use function glob;
use function ksort;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function usort;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;

final readonly class OtrReader
{
    /**
     * @throws SchemaValidationException
     */
    public function read(string $file): TestRun
    {
        $dom = new DOMDocument;
        $dom->load($file);

        $this->validate($dom, $file);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('core', 'https://schemas.opentest4j.org/reporting/core/0.2.0');
        $xpath->registerNamespace('e', 'https://schemas.opentest4j.org/reporting/events/0.2.0');
        $xpath->registerNamespace('phpunit', 'https://schema.phpunit.de/otr/phpunit/0.2.0');

        $rootStarted = $this->queryFirst($xpath, '/e:events/e:started[@id="1"]');

        assert($rootStarted instanceof DOMElement);

        $startedAt = new DateTimeImmutable($rootStarted->getAttribute('time'));

        /** @var array<string, array{className: non-empty-string, methodName: non-empty-string, displayName: non-empty-string, order: int}> $tests */
        $tests = [];
        $order = 0;

        $startedNodes = $xpath->query('//e:started[.//phpunit:methodSource]');

        assert($startedNodes !== false);

        foreach ($startedNodes as $started) {
            assert($started instanceof DOMElement);

            $id     = $started->getAttribute('id');
            $source = $this->queryFirst($xpath, './/phpunit:methodSource', $started);

            assert($source instanceof DOMElement);

            $className   = $source->getAttribute('className');
            $methodName  = $source->getAttribute('methodName');
            $displayName = $started->getAttribute('name');

            assert($className !== '');
            assert($methodName !== '');
            assert($displayName !== '');

            $tests[$id] = [
                'className'   => $className,
                'methodName'  => $methodName,
                'displayName' => $displayName,
                'order'       => $order++,
            ];
        }

        /** @var array<string, list<Issue>> $issuesById */
        $issuesById    = [];
        $reportedNodes = $xpath->query('//e:reported');

        assert($reportedNodes !== false);

        foreach ($reportedNodes as $reported) {
            assert($reported instanceof DOMElement);

            $id = $reported->getAttribute('id');

            if (!isset($tests[$id])) {
                continue;
            }

            $issueNodes = $xpath->query('.//phpunit:issue', $reported);

            assert($issueNodes !== false);

            foreach ($issueNodes as $issue) {
                assert($issue instanceof DOMElement);

                $type = $issue->getAttribute('type');

                assert($type !== '');

                $issuesById[$id][] = new Issue($type, $issue->getAttribute('message'));
            }
        }

        $times      = [];
        $cpuTimes   = [];
        $peakMemory = [];
        $totalTime  = 0.0;

        /** @var array<string, array{status: TestStatus, reason: string, throwable: ?Throwable, time: ?float}> $finishedById */
        $finishedById = [];

        $finishedNodes = $xpath->query('//e:finished');

        assert($finishedNodes !== false);

        foreach ($finishedNodes as $finished) {
            assert($finished instanceof DOMElement);

            $id    = $finished->getAttribute('id');
            $usage = $this->queryFirst($xpath, './/phpunit:resourceUsage', $finished);

            $time = null;

            if ($usage instanceof DOMElement) {
                $time = (float) $usage->getAttribute('time');

                if ($id === '1') {
                    $totalTime = $time;
                } elseif (isset($tests[$id])) {
                    $name              = $tests[$id]['className'] . '::' . $tests[$id]['displayName'];
                    $times[$name]      = $time;
                    $cpuTimes[$name]   = (float) $usage->getAttribute('cpuTime');
                    $peakMemory[$name] = (float) $usage->getAttribute('peakMemoryUsage');
                }
            }

            if (!isset($tests[$id])) {
                continue;
            }

            $result = $this->queryFirst($xpath, './core:result', $finished);

            if (!($result instanceof DOMElement)) {
                continue;
            }

            $status = TestStatus::tryFrom($result->getAttribute('status'));

            if ($status === null) {
                continue;
            }

            $reasonNode = $this->queryFirst($xpath, './core:reason', $result);
            $reason     = $reasonNode instanceof DOMElement ? $reasonNode->textContent : '';

            $throwable = null;
            $throwNode = $this->queryFirst($xpath, './phpunit:throwable', $result);

            if ($throwNode instanceof DOMElement) {
                $type = $throwNode->getAttribute('type');

                if ($type !== '') {
                    $throwable = new Throwable(
                        $type,
                        $throwNode->getAttribute('assertionError') === 'true',
                        $throwNode->textContent,
                    );
                }
            }

            $finishedById[$id] = [
                'status'    => $status,
                'reason'    => $reason,
                'throwable' => $throwable,
                'time'      => $id === '1' ? null : $time,
            ];
        }

        $results = [];

        foreach ($tests as $id => $test) {
            if (!isset($finishedById[$id])) {
                continue;
            }

            $results[$test['order']] = new TestResult(
                $test['className'],
                $test['methodName'],
                $test['displayName'],
                $finishedById[$id]['status'],
                $finishedById[$id]['reason'],
                $finishedById[$id]['throwable'],
                $issuesById[$id] ?? [],
                $finishedById[$id]['time'],
            );
        }

        ksort($results);

        return new TestRun($file, $startedAt, $totalTime, $times, $cpuTimes, $peakMemory, array_values($results));
    }

    /**
     * @throws SchemaValidationException
     */
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

    /**
     * @throws SchemaValidationException
     */
    private function validate(DOMDocument $dom, string $file): void
    {
        $previousUseInternalErrors = libxml_use_internal_errors(true);

        $valid  = $dom->schemaValidate(__DIR__ . '/../../schema/otr.xsd');
        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (!$valid) {
            throw new SchemaValidationException($file, $errors);
        }
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
