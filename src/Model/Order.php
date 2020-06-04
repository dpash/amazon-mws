<?php


namespace Dpash\AmazonMWS\Model;


use DateTime;
use Exception;

/**
 * Class Order
 * @package Dpash\AmazonMWS\Model
 * https://docs.developer.amazonservices.com/en_US/orders-2013-09-01/Orders_Datatypes.html#Order
 */
class Order
{

    /**
     * @var string
     * An Amazon-defined order identifier, in 3-7-7 format.
     */
    private $amazonOrderId;

    /**
     * @var string|null
     * A seller-defined order identifier.
     * Optional
     */
    private   $sellerOrderId;

    /**
     * @var DateTime
     * The date when the order was created.
     */
    private $purchaseDate;

    /**
     * @var DateTime
     * The date when the order was last updated.
     * Note: LastUpdateDate is returned with an incorrect date for orders that were last updated before 2009-04-01.
     */
    private $lastUpdateDate;

    /**
     * @var string
     * The current order status.
     */
    private $orderStatus;

    /**
     * @var string|null
     * How the order was fulfilled: by Amazon (AFN) or by the seller (MFN).
     * Optional
     */
    private   $fulfillmentChannel;

    /**
     * @var string|null
     * The sales channel of the first item in the order.
     * Optional
     */
    private $salesChannel;

    /**
     * @var string|null
     * The order channel of the first item in the order.
     * Optional
     */
    private $orderChannel;

    /**
     * @var string|null
     * The shipment service level of the order.
     * Optional
     */
    private $shipServiceLevel;


    /**
     * @var Address|null
     * The shipping address for the order.
     * Optional
     */
    private $shippingAddress;

    /**
     * @var Money|null
     * The total charge for the order.
     * Optional
     */
    private $orderTotal;

    /**
     * @var int
     * The number of items shipped.
     * Optional
     */
    private $numberOfItemsShipped = 0;

    /**
     * @var int
     * The number of items unshipped.
     * Optional
     */
    private $numberOfItemsUnshipped = 0;

    /**
     * @var PaymentExecutionDetail|null
     * Information about sub-payment methods for a Cash On Delivery (COD) order. A COD order is an order with
     * PaymentMethod = COD. Contains one or more PaymentExecutionDetailItem response elements.
     *
     * Note: For a COD order that is paid for using one sub-payment method, one PaymentExecutionDetailItem response
     * element is returned, with PaymentExecutionDetailItem/PaymentMethod = COD. For a COD order that is paid for
     * using multiple sub-payment methods, two or more PaymentExecutionDetailItem response elements are returned.
     *
     * Optional
     */
    private $paymentExecutionDetail;

    /**
     * @var string
     * The payment method for the order. This response element is limited to Cash On Delivery (COD) and Convenience
     * Store (CVS) payment methods. Unless you need the specific COD payment information provided by the
     * PaymentExecutionDetailItem element, we recommend using the PaymentMethodDetails response element to get
     * payment method information.
     *
     * PaymentMethod values:
     *
     * * COD - Cash On Delivery. Available only in Japan (JP).
     * * CVS - Convenience Store. Available only in JP.
     * * Other - A payment method other than COD and CVS.
     *
     * Note: Orders with PaymentMethod = COD can be paid for using multiple sub-payment methods. Each sub-payment
     * method is represented by a PaymentExecutionDetailItem object.
     *
     * Optional
     */
    private $paymentMethod = 'Other';

    /**
     * @var PaymentMethodDetails|null
     *
     * A list of payment methods for the order.
     * Optional
     */
    private $paymentMethodDetails;

    /**
     * @var bool
     *
     *  true if this is a replacement order.
     * Optional
     */
    private $isReplacementOrder = false;

    /**
     * @var string|null
     * The AmazonOrderId value for the order that is being replaced.
     *
     * No. Returned only if IsReplacementOrder = true
     */
    private $replacedOrderId;

    /**
     * @var string|null
     * The anonymized identifier for the Marketplace where the order was placed.
     * Optional
     */
    private $marketplaceId;

    /**
     * @var string|null
     * The anonymized e-mail address of the buyer.
     * Optional
     */
    private $buyerEmail;

    /**
     * @var string|null
     * The name of the buyer.
     * Optional
     */
    private $buyerName;

    /**
     * @var string|null
     *  	The county of the buyer.
     * This element is used only in the Brazil marketplace.
     */
    private $buyerCounty;

    /**
     * @var BuyerTaxInfo|null
     * Tax information about the buyer.
     */
    private $buyerTaxInfo;

    /**
     * @var string|null
     * The shipment service level category of the order.
     *
     * ShipmentServiceLevelCategory values:
     *  * Expedited,
     *  * FreeEconomy,
     *  * NextDay,
     *  * SameDay,
     *  * SecondDay,
     *  * Scheduled,
     *  * Standard
     */
    private  $shipmentServiceLevelCategory;


    /**
     * @var string|null
     * The status of the Amazon Easy Ship order. This element is included only for Amazon Easy Ship orders.
     *
     * EasyShipShipmentStatus values:
     * * PendingPickUp,
     * * LabelCanceled,
     * * PickedUp,
     * * OutForDelivery,
     * * Damaged,
     * * Delivered,
     * * RejectedByBuyer,
     * * Undeliverable,
     * * ReturnedToSeller,
     * * ReturningToSeller
     *
     * Amazon Easy Ship is available only in the India marketplace.
     */
    private $easyShipShipmentStatus;

    /**
     * @var string
     * The type of the order.
     *
     * OrderType values:
     *
     * * StandardOrder - An order that contains items for which you currently have inventory in stock.
     * * Preorder - An order that contains items with a release date that is in the future.
     * * SourcingOnDemandOrder - A Sourcing On Demand order.
     *
     * Note: Preorder and SourcingOnDemandOrder are possible OrderType values only in the Japan marketplace.
     */
    private $orderType = 'StandardOrder';

    /**
     * @var DateTime|null
     * The start of the time period that you have committed to ship the order. In ISO 8601 date time format.
     * Note: EarliestShipDate might not be returned for orders placed before February 1, 2013.
     */
    private   $earliestShipDate;

    /** @var  DateTime|null latestShipDate
     * The end of the time period that you have committed to ship the order. In ISO 8601 date time format.
     * Note: LatestShipDate might not be returned for orders placed before February 1, 2013.
     */
    private $latestShipDate;

    /**
     * @var DateTime|null
     * The start of the time period that you have commited to fulfill the order. In ISO 8601 date time format.
     * Returned only for seller-fulfilled orders that do not have a PendingAvailability, Pending, or Canceled status.
     */
    private $earliestDeliveryDate;

    /**
     * @var DateTime|null
     * The end of the time period that you have commited to fulfill the order. In ISO 8601 date time format.
     * Returned only for seller-fulfilled orders that do not have a PendingAvailability, Pending, or Canceled status.
     */
    private $latestDeliveryDate;

    /**
     * @var bool
     * true if the order is an Amazon Business order. An Amazon Business order is an order where the buyer is a Verified Business Buyer.
     *
     * IsBusinessOrder values:
     *
     * * true - The order is an Amazon Business order.
     * * false - The order is not an Amazon Business order.
     * Optional
     */
    private $isBusinessOrder = false;

    /**
     * @var bool
     * true if the items in this order were bought and re-sold by Amazon Business EU SARL (ABEU).
     *
     * IsSoldByAB values:
     *
     * * true - The items in this order were bought and re-sold by ABEU.
     * * false - The items in this order were not bought and re-sold by ABEU.
     * Optional
     */
    private $isSoldByAB = false;

    /**
     * @var string|null
     * The purchase order (PO) number entered by the buyer at checkout.
     * Returned only for orders where the buyer entered a PO number at checkout.
     * Optional
     */
    private $purchaseOrderNumber;

    /**
     * @var bool
     * true if the order is a seller-fulfilled Amazon Prime order.
     * Optional
     */
    private $isPrime = false;

    /**
     * @var bool
     *
     * true if the order has a Premium Shipping Service Level Agreement. For more information about Premium Shipping orders, see "Premium Shipping Options" in the Seller Central Help.
     *
     * Optional
     */
    private $isPremiumOrder = false;

    /**
     * @var bool
     *
     * true if the order is a Global Express order. For more information about the Global Express program, see "Global Express" in the Seller Central Help.
     *
     * Optional
     */
    private $isGlobalExpressEnabled = false;


    /**
     * @var DateTime|null
     *
     * Indicates the date by which the seller must respond to the buyer with an Estimated Ship Date.
     * Returned only for Sourcing on Demand orders.
     *
     * Optional
     */
    private $promiseResponseDueDate;


    /**
     * @var bool
     * true if the Estimated Ship Date is set for the order.
     * Returned only for Sourcing on Demand orders.
     *
     * Optional
     */
    private $isEstimatedShipDateSet = false;

    /**
     * Order constructor.
     * @param array $data associate array of order data
     * @throws Exception
     */
    public function __construct(array $data)
    {
        if(!array_key_exists('AmazonOrderId', $data)) {
            throw new Exception("Not an order object");
        }

        // Mandatory fields
        $this->amazonOrderId = $data['AmazonOrderId'];
        $this->purchaseDate = new DateTime($data['PurchaseDate']);
        $this->lastUpdateDate = new DateTime($data['LastUpdateDate']);
        $this->orderStatus = $data['OrderStatus'];

        if (array_key_exists('SellerOrderId', $data)) {
            $this->sellerOrderId = $data['SellerOrderId'];
        }
        if (array_key_exists('FulfillmentChannel', $data)) {
            $this->fulfillmentChannel = $data['FulfillmentChannel'];
        }
        if (array_key_exists('SalesChannel', $data)) {
            $this->salesChannel = $data['SalesChannel'];
        }
        if (array_key_exists('OrderChannel', $data)) {
            $this->orderChannel = $data['OrderChannel'];
        }
        if (array_key_exists('ShipServiceLevel', $data)) {
            $this->shipServiceLevel = $data['ShipServiceLevel'];
        }
        if (array_key_exists('ShippingAddress', $data)) {
            $this->shippingAddress = new Address($data['ShippingAddress']);
        }
        if (array_key_exists('OrderTotal', $data)) {
            $this->orderTotal = new Money($data['OrderTotal']);
        }
        if (array_key_exists('NumberOfItemsShipped', $data)) {
            $this->numberOfItemsShipped = $data['NumberOfItemsShipped'];
        }
        if (array_key_exists('NumberOfItemsShipped', $data)) {
            $this->numberOfItemsUnshipped = $data['NumberOfItemsShipped'];
        }
        if (array_key_exists('PaymentExecutionDetail', $data)) {
            $this->paymentExecutionDetail = new PaymentExecutionDetail($data['PaymentExecutionDetail']);
        }
        if (array_key_exists('PaymentMethod', $data)) {
            $this->paymentMethod = $data['PaymentMethod'];
        }
        if (array_key_exists('PaymentMethodDetails', $data)) {
            $this->paymentMethodDetails = $data['PaymentMethodDetails'];
        }
        if (array_key_exists('IsReplacementOrder', $data)) {
            $this->isReplacementOrder = $data['IsReplacementOrder'];
        }
        if (array_key_exists('ReplacedOrderId', $data)) {
            $this->sellerOrderId = $data['ReplacedOrderId'];
        }
        if (array_key_exists('MarketplaceId', $data)) {
            $this->marketplaceId = $data['MarketplaceId'];
        }
        if (array_key_exists('BuyerEmail', $data)) {
            $this->buyerEmail = $data['BuyerEmail'];
        }
        if (array_key_exists('BuyerName', $data)) {
            $this->buyerName = $data['BuyerName'];
        }
        if (array_key_exists('BuyerCounty', $data)) {
            $this->buyerCounty = $data['BuyerCounty'];
        }
        if (array_key_exists('BuyerTaxInfo', $data)) {
            $this->buyerTaxInfo = new BuyerTaxInfo($data['BuyerTaxInfo']);
        }
        if (array_key_exists('ShipmentServiceLevelCategory', $data)) {
            $this->shipmentServiceLevelCategory = $data['ShipmentServiceLevelCategory'];
        }
        if (array_key_exists('EasyShipShipmentStatus', $data)) {
            $this->easyShipShipmentStatus = $data['EasyShipShipmentStatus'];
        }
        if (array_key_exists('OrderType', $data)) {
            $this->orderType = $data['OrderType'];
        }
        if (array_key_exists('EarliestShipDate', $data)) {
            $this->earliestShipDate = new DateTime($data['EarliestShipDate']);
        }
        if (array_key_exists('LatestShipDate', $data)) {
            $this->latestShipDate = new DateTime($data['LatestShipDate']);
        }
        if (array_key_exists('EarliestDeliveryDate', $data)) {
            $this->earliestDeliveryDate = new DateTime($data['EarliestDeliveryDate']);
        }
        if (array_key_exists('LatestDeliveryDate', $data)) {
            $this->latestDeliveryDate = new DAteTime($data['LatestDeliveryDate']);
        }
        if (array_key_exists('IsBusinessOrder', $data)) {
            $this->isBusinessOrder = $data['IsBusinessOrder'];
        }
        if (array_key_exists('IsSoldByAB', $data)) {
            $this->isSoldByAB = $data['IsSoldByAB'];
        }
        if (array_key_exists('PurchaseOrderNumber', $data)) {
            $this->purchaseOrderNumber = $data['PurchaseOrderNumber'];
        }
        if (array_key_exists('IsPrime', $data)) {
            $this->isPrime = $data['IsPrime'];
        }
        if (array_key_exists('IsPremiumOrder', $data)) {
            $this->isPremiumOrder = $data['IsPremiumOrder'];
        }
        if (array_key_exists('IsGlobalExpressEnabled', $data)) {
            $this->isGlobalExpressEnabled = $data['IsGlobalExpressEnabled'];
        }
        if (array_key_exists('PromiseResponseDueDate', $data)) {
            $this->promiseResponseDueDate = new DateTime($data['PromiseResponseDueDate']);
        }
        if (array_key_exists('IsEstimatedShipDateSet', $data)) {
            $this->isEstimatedShipDateSet = $data['IsEstimatedShipDateSet'];
        }

    }


    /**
     * @return string
     */
    public function getAmazonOrderId(): string
    {
        return $this->amazonOrderId;
    }

    /**
     * @return string|null
     */
    public function getSellerOrderId(): ?string
    {
        return $this->sellerOrderId;
    }

    /**
     * @return DateTime
     */
    public function getPurchaseDate(): DateTime
    {
        return $this->purchaseDate;
    }

    /**
     * @return DateTime
     */
    public function getLastUpdateDate(): DateTime
    {
        return $this->lastUpdateDate;
    }

    /**
     * @return string
     */
    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    /**
     * @return string|null
     */
    public function getFulfillmentChannel(): ?string
    {
        return $this->fulfillmentChannel;
    }

    /**
     * @return string|null
     */
    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    /**
     * @return string|null
     */
    public function getOrderChannel(): ?string
    {
        return $this->orderChannel;
    }

    /**
     * @return string|null
     */
    public function getShipServiceLevel(): ?string
    {
        return $this->shipServiceLevel;
    }

    /**
     * @return Address|null
     */
    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    /**
     * @return Money|null
     */
    public function getOrderTotal(): ?Money
    {
        return $this->orderTotal;
    }

    /**
     * @return int
     */
    public function getNumberOfItemsShipped(): int
    {
        return $this->numberOfItemsShipped;
    }

    /**
     * @return int
     */
    public function getNumberOfItemsUnshipped(): int
    {
        return $this->numberOfItemsUnshipped;
    }

    /**
     * @return PaymentExecutionDetail|null
     */
    public function getPaymentExecutionDetail(): ?PaymentExecutionDetail
    {
        return $this->paymentExecutionDetail;
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @return PaymentMethodDetails|null
     */
    public function getPaymentMethodDetails(): ?PaymentMethodDetails
    {
        return $this->paymentMethodDetails;
    }

    /**
     * @return bool
     */
    public function isReplacementOrder(): bool
    {
        return $this->isReplacementOrder;
    }

    /**
     * @return string|null
     */
    public function getReplacedOrderId(): ?string
    {
        return $this->replacedOrderId;
    }

    /**
     * @return string|null
     */
    public function getMarketplaceId(): ?string
    {
        return $this->marketplaceId;
    }

    /**
     * @return string|null
     */
    public function getBuyerEmail(): ?string
    {
        return $this->buyerEmail;
    }

    /**
     * @return string|null
     */
    public function getBuyerName(): ?string
    {
        return $this->buyerName;
    }

    /**
     * @return string|null
     */
    public function getBuyerCounty(): ?string
    {
        return $this->buyerCounty;
    }

    /**
     * @return BuyerTaxInfo|null
     */
    public function getBuyerTaxInfo(): ?BuyerTaxInfo
    {
        return $this->buyerTaxInfo;
    }

    /**
     * @return string|null
     */
    public function getShipmentServiceLevelCategory(): ?string
    {
        return $this->shipmentServiceLevelCategory;
    }

    /**
     * @return string|null
     */
    public function getEasyShipShipmentStatus(): ?string
    {
        return $this->easyShipShipmentStatus;
    }

    /**
     * @return string
     */
    public function getOrderType(): string
    {
        return $this->orderType;
    }

    /**
     * @return DateTime|null
     */
    public function getEarliestShipDate(): ?DateTime
    {
        return $this->earliestShipDate;
    }

    /**
     * @return DateTime|null
     */
    public function getLatestShipDate(): ?DateTime
    {
        return $this->latestShipDate;
    }

    /**
     * @return DateTime|null
     */
    public function getEarliestDeliveryDate(): ?DateTime
    {
        return $this->earliestDeliveryDate;
    }

    /**
     * @return DateTime|null
     */
    public function getLatestDeliveryDate(): ?DateTime
    {
        return $this->latestDeliveryDate;
    }

    /**
     * @return bool
     */
    public function isBusinessOrder(): bool
    {
        return $this->isBusinessOrder;
    }

    /**
     * @return bool
     */
    public function isSoldByAB(): bool
    {
        return $this->isSoldByAB;
    }

    /**
     * @return string|null
     */
    public function getPurchaseOrderNumber(): ?string
    {
        return $this->purchaseOrderNumber;
    }

    /**
     * @return bool
     */
    public function isPrime(): bool
    {
        return $this->isPrime;
    }

    /**
     * @return bool
     */
    public function isPremiumOrder(): bool
    {
        return $this->isPremiumOrder;
    }

    /**
     * @return bool
     */
    public function isGlobalExpressEnabled(): bool
    {
        return $this->isGlobalExpressEnabled;
    }

    /**
     * @return DateTime|null
     */
    public function getPromiseResponseDueDate(): ?DateTime
    {
        return $this->promiseResponseDueDate;
    }

    /**
     * @return bool
     */
    public function isEstimatedShipDateSet(): bool
    {
        return $this->isEstimatedShipDateSet;
    }









}
