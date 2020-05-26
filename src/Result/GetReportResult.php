<?php


namespace Dpash\AmazonMWS\Result;


use League\Csv\Reader;

class GetReportResult
{

    use HasResult;

    /**
     * @var bool
     */
    public $success = true;
    /**
     * @var array
     */
    public $data = [];
    /**
     * @var mixed|string
     */
    public $status = "_UNKNOWN_";

    /**
     * GetReportResult constructor.
     * @param bool $success
     * @param string $status
     * @param array $data
     */
    public function __construct(GetReportRequestStatusResult $reportStatusResult, MWSResult $result = null)
    {
        $this->status = $reportStatusResult->processingStatus;

        if (is_null($result)) {
            $this->setResult($reportStatusResult->getResult());
            $this->success = false;
            return;
        }

        $this->setResult($result);

        if (!is_string($result->rawBody)) {
            $this->success = false;
            return;
        }

        $csv = Reader::createFromString($result->body);
        $csv->setDelimiter("\t");
        $headers = $csv->fetchOne();
        $data = [];
        foreach ($csv->setOffset(1)->fetchAll() as $row) {
            $data[] = array_combine($headers, $row);
        }
        $this->data = $data;
    }


}
