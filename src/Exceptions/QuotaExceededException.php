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
        if ($exception->getResponse()->hasHeader('x-mws-quota-resetsOn')) {
            $this->quota_reset = new \DateTime($exception->getResponse()->getHeader('x-mws-quota-resetsOn')[0]);
        } else {
            print_r($exception->getResponse()->getBody()->getContents());
            $this->quota_reset = new \DateTime('now');
        }
    }

    public function secondsToReset() : int {
        $now = new \DateTime('now');
        $interval = $this->quota_reset->diff($now);
        return $interval->s;
    }
}
