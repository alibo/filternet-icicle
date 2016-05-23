<?php
namespace Filternet\Icicle\Tasks;

use Exception;
use Filternet\Icicle\Site;
use Filternet\Icicle\Tasks\BaseTask;
use Icicle\Concurrent\Worker\Environment;
use Icicle\Concurrent\Worker\Task;
use Filternet\Icicle\Results\Dns as DnsResult;
use Icicle\Coroutine;
use Icicle\Dns;
use Icicle\Dns\Executor\BasicExecutor;
use Icicle\Dns\Executor\MultiExecutor;

class DnsTask extends BaseTask implements Task
{
    /**
     * @var Site
     */
    private $site;

    /**
     * DnsTask constructor.
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
            $this->startWatch();

            try {
                $ips = yield from Dns\resolve($this->site->domain());

                if (empty($ips)) {
                    throw new Exception("cannot resolve domain");
                }

                $result = new DnsResult($this->site);
                $result->setIps($ips);
                $result->setElapsedTime($this->elapsedTime());

                $this->logger()->logDns($result);

                return $result;
            } catch (Exception $e) {
                $result = new DnsResult($this->site);
                $result->setUnknown();
                $result->setElapsedTime($this->elapsedTime());

                $this->logger()->logDns($result);

                return $result;
            }

        });
    }

    public static function setupDnsServers($servers)
    {
        $executor = new MultiExecutor();

        foreach ($servers as $server) {
            $executor->add(new BasicExecutor($server));
        }

        Dns\executor($executor);
    }
}