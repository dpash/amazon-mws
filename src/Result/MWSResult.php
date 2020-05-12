<?php


namespace Dpash\AmazonMWS\Result;

use GuzzleHttp\Psr7\Response;

class MWSResult
{

    public Response $response;
    public string $rawBody;
    public $body;
    public array $xmlBody = [];

    /**
     * MWSResult constructor.
     * @param object $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
        $this->rawBody = (string) $response->getBody();

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
     * Convert an xml string to an array
     * @param string $xmlstring
     * @return array
     */
    private function xmlToArray($xmlstring)
    {
        return json_decode(json_encode(simplexml_load_string($xmlstring)), true);
    }
}

