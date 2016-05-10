<?php
namespace Filternet\Icicle\Tasks;

use Filternet\Icicle\Site;
use Icicle\Concurrent\Worker\Environment;
use Icicle\Concurrent\Worker\Task;
use Icicle\Coroutine;
use Icicle\Dns;
use Filternet\Icicle\Results\Sni as SniResult;

class SniTask implements Task
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
     * SniTask constructor.
     * @param Site $site
     * @param string $host
     */
    public function __construct(Site $site, string $host = 'google.com')
    {
        $this->site = $site;
        $this->host = $host;
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

            $result = new SniResult($this->site);

            try {
                /** @var \Icicle\Socket\Socket $socket */
                $socket = yield from Dns\connect('google.com', 443, ['name' => $this->site->domain()]);
                yield from $socket->enableCrypto(STREAM_CRYPTO_METHOD_TLS_CLIENT);
            } catch (\Exception $e) {
                $result->setError($e->getMessage());
            }

            return $result;
        });
    }
}