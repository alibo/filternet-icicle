<?php
namespace Filternet\Icicle\Results;

use Filternet\Icicle\Site;

class Sni implements \JsonSerializable, Result
{
    /**
     * @var Site
     */
    private $site;

    /**
     * @var string|null
     */
    private $error;

    /**
     * @var float
     */
    private $elapsedTime;

    /**
     * @var bool
     */
    private $unknown = false;

    /**
     * Sni constructor.
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
        if ($this->error && $this->hasSslError()) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $error
     */
    public function setError(string $error)
    {
        $this->error = $error;
    }

    public function hasSslError()
    {
        return preg_match('/ssl/ism', $this->error) && !$this->isTimeoutError($this->error);
    }

    public function isTimeoutError($error)
    {
        return preg_match('/timed out/ism', $error);
    }

    /**
     * @return float
     */
    public function getElapsedTime(): float
    {
        return $this->elapsedTime;
    }

    /**
     * @param float $elapsedTime
     */
    public function setElapsedTime(float $elapsedTime)
    {
        $this->elapsedTime = $elapsedTime;
    }

    /**
     * @return boolean
     */
    public function isUnknown()
    {
        return $this->unknown;
    }

    /**
     */
    public function setUnknown()
    {
        $this->unknown = true;
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
            'domain' => $this->site->domain(),
            'rank' => $this->site->rank(),
            'status' => $this->unknown ? 'unknown' : ($this->isBlocked() ? 'blocked' : 'open'),
            'elapsedTime' => $this->elapsedTime,
            'error' => (string)$this->getError()
        ];
    }
}