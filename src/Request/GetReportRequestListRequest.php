<?php


namespace Dpash\AmazonMWS\Request;


use DateTime;

class GetReportRequestListRequest
{


    /**
     * @var string[]
     *
     * A structured list of ReportRequestId values. If you pass in ReportRequestId values, other query conditions are
     * ignored.
     *
     * Default: All
     * Optional: yes
     */
    private $reportRequestIdList = [];


    /**
     * @var string[]
     *
     * A structured list of ReportType enumeration values.
     *
     * Optional: Yes
     * Default: All
     */
    private $reportTypeList = [];

    /**
     * @var string[]
     *    A structured list of report processing statuses by which to filter report requests.
     *
     * Range:
     * * _SUBMITTED_
     * * _IN_PROGRESS_
     * * _CANCELLED_
     * * _DONE_
     * * _DONE_NO_DATA_
     *
     * Optional: Yes
     * Default: All
     */
    private $reportProcessingStatusList = [];


    /**
     * @var int
     * A non-negative integer that represents the maximum number of report requests to return. If you specify a number
     * greater than 100, the request is rejected.
     *
     * Range: 1-100
     * Optional: Yes
     */
    private $maxCount = 100;

    /**
     * @var DateTime The start of the date range used for selecting the data to report, in ISO 8601 date time format.
     *
     * Optional: Yes
     * Default: 90 days ago
     */
    private $requestedFromDate = null;


    /**
     * @var DateTime
     * The end of the date range used for selecting the data to report, in ISO 8601 date time format.
     *
     * Optional: Yes
     * Default: Now
     */
    private $requestedToDate = null;

    /**
     * @param string[] $reportRequestIdList
     * @return GetReportRequestListRequest
     */
    public function setReportRequestIdList(array $reportRequestIdList): GetReportRequestListRequest
    {
        $this->reportRequestIdList = $reportRequestIdList;
        return $this;
    }

    /**
     * @param string[] $reportTypeList
     * @return GetReportRequestListRequest
     */
    public function setReportTypeList(array $reportTypeList): GetReportRequestListRequest
    {
        $this->reportTypeList = $reportTypeList;
        return $this;
    }

    /**
     * @param string[] $reportProcessingStatusList
     * @return GetReportRequestListRequest
     */
    public function setReportProcessingStatusList(array $reportProcessingStatusList): GetReportRequestListRequest
    {
        $this->reportProcessingStatusList = $reportProcessingStatusList;
        return $this;
    }

    /**
     * @param int $maxCount
     * @return GetReportRequestListRequest
     */
    public function setMaxCount(int $maxCount): GetReportRequestListRequest
    {
        $this->maxCount = $maxCount;
        return $this;
    }

    /**
     * @param DateTime $requestedFromDate
     * @return GetReportRequestListRequest
     */
    public function setRequestedFromDate(DateTime $requestedFromDate): GetReportRequestListRequest
    {
        $this->requestedFromDate = $requestedFromDate;
        return $this;
    }

    /**
     * @param DateTime $requestedToDate
     * @return GetReportRequestListRequest
     */
    public function setRequestedToDate(DateTime $requestedToDate): GetReportRequestListRequest
    {
        $this->requestedToDate = $requestedToDate;
        return $this;
    }


    public function getQuery() : array
    {
        // If report request ids are requested, everything else is ignored
        if (!empty($this->reportRequestIdList)) {
            $query = $this->arrayToParam($this->reportRequestIdList, 'ReportRequestIdList.Id');
            return $query;
        }
        $query  = [
            'MaxCount' => $this->maxCount,
        ];
        $query = array_merge($query, $this->arrayToParam($this->reportProcessingStatusList, 'ReportProcessingStatusList.Status'));
        $query = array_merge($query, $this->arrayToParam($this->reportTypeList, 'ReportTypeList.Type'));
        return $query;
    }

    private function arrayToParam(array $array, string $parameter ) {
        $ret = [];
        $counter = 1;
        if (count($array)) {
            foreach($array as $item) {
                $ret[$parameter . '.' . $counter] = $item;
                $counter++;
            }
        }
        return $ret;
    }
}
