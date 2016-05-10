<?php
namespace Filternet\Icicle\Results;

use Filternet\Icicle\Site;

class Dns implements \JsonSerializable
{
    /**
     * @var Site
     */
    private $site;

    /**
     * @var array
     */
    private $ips;

    /**
     * Dns constructor.
     * @param Site $site
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    /**
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        return preg_match('/10\.10\.34\.3[4-6]/', $this->getIp());
    }

    /**
     * @return array
     */
    public function getIps(): array
    {
        return $this->ips;
    }

    public function getIp()
    {
        if (count($this->ips)) {
            return $this->ips[0];
        }

        return null;
    }

    /**
     * @param array $ips
     */
    public function setIps(array $ips)
    {
        $this->ips = $ips;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'status' => $this->isBlocked() ? 'blocked' : 'open',
            'ips' => (array)$this->getIps()
        ];
    }
}