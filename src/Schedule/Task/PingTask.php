<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Zenstruck\ScheduleBundle\Schedule\HasMissingDependencyMessage;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PingTask extends Task implements HasMissingDependencyMessage
{
    /** @var string */
    private $url;

    /** @var string */
    private $method;

    /** @var array */
    private $options;

    /**
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    public function __construct(string $url, string $method = 'GET', array $options = [])
    {
        $this->url = $url;
        $this->method = $method;
        $this->options = $options;

        parent::__construct("Ping {$url}");
    }

    public function getContext(): array
    {
        return [
            'Url' => $this->url,
            'Method' => $this->method,
            'Options' => \json_encode($this->options),
        ];
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public static function getMissingDependencyMessage(): string
    {
        return \sprintf('Symfony HttpClient is required to use "%s". Install with "composer require symfony/http-client".', self::class);
    }
}
