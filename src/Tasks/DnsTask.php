<?php
namespace Filternet\Icicle\Tasks;

use Filternet\Icicle\Site;
use Icicle\Concurrent\Worker\Environment;
use Icicle\Concurrent\Worker\Task;
use Filternet\Icicle\Results\Dns as DnsResult;
use Icicle\Coroutine;
use Icicle\Dns;
use Icicle\Dns\Executor\BasicExecutor;
use Icicle\Dns\Executor\MultiExecutor;

class DnsTask implements Task
{
    /**
     * @var Site
     */
    private $site;
    /**
     * DNS servers
     *
     * @var array
     */
    private $servers;

    /**
     * DnsTask constructor.
     * @param Site $site
     * @param array $servers
     */
    public function __construct(Site $site, array $servers = ['8.8.8.8', '4.2.2.4'])
    {
        $this->site = $site;
        $this->servers = $servers;
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
        $this->setupExecutors();

        return Coroutine\create(function () {

            $ips = yield from Dns\resolve($this->site->domain());

            $result = new DnsResult($this->site);
            $result->setIps($ips);

            return $result;
        });
    }

    private function setupExecutors()
    {
        $executor = new MultiExecutor();

        foreach ($this->servers as $server) {
            $executor->add(new BasicExecutor($server));
        }

        Dns\executor($executor);
    }
}