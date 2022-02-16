<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task\Runner;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\ScheduleBundle\Schedule\Exception\MissingDependency;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\PingTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PingTaskRunner implements TaskRunner
{
    /** @var HttpClientInterface */
    private $httpClient;

    public function __construct(?HttpClientInterface $httpClient = null)
    {
        if (null === $httpClient && !\class_exists(HttpClient::class)) {
            throw new MissingDependency(PingTask::getMissingDependencyMessage());
        }

        $this->httpClient = $httpClient ?: HttpClient::create();
    }

    /**
     * @param PingTask $task
     */
    public function __invoke(Task $task): Result
    {
        $response = $this->httpClient->request($task->getMethod(), $task->getUrl(), $task->getOptions());
        $content = $response->getContent();
        $output = \array_merge($response->getInfo('response_headers'), ['', $content]);

        return Result::successful($task, \implode("\n", $output));
    }

    public function supports(Task $task): bool
    {
        return $task instanceof PingTask;
    }
}
