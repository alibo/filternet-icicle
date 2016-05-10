<?php
namespace Filternet\Icicle\Tasks;

use Filternet\Icicle\Site;
use Icicle\Concurrent\Worker\Environment;
use Icicle\Concurrent\Worker\Task;
use Icicle\Dns;
use Icicle\Http\Driver\Reader\Http1Reader;
use Icicle\Http\Message\BasicResponse;
use Icicle\Coroutine;
use Filternet\Icicle\Results\Http as HttpResult;

class HttpTask implements Task
{
    /**
     * @var Site
     */
    private $site;

    /**
     * HttpTask constructor.
     * @param Site $site
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
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
            $socket = yield from $this->connect();

            yield from $socket->write($this->createHttpRequest());

            return yield from $this->result($socket);
        });
    }

    /**
     * @return \Generator|\Icicle\Socket\Socket
     */
    protected function connect(): \Generator
    {
        return yield from Dns\connect($this->site->domain(), 80);
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
        return yield from (new Http1Reader())->readResponse($socket);
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
            $result->setBody(yield from $body->read(196));
        }

        return $result;
    }
}