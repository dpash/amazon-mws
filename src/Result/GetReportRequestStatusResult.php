<?php


namespace Dpash\AmazonMWS\Result;


class GetReportRequestStatusResult
{
    use HasResult;

    public bool $status = true;
    public array $data;
    public string $processingStatus;
    public string $reportId;

    /**
     * GetReportRequestStatusResult constructor.
     * @param MWSResult $result
     */
    public function __construct(MWSResult $result)
    {
        $this->setResult($result);

        $body = $result->xmlBody;

        if (!isset($body['GetReportRequestListResult']['ReportRequestInfo'])) {
            print_r($body);
            $this->status = false;
            return;
        }

        $this->data = $body['GetReportRequestListResult']['ReportRequestInfo'];
        $this->reportType = $this->data['ReportType'];
        $this->processingStatus = $this->data['ReportProcessingStatus'];
        $this->endDate = $this->data['EndDate'];
        $this->scheduled = $this->data['Scheduled'];
        $this->reportRequestId =$this->data['ReportRequestId'];
        $this->submittedDate = $this->data['SubmittedDate'];
        $this->startDate = $this->data['StartDate'];

        switch ($this->processingStatus) {
            case '_DONE_NO_DATA_':
            case '_DONE_':
                $this->completedDate = $this->data['CompletedDate'];
                $this->reportId = $this->data['GeneratedReportId'];
                // Intentional fall through
            case '_IN_PROGRESS_':
                $this->startedProcessingDate = $this->data['StartedProcessingDate'];
                break;
            case '_SUBMITTED_':
                // do nothing
                break;
            default:
                print_r($body);
                throw new \Exception("Unhandled processing status: ". $this->processingStatus);
        }
    }
}
