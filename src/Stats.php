<?php
namespace Filternet\Icicle;

use Filternet\Icicle\Results\Dns;
use Filternet\Icicle\Results\Http;
use Filternet\Icicle\Results\Sni;

class Stats
{
    protected $http = [
        'name' => 'http',
        'open' => 0,
        'blocked' => 0,
        'unknown' => 0,
        'total' => 0
    ];
    protected $dns = [
        'name' => 'dns',
        'open' => 0,
        'blocked' => 0,
        'unknown' => 0,
        'total' => 0
    ];
    protected $sni = [
        'name' => 'sni',
        'open' => 0,
        'blocked' => 0,
        'unknown' => 0,
        'total' => 0
    ];

    /**
     * @var int
     */
    protected $startTime;

    public function startWatch()
    {
        $this->startTime = microtime(true);
    }

    public function elapsedTime(): float
    {
        $now = microtime(true);
        return $now - $this->startTime;
    }

    public function addSni(Sni $sni)
    {
        if ($sni->isUnknown()) {
            $this->sni['unknown']++;
        } elseif ($sni->isBlocked()) {
            $this->sni['blocked']++;
        } else {
            $this->sni['open']++;
        }

        $this->sni['total']++;
    }

    public function addDns(Dns $dns)
    {
        if ($dns->isUnknown()) {
            $this->dns['unknown']++;
        } elseif ($dns->isBlocked()) {
            $this->dns['blocked']++;
        } else {
            $this->dns['open']++;
        }

        $this->dns['total']++;
    }

    public function addHttp(Http $http)
    {
        if ($http->isUnknown()) {
            $this->http['unknown']++;
        } elseif ($http->isBlocked()) {
            $this->http['blocked']++;
        } else {
            $this->http['open']++;
        }

        $this->http['total']++;
    }

    /**
     * @return array
     */
    public function getHttp()
    {
        return $this->calculatePercentage($this->http);
    }

    /**
     * @return array
     */
    public function getDns()
    {
        return $this->calculatePercentage($this->dns);
    }

    /**
     * @return array
     */
    public function getSni()
    {
        return $this->calculatePercentage($this->sni);
    }

    protected function calculatePercentage($stats)
    {
        $stats['blocked-percentage'] = '0%';
        $stats['open-percentage'] = '0%';
        $stats['unknown-percentage'] = '0%';

        if ($stats['total'] === 0) {
            return $stats;
        }

        $stats['blocked-percentage'] = (($stats['blocked'] / $stats['total']) * 100) . '%';
        $stats['open-percentage'] = (($stats['open'] / $stats['total']) * 100) . '%';;
        $stats['unknown-percentage'] = (($stats['unknown'] / $stats['total']) * 100) . '%';;

        return $stats;
    }
}