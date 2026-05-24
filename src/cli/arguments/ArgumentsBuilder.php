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

use function array_merge;
use SebastianBergmann\CliParser\Exception as CliParserException;
use SebastianBergmann\CliParser\Parser as CliParser;

final class ArgumentsBuilder
{
    private const array COMMANDS = [
        'todo' => [
            'longOptions' => [
            ],
            'arguments' => [
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

        switch ($command) {
            case 'todo':
                break;
        }

        $help    = false;
        $version = false;

        foreach ($options[0] as $option) {
            switch ($option[0]) {
                case 'h':
                case '--help':
                    $help = true;

                    break;

                case 'v':
                case '--version':
                    $version = true;

                    break;
            }
        }

        return new Arguments(
            $command,
            $help,
            $version,
        );
    }
}
