<?php


namespace Dpash\AmazonMWS\Exceptions;

use GuzzleHttp\Exception\BadResponseException;

class RequestThrottledException extends MWSException
{
    /**
     * @var \DateTime
     */
    private $quota_reset;

    public function __construct(string $message, BadResponseException $exception) {
        parent::__construct($message, 0, $exception);
        if ($exception->getResponse()->hasHeader('x-mws-quota-resetsOn')) {
            $this->quota_reset = new \DateTime($exception->getResponse()->getHeader('x-mws-quota-resetsOn')[0]);
        } else {
            print_r($exception->getResponse()->getBody()->getContents());
            $this->quota_reset = new \DateTime('now');
        }
    }
}
