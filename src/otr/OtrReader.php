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
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function substr;
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

        $rootId    = $rootStarted->getAttribute('id');
        $startedAt = new DateTimeImmutable($rootStarted->getAttribute('time'));

        /** @var array<string, string> $parentOf */
        $parentOf = [];

        /** @var array<string, non-empty-string> $suiteNames */
        $suiteNames = [];

        $allStartedNodes = $xpath->query('//e:started');

        assert($allStartedNodes !== false);

        foreach ($allStartedNodes as $node) {
            assert($node instanceof DOMElement);

            $nodeId   = $node->getAttribute('id');
            $parentId = $node->getAttribute('parentId');

            if ($parentId !== '') {
                $parentOf[$nodeId] = $parentId;
            }

            // A configured test suite is a direct child of the root event that
            // carries no source of its own (unlike test classes, methods, or
            // PHPT files, which all reference a source).
            if ($parentId === $rootId && !$this->hasSource($xpath, $node)) {
                $name = $node->getAttribute('name');

                if ($name !== '') {
                    $suiteNames[$nodeId] = $name;
                }
            }
        }

        /** @var array<string, non-empty-string> $prettifiedClassNames */
        $prettifiedClassNames = [];

        $classStartedNodes = $xpath->query('//e:started[.//phpunit:classSource]');

        assert($classStartedNodes !== false);

        foreach ($classStartedNodes as $classStarted) {
            assert($classStarted instanceof DOMElement);

            $classId      = $classStarted->getAttribute('id');
            $classTestDox = $this->queryFirst($xpath, './/phpunit:testDox', $classStarted);

            if ($classTestDox instanceof DOMElement) {
                $prettifiedClassName = $classTestDox->getAttribute('prettifiedClassName');

                if ($prettifiedClassName !== '') {
                    $prettifiedClassNames[$classId] = $prettifiedClassName;
                }
            }
        }

        /** @var array<string, array{kind: 'method', className: non-empty-string, methodName: non-empty-string, displayName: non-empty-string, name: non-empty-string, prettifiedClassName: ?non-empty-string, prettifiedMethodName: ?non-empty-string}|array{kind: 'phpt', path: non-empty-string, name: non-empty-string}> $tests */
        $tests = [];

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

            $prettifiedMethodName = null;
            $methodTestDox        = $this->queryFirst($xpath, './/phpunit:testDox', $started);

            if ($methodTestDox instanceof DOMElement) {
                $prettifiedMethodNameAttr = $methodTestDox->getAttribute('prettifiedMethodName');

                if ($prettifiedMethodNameAttr !== '') {
                    $prettifiedMethodName = $prettifiedMethodNameAttr;
                }
            }

            $parentId            = $started->getAttribute('parentId');
            $prettifiedClassName = $parentId !== '' ? ($prettifiedClassNames[$parentId] ?? null) : null;

            $tests[$id] = [
                'kind'                 => 'method',
                'className'            => $className,
                'methodName'           => $methodName,
                'displayName'          => $displayName,
                'name'                 => $className . '::' . $displayName,
                'prettifiedClassName'  => $prettifiedClassName,
                'prettifiedMethodName' => $prettifiedMethodName,
            ];
        }

        $phptCandidateNodes = $xpath->query('//e:started[not(.//phpunit:methodSource) and not(.//phpunit:classSource)]');

        assert($phptCandidateNodes !== false);

        foreach ($phptCandidateNodes as $candidate) {
            assert($candidate instanceof DOMElement);

            $fileSource = $this->queryFirst($xpath, './/core:fileSource', $candidate);

            if ($fileSource === null) {
                continue;
            }

            $path = $fileSource->getAttribute('path');

            if (substr($path, -5) !== '.phpt') {
                continue;
            }

            $id = $candidate->getAttribute('id');

            $tests[$id] = [
                'kind' => 'phpt',
                'path' => $path,
                'name' => $path,
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
        $results    = [];

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
                    $name              = $tests[$id]['name'];
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

            $test   = $tests[$id];
            $issues = $issuesById[$id] ?? [];
            $suite  = $this->suiteFor($id, $parentOf, $suiteNames);

            if ($test['kind'] === 'phpt') {
                $results[] = new PhptResult(
                    $test['path'],
                    $status,
                    $reason,
                    $throwable,
                    $issues,
                    $time,
                    $suite,
                );
            } else {
                $results[] = new TestMethodResult(
                    $test['className'],
                    $test['methodName'],
                    $test['displayName'],
                    $status,
                    $reason,
                    $throwable,
                    $issues,
                    $time,
                    $test['prettifiedClassName'],
                    $test['prettifiedMethodName'],
                    $suite,
                );
            }
        }

        return new TestRun($file, $startedAt, $totalTime, $times, $cpuTimes, $peakMemory, $results);
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

    private function hasSource(DOMXPath $xpath, DOMElement $node): bool
    {
        $sources = $xpath->query('.//phpunit:methodSource | .//phpunit:classSource | .//core:fileSource', $node);

        return $sources !== false && $sources->length > 0;
    }

    /**
     * Resolves the test suite a test belongs to by walking up the parent chain
     * until a configured test suite is reached.
     *
     * @param array<string, string>           $parentOf
     * @param array<string, non-empty-string> $suiteNames
     *
     * @return ?non-empty-string
     */
    private function suiteFor(string $id, array $parentOf, array $suiteNames): ?string
    {
        while (true) {
            if (isset($suiteNames[$id])) {
                return $suiteNames[$id];
            }

            if (!isset($parentOf[$id])) {
                return null;
            }

            $id = $parentOf[$id];
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
