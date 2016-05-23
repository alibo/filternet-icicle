<?php
namespace Filternet\Icicle\Tasks;

use Filternet\Icicle\Logger;
use Filternet\Icicle\SyncLogger;
use Icicle\Coroutine;

class BaseTask
{
    /**
     * @var int
     */
    protected $startTime;

    /**
     * @var int
     */
    protected $retries = 0;

    /**
     * @var SyncLogger|Logger
     */
    private $logger;

    protected function startWatch()
    {
        $this->startTime = microtime(true);
    }

    protected function elapsedTime(): float
    {
        $now = microtime(true);
        return $now - $this->startTime;
    }

    protected function maximumRetries()
    {
        return 2;
    }

    protected function retry()
    {
        $this->retries++;
        return yield from Coroutine\sleep($this->retries);
    }

    /**
     * @return bool
     */
    function canRetry()
    {
        return $this->retries < $this->maximumRetries();
    }

    public function logger($logger = null)
    {
        if (!is_null($logger)) {
            $this->logger = $logger;
        }

        if (is_null($logger) && is_null($this->logger)) {
            $this->logger = new SyncLogger(__DIR__ . '/logs');
        }

        return $this->logger;
    }
}