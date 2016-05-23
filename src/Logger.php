<?php
namespace Filternet\Icicle;

use Filternet\Icicle\Results\Dns;
use Filternet\Icicle\Results\Http;
use Filternet\Icicle\Results\Sni;
use Icicle\File;
use Icicle\Coroutine;

class Logger
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
        return Coroutine\create(function () use ($http) {
            $filename = $this->getFileName($http->getSite(), 'http');
            yield $this->write(json_encode($http), $filename);
        });
    }

    public function logDns(Dns $dns)
    {
        return Coroutine\create(function () use ($dns) {
            $filename = $this->getFileName($dns->getSite(), 'dns');
            yield $this->write(json_encode($dns), $filename);
        });
    }

    public function logSni(Sni $sni)
    {
        return Coroutine\create(function () use ($sni) {
            $filename = $this->getFileName($sni->getSite(), 'sni');
            yield $this->write(json_encode($sni), $filename);
        });
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

    /**
     * @param string $content
     * @param string $filename
     * @return \Generator
     */
    public function write(string $content, string $filename)
    {
        /** @var \Icicle\File\File $file */
        $file = (yield File\open($filename, 'w+'));

        try {
            yield $file->write($content);
        } finally {
            $file->close();
        }
    }


}