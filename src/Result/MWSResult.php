<?php


namespace Dpash\AmazonMWS\Result;

use GuzzleHttp\Psr7\Response;

class MWSResult
{

    /**
     * @var Response|object
     */
    public $response;
    /**
     * @var string
     */
    public $rawBody;
    public $body;
    /**
     * @var array
     */
    public $xmlBody = [];
    /**
     * @var int
     */
    public $quota_max = 0;
    /**
     * @var int
     */
    public $quota_remaining = 0;
    /**
     * @var \DateTime
     */
    public $quota_reset;

    /**
     * MWSResult constructor.
     * @param object $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
        $this->rawBody = (string) $response->getBody();

        if ($response->hasHeader('x-mws-quota-max')) {
            $this->quota_max = intval($response->getHeader('x-mws-quota-max')[0]);
        }
        if ($response->hasHeader('x-mws-quota-remaining')) {
            $this->quota_remaining = intval($response->getHeader('x-mws-quota-remaining')[0]);
        }
         if ($response->hasHeader('x-mws-quota-resetsOn')) {
             $this->quota_reset = new \DateTime($response->getHeader('x-mws-quota-resetsOn')[0]);
         } else {
            $this->quota_reset = new \DateTime('now');
         }

        if(preg_match('/^ERROR:/', $this->rawBody)) {
            throw new \Dpash\AmazonMWS\Exceptions\Exception($this->rawBody);
        }

        if (strpos(strtolower($response->getHeader('Content-Type')[0]), 'xml') !== false) {
            $this->xmlBody = $this->xmlToArray($this->rawBody);
            $this->body = $this->xmlBody;
        } else {
            $this->body = $this->rawBody;
        }
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->rawBody;
    }

    /**
     * @return array
     */
    public function getBodyAsHash(): array
    {
        return $this->xmlBody;
    }



    /**
     * Convert an xml string to an array
     * @param string $xmlstring
     * @return array
     */
    private function xmlToArray($xmlstring)
    {
        return json_decode(json_encode(simplexml_load_string($xmlstring)), true);
    }
}

