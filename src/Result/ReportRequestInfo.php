<?php


namespace Dpash\AmazonMWS\Result;

use DateTime;

class ReportRequestInfo
{

    const STATUS_DONE = '_DONE_';
    const STATUS_DONE_NO_DATA = '_DONE_NO_DATA_';
    const STATUS_CANCELLED = '_CANCELLED_';
    const STATUS_SUBMITTED = 'SUBMITTED_';
    /**
     * @var string A unique report request identifier.
     */
    private $reportRequestId;

    /**
     * @var string 	The ReportType value requested.
     */
    private $reportType;

    /**
     * @var DateTime The start of a date range used for selecting the data to report.
     */
    private $startDate;


    /**
     * @var DateTime The end of a date range used for selecting the data to report.
     */
    private $endDate;

    /**
     * @var bool A Boolean value that indicates if a report is scheduled. The value is true if the report was scheduled; otherwise false.
     */
    private $scheduled;

    /**
     * @var DateTime The date when the report was submitted.
     */
    private $submittedDate;

    /**
     * @var string The processing status of the report.
     */
    private $processingStatus;

    /**
     * @var string The report identifier used to retrieve the report.
     */
    private $generatedReportId;

    /**
     * @var DateTime The date when the report processing started.
     */
    private $startedProcessingDate;

    /**
     * @var DateTime The date when the report processing completed.
     */
    private $completedDate;

    /**
     * ReportRequestInfo constructor.
     * @param array $info
     * @throws \Exception
     */
    public function __construct(array $info)
    {
        $this->reportType = $info['ReportType'];
        $this->processingStatus = $info['ReportProcessingStatus'];
        $this->endDate = $info['EndDate'];
        $this->scheduled = $info['Scheduled'];
        $this->reportRequestId =$info['ReportRequestId'];
        $this->submittedDate = $info['SubmittedDate'];
        $this->startDate = $info['StartDate'];

        switch ($this->processingStatus) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case '_DONE_':
                $this->generatedReportId = $info['GeneratedReportId'];
                // Intentional fall through
            case '_CANCELLED_';
            /** @noinspection PhpMissingBreakStatementInspection */
            case '_DONE_NO_DATA_':
                $this->completedDate = $info['CompletedDate'];
                // Intentional fall through
            case '_IN_PROGRESS_':
                $this->startedProcessingDate = $info['StartedProcessingDate'];
                break;
            case '_SUBMITTED_':
                // do nothing
                break;
            default:
                print_r($info);
                throw new \Exception("Unhandled processing status: ". $this->processingStatus);
        }

    }

    /**
     * @return string
     */
    public function getReportRequestId(): string
    {
        return $this->reportRequestId;
    }

    /**
     * @return string
     */
    public function getReportType(): string
    {
        return $this->reportType;
    }

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * @return DateTime
     */
    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    /**
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->scheduled;
    }

    /**
     * @return DateTime
     */
    public function getSubmittedDate(): DateTime
    {
        return $this->submittedDate;
    }

    /**
     * @return string
     */
    public function getProcessingStatus(): string
    {
        return $this->processingStatus;
    }

    /**
     * @return string
     */
    public function getGeneratedReportId(): string
    {
        return $this->generatedReportId;
    }

    /**
     * @return DateTime
     */
    public function getStartedProcessingDate(): DateTime
    {
        return $this->startedProcessingDate;
    }

    /**
     * @return DateTime
     */
    public function getCompletedDate(): DateTime
    {
        return $this->completedDate;
    }



}
