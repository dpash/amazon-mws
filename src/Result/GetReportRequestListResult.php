<?php


namespace Dpash\AmazonMWS\Result;


class GetReportRequestListResult
{

    /**
     * @var string
     */
    private $nextToken;
    /**
     * @var bool
     */
    private $hasNext = false;
    /**
     * @var ReportRequestInfo[]
     */
    private $info = [];

    /**
     * GetReportRequestListResult constructor.
     * @param MWSResult $result
     * @throws \Exception
     */
    public function __construct(MWSResult $result)
    {
        $body = $result->getBodyAsHash();
        $this->nextToken = $body['GetReportRequestListResult']['NextToken'];
        $this->hasNext = $body['GetReportRequestListResult']['HasNext'];
        foreach($body['GetReportRequestListResult']['ReportRequestInfo'] as $info) {
            $this->info[] = new ReportRequestInfo($info);
        }
    }

    /**
     * @return mixed
     */
    public function getNextToken() : string
    {
        return $this->nextToken;
    }

    /**
     * @return mixed
     */
    public function getHasNext() : bool
    {
        return $this->hasNext;
    }

    /**
     * @return ReportRequestInfo[]
     */
    public function getInfo() : array
    {
        return $this->info;
    }


}
