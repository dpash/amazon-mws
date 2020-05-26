<?php


namespace Dpash\AmazonMWS\Result;


trait HasResult
{

    /**
     * @var MWSResult
     */
    private $result;

    protected function setResult(MWSResult $result) {
        $this->result = $result;
    }

    public function getResult() : MWSResult {
        return $this->result;
    }
}
