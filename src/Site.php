<?php
namespace Filternet\Icicle;

class Site
{
    /**
     * @var string
     */
    private $domain;
    /**
     * @var int
     */
    private $rank;

    public function __construct(string $domain, int $rank)
    {
        $this->domain = strtolower($domain);
        $this->rank = $rank;
    }

    /**
     * @return string
     */
    public function domain(): string
    {
        return $this->domain;
    }

    /**
     * @return int
     */
    public function rank(): int
    {
        return $this->rank;
    }

}