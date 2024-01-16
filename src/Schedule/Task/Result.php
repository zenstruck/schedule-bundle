<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Result
{
    public const SUCCESSFUL = 'successful';
    public const FAILED = 'failed';
    public const SKIPPED = 'skipped';

    /** @var Task */
    private $task;

    /** @var string */
    private $type;

    /** @var string */
    private $description;

    /** @var Task\string|null */
    private $output;

    /** @var \Throwable|null */
    private $exception;

    private function __construct(Task $task, string $type, string $description)
    {
        $this->task = $task;
        $this->type = $type;
        $this->description = $description;
    }

    public function __toString(): string
    {
        return $this->getDescription();
    }

    public static function successful(Task $task, ?string $output = null, string $description = 'Successful'): self
    {
        $result = new self($task, self::SUCCESSFUL, $description);
        $result->output = $output;

        return $result;
    }

    public static function failure(Task $task, string $description, ?string $output = null): self
    {
        $result = new self($task, self::FAILED, $description);
        $result->output = $output;

        return $result;
    }

    public static function exception(Task $task, \Throwable $exception, ?string $output = null, ?string $description = null): self
    {
        $description = $description ?: \sprintf('%s: %s', (new \ReflectionClass($exception))->getShortName(), $exception->getMessage());

        $result = self::failure($task, $description, $output);
        $result->exception = $exception;

        return $result;
    }

    public static function skipped(Task $task, string $description): self
    {
        return new self($task, self::SKIPPED, $description);
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    public function isSuccessful(): bool
    {
        return self::SUCCESSFUL === $this->getType();
    }

    public function isFailure(): bool
    {
        return self::FAILED === $this->getType();
    }

    public function isException(): bool
    {
        return $this->isFailure() && $this->exception instanceof \Throwable;
    }

    public function isSkipped(): bool
    {
        return self::SKIPPED === $this->getType();
    }

    public function hasRun(): bool
    {
        return self::SKIPPED !== $this->getType();
    }
}
