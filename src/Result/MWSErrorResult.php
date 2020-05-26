<?php


namespace Dpash\AmazonMWS\Result;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\BadResponseException;

class MWSErrorResult
{
    /**
     * @var \SimpleXMLElement|string
     */
    public $type;
    /**
     * @var \SimpleXMLElement|string
     */
    public $code;
    /**
     * @var \SimpleXMLElement|string
     */
    public $message;
    /**
     * @var \SimpleXMLElement|string
     */
    public $requestId;

    /**
     * @var \GuzzleHttp\Promise\PromiseInterface|ResponseInterface|null
     */
    public $response;

    /**
     * MWSErrorResult constructor.
     * @param ResponseInterface $response
     */
    public function __construct(BadResponseException $exception)
    {
        if ($exception->hasResponse()) {
            $this->response = $exception->getResponse();
            $body = $this->response->getBody();
            if (strpos($body, '<ErrorResponse') !== false) {
                $error = simplexml_load_string($body);
                $this->type = $error->Error->Type;
                $this->code = $error->Error->Code;
                $this->message = $error->Error->Message;
                $this->requestId = $error->RequestId;
            } else {
                $this->message = $exception->getMessage();
            }
        } else {
            $this->message = $exception->getMessage();
        }
    }


}
