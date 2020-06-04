<?php


namespace Dpash\AmazonMWS\Result;


use Dpash\AmazonMWS\Model\Order;

class GetOrderResult
{

    use HasResult;

    /**
     * @var Order[]
     */
    private $orders;

    /**
     * GetOrderResult constructor.
     * @param MWSResult $result
     * @throws \Exception
     */
    public function __construct(MWSResult $result)
    {
        $this->setResult($result);

        $response = $result->getBodyAsHash();

        if (!isset($response['GetOrderResult']['Orders']['Order'])) {
            throw new \Exception("Invalid GetOrder result");
        }

        if (array_key_exists('AmazonOrderId', $response['GetOrderResult']['Orders']['Order'])) {
            $orders[] = new Order($response['GetOrderResult']['Orders']['Order']);
        } else {
            foreach ($response['GetOrderResult']['Orders']['Order'] as $order_data) {
                $orders[] = new Order($order_data);
            }
        }
    }

    /**
     * @return Order[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }


}
