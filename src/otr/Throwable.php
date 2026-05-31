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

final readonly class Throwable
{
    /**
     * @var non-empty-string
     */
    private string $type;
    private bool $assertionError;
    private string $message;

    /**
     * @param non-empty-string $type
     */
    public function __construct(string $type, bool $assertionError, string $message)
    {
        $this->type           = $type;
        $this->assertionError = $assertionError;
        $this->message        = $message;
    }

    /**
     * @return non-empty-string
     */
    public function type(): string
    {
        return $this->type;
    }

    public function assertionError(): bool
    {
        return $this->assertionError;
    }

    public function message(): string
    {
        return $this->message;
    }
}
