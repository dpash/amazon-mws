<?php


namespace Dpash\AmazonMWS\Result;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\BadResponseException;

class MWSErrorResult
{
    public string $type;
    public string $code;
    public string $message;
    public string $requestId;

    public ResponseInterface $response;

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
