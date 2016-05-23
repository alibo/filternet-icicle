<?php
namespace Filternet\Icicle\Tasks;

use Exception;
use Filternet\Icicle\Site;
use Filternet\Icicle\Tasks\BaseTask;
use Icicle\Concurrent\Worker\Environment;
use Icicle\Concurrent\Worker\Task;
use Icicle\Coroutine;
use Icicle\Dns;
use Filternet\Icicle\Results\Sni as SniResult;

class SniTask extends BaseTask implements Task
{
    /**
     * @var Site
     */
    private $site;
    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $timeout;

    /**
     * SniTask constructor.
     * @param Site $site
     * @param string|string $host
     * @param int $timeout
     */
    public function __construct(Site $site, string $host = 'google.com', int $timeout = 10)
    {
        $this->site = $site;
        $this->host = $host;
        $this->timeout = $timeout;
    }


    /**
     * @coroutine
     *
     * Runs the task inside the caller's context.
     *
     * Does not have to be a coroutine, can also be a regular function returning a value.
     *
     * @param \Icicle\Concurrent\Worker\Environment
     *
     * @return mixed|\Icicle\Awaitable\Awaitable|\Generator
     *
     * @resolve mixed
     */
    public function run(Environment $environment)
    {
        return Coroutine\create(function () {
            $this->startWatch();

            $error = '';

            while ($this->canRetry()) {
                $result = new SniResult($this->site);

                try {

                    /** @var \Icicle\Socket\Socket $socket */
                    $socket = yield from Dns\connect($this->host, 443, [
                        'name' => $this->site->domain(),
                        'timeout' => $this->timeout
                    ]);
                    yield from $socket->enableCrypto(STREAM_CRYPTO_METHOD_TLS_CLIENT, $this->timeout);

                    $result->setElapsedTime($this->elapsedTime());

                    $this->logger()->logSni($result);

                    return $result;

                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    if (!$result->isTimeoutError($error)) {
                        $result->setError($error);
                        $result->setElapsedTime($this->elapsedTime());

                        $this->logger()->logSni($result);

                        return $result;
                    }
                }

                yield from $this->retry();
                $this->timeout += 5;

            }

            $result = new SniResult($this->site);
            $result->setUnknown();
            $result->setError($error);
            $result->setElapsedTime($this->elapsedTime());

            $this->logger()->logSni($result);

            return $result;

        });
    }
}