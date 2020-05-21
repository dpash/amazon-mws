<?php


namespace Dpash\AmazonMWS\Result;


trait HasResult
{

    private MWSResult $result;

    protected function setResult(MWSResult $result) {
        $this->result = $result;
    }

    public function getResult() : MWSResult {
        return $this->result;
    }
}
