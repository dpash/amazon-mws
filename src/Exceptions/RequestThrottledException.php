<?php


namespace Dpash\AmazonMWS\Exceptions;

use GuzzleHttp\Exception\BadResponseException;

class RequestThrottledException extends MWSException
{
    private \DateTime $quota_reset;

    public function __construct(string $message, BadResponseException $exception) {
        parent::__construct($message, 0, $exception);
        print_r($exception->getResponse());
        $this->quota_reset =  new \DateTime($exception->getResponse()->getHeader('x-mws-quota-resetsOn')[0]);
    }
}
