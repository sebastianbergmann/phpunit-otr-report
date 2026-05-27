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

use const PHP_EOL;
use function rtrim;
use function sprintf;
use LibXMLError;
use RuntimeException;

final class SchemaValidationException extends RuntimeException implements Exception
{
    /**
     * @param list<LibXMLError> $errors
     */
    public function __construct(string $file, array $errors)
    {
        $message = sprintf('%s is not a valid OTR XML logfile', $file);

        foreach ($errors as $error) {
            $message .= sprintf('%s  - %s', PHP_EOL, rtrim($error->message));
        }

        parent::__construct($message);
    }
}
