<?php
namespace Filternet\Icicle\Results;

interface Result
{
    /**
     * @return boolean
     */
    public function isUnknown();

    /**
     * @return boolean
     */
    public function isBlocked();

    /**
     * @return float
     */
    public function getElapsedTime(): float;

}