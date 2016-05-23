<?php
namespace Filternet\Icicle;

use Filternet\Icicle\Results\Dns;
use Filternet\Icicle\Results\Http;
use Filternet\Icicle\Results\Sni;

class SyncLogger
{
    /**
     * @var string
     */
    private $path;

    /**
     * Logger constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function logHttp(Http $http)
    {
        $filename = $this->getFileName($http->getSite(), 'http');
        file_put_contents($filename, json_encode($http));
    }

    public function logDns(Dns $dns)
    {
        $filename = $this->getFileName($dns->getSite(), 'dns');
        file_put_contents($filename, json_encode($dns));
    }

    public function logSni(Sni $sni)
    {
        $filename = $this->getFileName($sni->getSite(), 'sni');
        file_put_contents($filename, json_encode($sni));
    }

    /**
     * @param Site $site
     * @param string $type
     * @return string
     */
    public function getFileName(Site $site, string $type): string
    {
        return $this->path . DIRECTORY_SEPARATOR .
        "{$site->domain()}__{$site->rank()}__{$type}.json";
    }
}