<?php
namespace Filternet\Icicle\Tasks;

use Exception;
use Filternet\Icicle\Site;
use Filternet\Icicle\Tasks\BaseTask;
use Icicle\Concurrent\Worker\Environment;
use Icicle\Concurrent\Worker\Task;
use Icicle\Dns;
use Icicle\Http\Driver\Reader\Http1Reader;
use Icicle\Http\Message\BasicResponse;
use Icicle\Coroutine;
use Filternet\Icicle\Results\Http as HttpResult;
use Icicle\Socket\Socket;

class HttpTask extends BaseTask implements Task
{
    /**
     * @var Site
     */
    private $site;

    /**
     * @var int
     */
    private $timeout;

    /**
     * HttpTask constructor.
     * @param Site $site
     * @param $timeout
     */
    public function __construct(Site $site, int $timeout = 10)
    {
        $this->site = $site;
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
            while ($this->canRetry()) {
                try {
                    /** @var Socket $socket */
                    $socket = yield from $this->connect();

                    yield from $socket->write($this->createHttpRequest(), $this->timeout);

                    $result = yield from $this->result($socket);
                    $this->logger()->logHttp($result);
                    return $result;

                } catch (Exception $e) {
                    yield from $this->retry();
                    $this->timeout += 5;
                }
            }

            $result = new HttpResult($this->site);
            $result->setUnknown();
            $result->setElapsedTime($this->elapsedTime());

            $this->logger()->logHttp($result);
            return $result;
        });
    }

    /**
     * @return \Generator|\Icicle\Socket\Socket
     */
    protected function connect(): \Generator
    {
        return yield from Dns\connect($this->site->domain(), 80, [
            'timeout' => $this->timeout
        ]);
    }

    /**
     * @return string
     */
    protected function createHttpRequest(): string
    {
        $request = "GET / HTTP/1.1\r\n";
        $request .= "Host: {$this->site->domain()}\r\n";
        $request .= "Connection: close\r\n";
        $request .= "\r\n";

        return $request;
    }

    /**
     * @param $socket
     * @return \Generator
     * @throws \MessageException
     * @throws \ParseException
     */
    protected function parseResponse($socket): \Generator
    {
        return yield from (new Http1Reader())->readResponse($socket, $this->timeout);
    }

    /**
     * @param $socket
     * @return HttpResult|\Generator
     */
    protected function result($socket): \Generator
    {
        /** @var BasicResponse $response */
        $response = yield from $this->parseResponse($socket);

        $result = new HttpResult($this->site);
        $result->setHeaders($response->getHeaders());
        $result->setStatusCode($response->getStatusCode());

        $body = $response->getBody();

        if ($body->isReadable()) {
            $result->setBody(yield from $body->read(196, null, $this->timeout));
        }

        $result->setElapsedTime($this->elapsedTime());
        return $result;
    }
}