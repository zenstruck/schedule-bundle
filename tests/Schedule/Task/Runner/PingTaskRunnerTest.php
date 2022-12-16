<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task\Runner;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Zenstruck\ScheduleBundle\Schedule\Task\PingTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\PingTaskRunner;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PingTaskRunnerTest extends TestCase
{
    /**
     * @test
     */
    public function creates_successful_result()
    {
        $context = self::createBuilder(
            new MockHttpClient(
                new MockResponse('response body', [
                    'response_headers' => [
                        'HTTP/1.1 200 OK',
                        'Server: Foo',
                    ],
                ])
            ))
            ->addTask(new PingTask('https://example.com'))
            ->run()
        ;

        $this->assertTrue($context->isSuccessful());
        $this->assertCount(1, $run = $context->getSuccessful());
        $this->assertSame("HTTP/1.1 200 OK\nServer: Foo\n\nresponse body", $run[0]->getOutput());
    }

    /**
     * @test
     */
    public function creates_failure_on_404()
    {
        $context = self::createBuilder(
            new MockHttpClient(
                new MockResponse('response body', [
                    'response_headers' => [
                        'HTTP/1.1 404 Not Found',
                        'Server: Foo',
                    ],
                ])
            ))
            ->addTask(new PingTask('https://example.com'))
            ->run()
        ;

        $this->assertFalse($context->isSuccessful());
        $this->assertTrue($context->isFailure());
        $this->assertCount(1, $run = $context->getFailures());
        $this->assertInstanceOf(ClientException::class, $run[0]->getException());
        $this->assertStringContainsString('ClientException', $run[0]->getDescription());
        $this->assertStringContainsString('HTTP/1.1 404 Not Found', $run[0]->getDescription());
        $this->assertStringContainsString('https://example.com/', $run[0]->getDescription());
    }

    /**
     * @test
     */
    public function creates_failure_on_500()
    {
        $context = self::createBuilder(
            new MockHttpClient(
                new MockResponse('response body', [
                    'response_headers' => [
                        'HTTP/1.1 500 Internal Server Error',
                        'Server: Foo',
                    ],
                ])
            ))
            ->addTask(new PingTask('https://example.com'))
            ->run()
        ;

        $this->assertFalse($context->isSuccessful());
        $this->assertTrue($context->isFailure());
        $this->assertCount(1, $run = $context->getFailures());
        $this->assertInstanceOf(ServerException::class, $run[0]->getException());
        $this->assertStringContainsString('ServerException', $run[0]->getDescription());
        $this->assertStringContainsString('HTTP/1.1 500 Internal Server Error', $run[0]->getDescription());
        $this->assertStringContainsString('https://example.com/', $run[0]->getDescription());
    }

    private static function createBuilder(MockHttpClient $httpClient): MockScheduleBuilder
    {
        return (new MockScheduleBuilder())
            ->addRunner(new PingTaskRunner($httpClient))
        ;
    }
}
