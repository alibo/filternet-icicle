<?php
namespace Filternet\Icicle\Results;

use Filternet\Icicle\Site;

class Http implements \JsonSerializable
{
    /**
     * @var Site
     */
    private $site;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * Http constructor.
     * @param Site $site
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        $titlePattern = '/<title>10\.10\.34\.3[4-6]<\/title>/ism';
        $iframePattern = '/<iframe.*?src="http:\/\/10\.10\.34\.3[4-6]/ism';

        return preg_match($titlePattern, $this->body) || preg_match($iframePattern, $this->body);

    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        array_walk($headers, function ($item, $key) {
            $this->headers[$key] = implode(' ', $item);
        });
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body)
    {
        $this->body = $body;
    }

    /**
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        $titlePattern = '/<title>(.*?)<\/title>/ism';

        if (preg_match($titlePattern, $this->body, $matches)) {
            return trim($matches[1]);
        }

        return null;
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
            'title' => $this->getTitle(),
            'http' => [
                'status' => $this->getStatusCode(),
                'headers' => (array)$this->getHeaders(),
                'body' => (string)$this->getBody()
            ]
        ];
    }
}