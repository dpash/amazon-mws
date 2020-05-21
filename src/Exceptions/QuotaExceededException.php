<?php


namespace Dpash\AmazonMWS\Exceptions;


use GuzzleHttp\Exception\BadResponseException;

class QuotaExceededException extends MWSException
{
    private \DateTime $quota_reset;
    /**
     * QuotaExceededException constructor.
     */
    public function __construct(string $message, BadResponseException $exception) {
        parent::__construct($message, 0, $exception);
        $this->quota_reset =  new \DateTime($exception->getResponse()->getHeader('x-mws-quota-resetsOn')[0]);
    }

    public function secondsToReset() : int {
        $now = new \DateTime('now');
        $interval = $this->quota_reset->diff($now);
        print_r($interval);
        return $interval->s;
    }
}
