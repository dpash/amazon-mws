<?php


namespace Dpash\AmazonMWS\Result;


class GetReportResult
{
    public bool $success = true;
    public array $data = [];
    public string $status = "_UNKNOWN_";

    /**
     * GetReportResult constructor.
     * @param bool $success
     * @param string $status
     * @param array $data
     */
    public function __construct(bool $success, string $status, array $data = null)
    {
        $this->success = $success;
        $this->status = $status;
        if (!is_null($data)) {
            $this->data = $data;
        }
    }


}