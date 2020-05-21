<?php


namespace Dpash\AmazonMWS\Result;

use GuzzleHttp\Psr7\Response;

class MWSResult
{

    public Response $response;
    public string $rawBody;
    public $body;
    public array $xmlBody = [];
    public int $quota_max;
    public int $quota_remaining;
    public \DateTime $quota_reset;

    /**
     * MWSResult constructor.
     * @param object $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
        $this->rawBody = (string) $response->getBody();

        $this->quota_max = intval($response->getHeader('x-mws-quota-max')[0]);
        $this->quota_remaining =  intval($response->getHeader('x-mws-quota-remaining')[0]);
        $this->quota_reset =  new \DateTime($response->getHeader('x-mws-quota-resetsOn')[0]);

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

