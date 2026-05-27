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

use function array_map;
use function array_merge;
use function array_slice;
use function ctype_digit;
use function implode;
use function sprintf;
use SebastianBergmann\CliParser\Exception as CliParserException;
use SebastianBergmann\CliParser\Parser as CliParser;

final readonly class ArgumentsBuilder
{
    private const array COMMANDS = [
        'slowest' => [
            'longOptions' => [
                'above-mean',
                'limit=',
                'sort=',
            ],
            'arguments' => [
                'file',
            ],
        ],
        'trends' => [
            'longOptions' => [
            ],
            'arguments' => [
                'directory',
                'output',
            ],
        ],
    ];

    /**
     * @param list<string> $argv
     *
     * @throws ArgumentsBuilderException
     */
    public function build(array $argv): Arguments
    {
        $longOptions = [
            'help',
            'version',
        ];

        $command = null;

        if (isset($argv[1], self::COMMANDS[$argv[1]])) {
            $command     = $argv[1];
            $longOptions = array_merge($longOptions, self::COMMANDS[$command]['longOptions']);
        }

        try {
            $options = (new CliParser)->parse(
                $argv,
                'hv',
                $longOptions,
            );
        } catch (CliParserException $e) {
            throw new ArgumentsBuilderException(
                $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }

        if ($command !== null) {
            foreach (self::COMMANDS[$command]['arguments'] as $position => $argument) {
                if (!isset($options[1][$position + 1])) {
                    throw new RequiredArgumentMissingException($argument);
                }
            }
        }

        $arguments = $command !== null ? array_slice($options[1], 1) : [];

        $help      = false;
        $aboveMean = false;
        $limit     = 10;
        $sort      = Metric::Time;
        $version   = false;

        foreach ($options[0] as $option) {
            switch ($option[0]) {
                case 'h':
                case '--help':
                    $help = true;

                    break;

                case '--above-mean':
                    $aboveMean = true;

                    break;

                case '--limit':
                    $limit = $this->limit($option[1]);

                    break;

                case '--sort':
                    $sort = $this->sort($option[1]);

                    break;

                case 'v':
                case '--version':
                    $version = true;

                    break;
            }
        }

        return new Arguments(
            $command,
            $arguments,
            $help,
            $aboveMean,
            $limit,
            $sort,
            $version,
        );
    }

    /**
     * @throws ArgumentsBuilderException
     *
     * @return positive-int
     */
    private function limit(?string $value): int
    {
        if ($value === null || !ctype_digit($value) || (int) $value < 1) {
            throw new ArgumentsBuilderException(
                sprintf(
                    'Value of --limit must be a positive integer, got "%s"',
                    (string) $value,
                ),
            );
        }

        return (int) $value;
    }

    /**
     * @throws ArgumentsBuilderException
     */
    private function sort(?string $value): Metric
    {
        $metric = $value !== null ? Metric::tryFrom($value) : null;

        if ($metric === null) {
            throw new ArgumentsBuilderException(
                sprintf(
                    'Value of --sort must be one of "%s", got "%s"',
                    implode('", "', array_map(static fn (Metric $case): string => $case->value, Metric::cases())),
                    (string) $value,
                ),
            );
        }

        return $metric;
    }
}
