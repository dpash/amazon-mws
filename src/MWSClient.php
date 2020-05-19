<?php
namespace Dpash\AmazonMWS;

use DateTime;
use Dpash\AmazonMWS\Exceptions\AccessDeniedException;
use Dpash\AmazonMWS\Exceptions\InputStreamDisconnectedException;
use Dpash\AmazonMWS\Exceptions\InternalErrorException;
use Dpash\AmazonMWS\Exceptions\InvalidAccessKeyIdException;
use Dpash\AmazonMWS\Exceptions\InvalidAddressException;
use Dpash\AmazonMWS\Exceptions\MWSException;
use Dpash\AmazonMWS\Exceptions\QuotaExceededException;
use Dpash\AmazonMWS\Exceptions\RequestThrottledException;
use Dpash\AmazonMWS\Result\GetReportResult;
use Dpash\AmazonMWS\Result\MWSErrorResult;
use Dpash\AmazonMWS\Result\MWSResult;
use Exception;
use League\Csv\Reader;
use League\Csv\Writer;
use SplTempFileObject;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Spatie\ArrayToXml\ArrayToXml;

class MWSClient{

    const SIGNATURE_METHOD = 'HmacSHA256';
    const SIGNATURE_VERSION = '2';
    const DATE_FORMAT = "Y-m-d\TH:i:s.\\0\\0\\0\\Z";
    const APPLICATION_NAME = 'Dpash\AmazonMWS\MwsClient';

    private array $config = [
        'Seller_Id' => null,
        'Marketplace_Id' => null,
        'Access_Key_ID' => null,
        'Secret_Access_Key' => null,
        'MWSAuthToken' => null,
        'Application_Version' => '0.0.*'
    ];

    private array $MarketplaceIds = [
        # https://docs.developer.amazonservices.com/en_US/dev_guide/DG_Endpoints.html
        # North America Region
        'A2Q3Y263D00KWC' => 'mws.amazonservices.com', # Brazil (BR)
        'A2EUQ1WTGCTBG2' => 'mws.amazonservices.ca', # Canada (CA)
        'A1AM78C64UM0Y8' => 'mws.amazonservices.com.mx', # Mexico (MX)
        'ATVPDKIKX0DER'  => 'mws.amazonservices.com', # United States (US)

        # Europe Region
        'A2VIGQ35RCS4UG' => 'mws.amazonservices.ae', # United Arab Emirates (AE)
        'A1PA6795UKMFR9' => 'mws-eu.amazonservices.com', # Germany (DE)
        'ARBP9OOSHTCHU'  => 'mws-eu.amazonservices.com', # Egypt (EG)
        'A1RKKUPIHCS9HS' => 'mws-eu.amazonservices.com', # Spain (ES)
        'A13V1IB3VIYZZH' => 'mws-eu.amazonservices.com', # France (FR)
        'A1F83G8C2ARO7P' => 'mws-eu.amazonservices.com', # United Kingdom (GB)
        'A21TJRUUN4KGV'  => 'mws.amazonservices.in', # India (IN)
        'APJ6JRA9NG5V4'  => 'mws-eu.amazonservices.com', # Italy (IT)
        'A1805IZSGTT6HS' => 'mws-eu.amazonservices.com', # Netherlands (NL)
        'A17E79C6D8DWNP' => 'mws-eu.amazonservices.com', # Saudi Arabia (SA)
        'A33AVAJ2PDY3EV' =>	'mws-eu.amazonservices.com', # Turkey (TR)

        # Far East Region
        'A19VAU5U5O7RUS' => 'mws-fe.amazonservices.com', # Singapore (SG)
        'A39IBJ37TRP1C6' => 'mws.amazonservices.com.au', # Australia (AU)
        'A1VC38T7YXB528' => 'mws.amazonservices.jp', # Japan (JP)
        'AAHKV2X7AFYLW'  => 'mws.amazonservices.com.cn', # China
    ];

    protected bool $debugNextFeed = false;
    protected ?Client $client = NULL;

    /**
     * MWSClient constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {

        foreach($config as $key => $value) {
            if (array_key_exists($key, $this->config)) {
                $this->config[$key] = $value;
            }
        }

        $required_keys = [
            'Marketplace_Id', 'Seller_Id', 'Access_Key_ID', 'Secret_Access_Key'
        ];

        foreach ($required_keys as $key) {
            if(is_null($this->config[$key])) {
                throw new Exception('Required field ' . $key . ' is not set');
            }
        }

        if (!isset($this->MarketplaceIds[$this->config['Marketplace_Id']])) {
            throw new Exception('Invalid Marketplace Id');
        }

        $this->config['Application_Name'] = self::APPLICATION_NAME;
        $this->config['Region_Host'] = $this->MarketplaceIds[$this->config['Marketplace_Id']];
        $this->config['Region_Url'] = 'https://' . $this->config['Region_Host'];

    }

    /**
     * Call this method to get the raw feed instead of sending it
     */
    public function debugNextFeed()
    {
        $this->debugNextFeed = true;
    }

    /**
     * A method to quickly check if the supplied credentials are valid
     * @return boolean
     */
    public function validateCredentials() : bool
    {
        try{
            $this->ListOrderItems('validate');
            return false;
        } catch(Exception $e) {
            if ($e->getMessage() == 'Invalid AmazonOrderId: validate') {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Returns the current competitive price of a product, based on ASIN.
     * @param array [$asin_array = []]
     * @return array
     * @throws Exception
     */
    public function GetCompetitivePricingForASIN(array $asin_array = []) : array
    {
        if (count($asin_array) > 20) {
            throw new Exception('Maximum amount of ASIN\'s for this call is 20');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        foreach($asin_array as $key){
            $query['ASINList.ASIN.' . $counter] = $key;
            $counter++;
        }

        $response = $this->request(
            'GetCompetitivePricingForASIN',
            $query
        )->xmlBody;

        if (isset($response['GetCompetitivePricingForASINResult'])) {
            $response = $response['GetCompetitivePricingForASINResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            return [];
        }

        $array = [];
        foreach ($response as $product) {
            if (isset($product['Product']['CompetitivePricing']['CompetitivePrices']['CompetitivePrice']['Price'])) {
                $array[$product['Product']['Identifiers']['MarketplaceASIN']['ASIN']] = $product['Product']['CompetitivePricing']['CompetitivePrices']['CompetitivePrice']['Price'];
            }
        }
        return $array;

    }

    /**
     * Returns the current competitive price of a product, based on SKU.
     * @param array [$sku_array = []]
     * @return array
     * @throws Exception
     */
    public function GetCompetitivePricingForSKU($sku_array = [])
    {
        if (count($sku_array) > 20) {
            throw new Exception('Maximum amount of SKU\'s for this call is 20');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        foreach($sku_array as $key){
            $query['SellerSKUList.SellerSKU.' . $counter] = $key;
            $counter++;
        }

        $response = $this->request(
            'GetCompetitivePricingForSKU',
            $query
        )->xmlBody;

        if (isset($response['GetCompetitivePricingForSKUResult'])) {
            $response = $response['GetCompetitivePricingForSKUResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            return [];
        }

        $array = [];
        foreach ($response as $product) {
            if (isset($product['Product']['CompetitivePricing']['CompetitivePrices']['CompetitivePrice']['Price'])) {
                $array[$product['Product']['Identifiers']['SKUIdentifier']['SellerSKU']]['Price'] = $product['Product']['CompetitivePricing']['CompetitivePrices']['CompetitivePrice']['Price'];
                $array[$product['Product']['Identifiers']['SKUIdentifier']['SellerSKU']]['Rank'] = $product['Product']['SalesRankings']['SalesRank'][1];
            }
        }
        return $array;

    }

    /**
     * Returns lowest priced offers for a single product, based on ASIN.
     * @param string $asin
     * @param string [$ItemCondition = 'New'] Should be one in: New, Used, Collectible, Refurbished, Club
     * @return array
     * @throws Exception
     */
    public function GetLowestPricedOffersForASIN($asin, $ItemCondition = 'New')
    {

        $query = [
            'ASIN' => $asin,
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'ItemCondition' => $ItemCondition
        ];

        return $this->request( 'GetLowestPricedOffersForASIN', $query )->body;

    }

    /**
     * Returns pricing information for your own offer listings, based on SKU.
     * @param array  [$sku_array = []]
     * @param string [$ItemCondition = null]
     * @return array
     * @throws Exception
     */
    public function GetMyPriceForSKU($sku_array = [], $ItemCondition = null)
    {
        if (count($sku_array) > 20) {
            throw new Exception('Maximum amount of SKU\'s for this call is 20');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        if (!is_null($ItemCondition)) {
            $query['ItemCondition'] = $ItemCondition;
        }

        foreach($sku_array as $key){
            $query['SellerSKUList.SellerSKU.' . $counter] = $key;
            $counter++;
        }

        $response = $this->request(
            'GetMyPriceForSKU',
            $query
        )->xmlBody;

        if (isset($response['GetMyPriceForSKUResult'])) {
            $response = $response['GetMyPriceForSKUResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            return [];
        }

        $array = [];
        foreach ($response as $product) {
            if (isset($product['@attributes']['status']) && $product['@attributes']['status'] == 'Success') {
                if (isset($product['Product']['Offers']['Offer'])) {
                    $array[$product['@attributes']['SellerSKU']] = $product['Product']['Offers']['Offer'];
                } else {
                    $array[$product['@attributes']['SellerSKU']] = [];
                }
            } else {
                $array[$product['@attributes']['SellerSKU']] = false;
            }
        }
        return $array;

    }

    /**
     * Returns pricing information for your own offer listings, based on ASIN.
     * @param array [$asin_array = []]
     * @param string [$ItemCondition = null]
     * @return array
     * @throws Exception
     */
    public function GetMyPriceForASIN($asin_array = [], $ItemCondition = null)
    {
        if (count($asin_array) > 20) {
            throw new Exception('Maximum amount of SKU\'s for this call is 20');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        if (!is_null($ItemCondition)) {
            $query['ItemCondition'] = $ItemCondition;
        }

        foreach($asin_array as $key){
            $query['ASINList.ASIN.' . $counter] = $key;
            $counter++;
        }

        $response = $this->request(
            'GetMyPriceForASIN',
            $query
        )->xmlBody;

        if (isset($response['GetMyPriceForASINResult'])) {
            $response = $response['GetMyPriceForASINResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            return [];
        }

        $array = [];
        foreach ($response as $product) {
            if (isset($product['@attributes']['status']) && $product['@attributes']['status'] == 'Success'  && isset($product['Product']['Offers']['Offer'])) {
                $array[$product['@attributes']['ASIN']] = $product['Product']['Offers']['Offer'];
            } else {
                $array[$product['@attributes']['ASIN']] = false;
            }
        }
        return $array;

    }

    /**
     * Returns pricing information for the lowest-price active offer listings for up to 20 products, based on ASIN.
     * @param array [$asin_array = []] array of ASIN values
     * @param array [$ItemCondition = null] Should be one in: New, Used, Collectible, Refurbished, Club. Default: All
     * @return array
     * @throws Exception
     */
    public function GetLowestOfferListingsForASIN($asin_array = [], $ItemCondition = null)
    {
        if (count($asin_array) > 20) {
            throw new Exception('Maximum amount of ASIN\'s for this call is 20');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        if (!is_null($ItemCondition)) {
            $query['ItemCondition'] = $ItemCondition;
        }

        foreach($asin_array as $key){
            $query['ASINList.ASIN.' . $counter] = $key;
            $counter++;
        }

        $response = $this->request(
            'GetLowestOfferListingsForASIN',
            $query
        )->xmlBody;

        if (isset($response['GetLowestOfferListingsForASINResult'])) {
            $response = $response['GetLowestOfferListingsForASINResult'];
            if (array_keys($response) !== range(0, count($response) - 1)) {
                $response = [$response];
            }
        } else {
            return [];
        }

        $array = [];
        foreach ($response as $product) {
            if (isset($product['Product']['LowestOfferListings']['LowestOfferListing'])) {
                $array[$product['Product']['Identifiers']['MarketplaceASIN']['ASIN']] = $product['Product']['LowestOfferListings']['LowestOfferListing'];
            } else {
                $array[$product['Product']['Identifiers']['MarketplaceASIN']['ASIN']] = false;
            }
        }
        return $array;

    }

    /**
     * Returns orders created or updated during a time frame that you specify.
     * @param DateTime $from, beginning of time frame
     * @param boolean $allMarketplaces , list orders from all marketplaces
     * @param array $states , an array containing orders states you want to filter on
     * @param string $FulfillmentChannels
     * @param DateTime $till  end of time frame
     * @return array
     * @throws Exception
     */
    public function ListOrders(DateTime $from, $allMarketplaces = false, $states = [
        'Unshipped', 'PartiallyShipped'
    ], $FulfillmentChannels = 'MFN', DateTime $till = null)
    {
        $query = [
            'CreatedAfter' => gmdate(self::DATE_FORMAT, $from->getTimestamp())
        ];

        if ($till !== null) {
          $query['CreatedBefore'] = gmdate(self::DATE_FORMAT, $till->getTimestamp());
        }

        $counter = 1;
        foreach ($states as $status) {
            $query['OrderStatus.Status.' . $counter] = $status;
            $counter = $counter + 1;
        }

        if ($allMarketplaces == true) {
            $counter = 1;
            foreach($this->MarketplaceIds as $key => $value) {
                $query['MarketplaceId.Id.' . $counter] = $key;
                $counter = $counter + 1;
            }
        }

        if (is_array($FulfillmentChannels)) {
            $counter = 1;
            foreach ($FulfillmentChannels as $fulfillmentChannel) {
                $query['FulfillmentChannel.Channel.' . $counter] = $fulfillmentChannel;
                $counter = $counter + 1;
            }
        } else {
            $query['FulfillmentChannel.Channel.1'] = $FulfillmentChannels;
        }

        $response = $this->request('ListOrders', $query)->xmlBody;

        if (isset($response['ListOrdersResult']['Orders']['Order'])) {
            if (isset($response['ListOrdersResult']['NextToken'])) {
                $data['ListOrders'] = $response['ListOrdersResult']['Orders']['Order'];
                $data['NextToken'] = $response['ListOrdersResult']['NextToken'];
                return $data;
            }

            $response = $response['ListOrdersResult']['Orders']['Order'];

            if (array_keys($response) !== range(0, count($response) - 1)) {
                return [$response];
            }

            return $response;

        } else {
            return [];
        }
    }

    /**
     * Returns orders created or updated during a time frame that you specify.
     * @param string $nextToken
     * @return array
     * @throws Exception
     */
    public function ListOrdersByNextToken($nextToken)
    {
        $query = [
            'NextToken' => $nextToken,
        ];

        $response = $this->request(
            'ListOrdersByNextToken',
            $query
        )->xmlBody;
        if (isset($response['ListOrdersByNextTokenResult']['Orders']['Order'])) {
            if(isset($response['ListOrdersByNextTokenResult']['NextToken'])){
                $data['ListOrders'] = $response['ListOrdersByNextTokenResult']['Orders']['Order'];
                $data['NextToken'] = $response['ListOrdersByNextTokenResult']['NextToken'];
                return $data;
            }
            $response = $response['ListOrdersByNextTokenResult']['Orders']['Order'];

            if (array_keys($response) !== range(0, count($response) - 1)) {
                return [$response];
            }
            return $response;
        } else {
            return [];
        }
    }

    /**
     * Returns an order based on the AmazonOrderId values that you specify.
     * @param string $AmazonOrderId
     * @return array if the order is found, false if not
     * @throws Exception
     */
    public function GetOrder($AmazonOrderIds)
    {
        if(!is_array($AmazonOrderIds)){
            $AmazonOrderIds = [$AmazonOrderIds];
        }

        $data = [];
        $i=1;

        foreach($AmazonOrderIds as $id){
            $data['AmazonOrderId.Id.'.($i++)] = $id;
        }

        $response = $this->request('GetOrder', $data)->xmlBody;

        if (isset($response['GetOrderResult']['Orders']['Order'])) {
            return $response['GetOrderResult']['Orders']['Order'];
        } else {
            return false;
        }
    }

    /**
     * Returns order items based on the AmazonOrderId that you specify.
     * @param string $AmazonOrderId
     * @return array
     * @throws Exception
     */
    public function ListOrderItems($AmazonOrderId)
    {
        $response = $this->request('ListOrderItems', [
            'AmazonOrderId' => $AmazonOrderId
        ])->xmlBody;

        $result = array_values($response['ListOrderItemsResult']['OrderItems']);

        if (isset($result[0]['QuantityOrdered'])) {
            return $result;
        } else {
            return $result[0];
        }
    }

    /**
     * Returns the parent product categories that a product belongs to, based on SellerSKU.
     * @param string $SellerSKU
     * @return array if found, false if not found
     * @throws Exception
     */
    public function GetProductCategoriesForSKU($SellerSKU)
    {
        $result = $this->request('GetProductCategoriesForSKU', [
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'SellerSKU' => $SellerSKU
        ])->xmlBody;

        if (isset($result['GetProductCategoriesForSKUResult']['Self'])) {
            return $result['GetProductCategoriesForSKUResult']['Self'];
        } else {
            return false;
        }
    }

    /**
     * Returns the parent product categories that a product belongs to, based on ASIN.
     * @param string $ASIN
     * @return array if found, false if not found
     * @throws Exception
     */
    public function GetProductCategoriesForASIN($ASIN)
    {
        $result = $this->request('GetProductCategoriesForASIN', [
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'ASIN' => $ASIN
        ])->xmlBody;

        if (isset($result['GetProductCategoriesForASINResult']['Self'])) {
            return $result['GetProductCategoriesForASINResult']['Self'];
        } else {
            return false;
        }
    }


    /**
     * Returns a list of products and their attributes, based on a list of ASIN, GCID, SellerSKU, UPC, EAN, ISBN, and JAN values.
     * @param array $asin_array A list of id's
     * @param string [$type = 'ASIN']  the identifier name
     * @return array
     * @throws Exception
     * @throws Exception
     */
    public function GetMatchingProductForId(array $asin_array, $type = 'ASIN')
    {
        $asin_array = array_unique($asin_array);

        if(count($asin_array) > 5) {
            throw new Exception('Maximum number of id\'s = 5');
        }

        $counter = 1;
        $array = [
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'IdType' => $type
        ];

        foreach($asin_array as $asin){
            $array['IdList.Id.' . $counter] = $asin;
            $counter++;
        }

        $response = $this->request(
            'GetMatchingProductForId',
            $array,
            null,
            true
        )->rawBody;

        $languages = [
            'de-DE', 'en-EN', 'es-ES', 'fr-FR', 'it-IT', 'en-US'
        ];

        $replace = [
            '</ns2:ItemAttributes>' => '</ItemAttributes>'
        ];

        foreach($languages as $language) {
            $replace['<ns2:ItemAttributes xml:lang="' . $language . '">'] = '<ItemAttributes><Language>' . $language . '</Language>';
        }

        $replace['ns2:'] = '';

        $response = $this->xmlToArray(strtr($response, $replace));

        if (isset($response['GetMatchingProductForIdResult']['@attributes'])) {
            $response['GetMatchingProductForIdResult'] = [
                0 => $response['GetMatchingProductForIdResult']
            ];
        }

        $found = [];
        $not_found = [];

        if (isset($response['GetMatchingProductForIdResult']) && is_array($response['GetMatchingProductForIdResult'])) {
            foreach ($response['GetMatchingProductForIdResult'] as $result) {

                //print_r($product);exit;

                $asin = $result['@attributes']['Id'];
                if ($result['@attributes']['status'] != 'Success') {
                    $not_found[] = $asin;
                } else {
                    if (isset($result['Products']['Product']['AttributeSets'])) {
                        $products[0] = $result['Products']['Product'];
                    }
                    else{
                        $products = $result['Products']['Product'];
                    }
                    foreach($products as $product){
                        $array = [];
                        if(isset($product['Identifiers']['MarketplaceASIN']['ASIN']))
                        {
                            $array["ASIN"] = $product['Identifiers']['MarketplaceASIN']['ASIN'];
                        }

                        foreach ($product['AttributeSets']['ItemAttributes'] as $key => $value) {
                            if (is_string($key) && is_string($value)) {
                                $array[$key] = $value;
                            }
                        }

                        if (isset($product['AttributeSets']['ItemAttributes']['Feature'])) {
                            $array['Feature'] = $product['AttributeSets']['ItemAttributes']['Feature'];
                        }

                        if (isset($product['AttributeSets']['ItemAttributes']['PackageDimensions'])) {
                            $array['PackageDimensions'] = array_map(
                                'floatval',
                                $product['AttributeSets']['ItemAttributes']['PackageDimensions']
                            );
                        }

                        if (isset($product['AttributeSets']['ItemAttributes']['ListPrice'])) {
                            $array['ListPrice'] = $product['AttributeSets']['ItemAttributes']['ListPrice'];
                        }

                        if (isset($product['AttributeSets']['ItemAttributes']['SmallImage'])) {
                            $image = $product['AttributeSets']['ItemAttributes']['SmallImage']['URL'];
                            $array['medium_image'] = $image;
                            $array['small_image'] = str_replace('._SL75_', '._SL50_', $image);
                            $array['large_image'] = str_replace('._SL75_', '', $image);
                        }
                        if (isset($product['Relationships']['VariationParent']['Identifiers']['MarketplaceASIN']['ASIN'])) {
                            $array['Parentage'] = 'child';
			    $array['Relationships'] = $product['Relationships']['VariationParent']['Identifiers']['MarketplaceASIN']['ASIN'];
                        }
			if (isset($product['Relationships']['VariationChild'])) {
		            $array['Parentage'] = 'parent';
	                }
                        if (isset($product['SalesRankings']['SalesRank'])) {
                            $array['SalesRank'] = $product['SalesRankings']['SalesRank'];
                        }
                        $found[$asin][] = $array;
                    }
                }
            }
        }

        return [
            'found' => $found,
            'not_found' => $not_found
        ];

    }

    /**
     * Returns a list of products and their attributes, ordered by relevancy, based on a search query that you specify.
     * @param string $query the open text query
     * @param string [$query_context_id = null] the identifier for the context within which the given search will be performed. see: http://docs.developer.amazonservices.com/en_US/products/Products_QueryContextIDs.html
     * @return array
     * @throws Exception
     * @throws Exception
     */
    public function ListMatchingProducts($query, $query_context_id = null)
    {

        if(trim($query) == "") {
            throw new Exception('Missing query');
        }

        $array = [
            'MarketplaceId' => $this->config['Marketplace_Id'],
            'Query' => urlencode($query),
            'QueryContextId' => $query_context_id
        ];

        $response = $this->request(
            'ListMatchingProducts',
            $array,
            null,
            true
        )->rawBody;



        $languages = [
            'de-DE', 'en-EN', 'es-ES', 'fr-FR', 'it-IT', 'en-US'
        ];

        $replace = [
            '</ns2:ItemAttributes>' => '</ItemAttributes>'
        ];

        foreach($languages as $language) {
            $replace['<ns2:ItemAttributes xml:lang="' . $language . '">'] = '<ItemAttributes><Language>' . $language . '</Language>';
        }

        $replace['ns2:'] = '';

        $response = $this->xmlToArray(strtr($response, $replace));

        if (isset($response['ListMatchingProductsResult'])) {
            return $response['ListMatchingProductsResult'];
        }else
            return ['ListMatchingProductsResult'=>[]];

    }


    /**
     * Returns a list of reports that were created in the previous 90 days.
     * @param array [$ReportTypeList = []]
     * @return array
     * @throws Exception
     */
    public function GetReportList($ReportTypeList = [])
    {
        $array = [];
        $counter = 1;
        if (count($ReportTypeList)) {
            foreach($ReportTypeList as $ReportType) {
                $array['ReportTypeList.Type.' . $counter] = $ReportType;
                $counter++;
            }
        }

        return $this->request('GetReportList', $array)->body;
    }

    /**
     * Returns your active recommendations for a specific category or for all categories for a specific marketplace.
     * @param string [$RecommendationCategory = null] One of: Inventory, Selection, Pricing, Fulfillment, ListingQuality, GlobalSelling, Advertising
     * @return array/false if no result
     * @throws Exception
     */
    public function ListRecommendations($RecommendationCategory = null)
    {
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        if (!is_null($RecommendationCategory)) {
            $query['RecommendationCategory'] = $RecommendationCategory;
        }

        $result = $this->request('ListRecommendations', $query)->xmlBody;

        if (isset($result['ListRecommendationsResult'])) {
            return $result['ListRecommendationsResult'];
        } else {
            return false;
        }

    }

    /**
     * Returns a list of marketplaces that the seller submitting the request can sell in, and a list of participations that include seller-specific information in that marketplace
     * @return array
     * @throws Exception
     */
    public function ListMarketplaceParticipations()
    {
        $result = $this->request('ListMarketplaceParticipations')->xmlBody;
        if (isset($result['ListMarketplaceParticipationsResult'])) {
            return $result['ListMarketplaceParticipationsResult'];
        } else {
            return $result;
        }
    }

    /**
     * Delete product's based on SKU
     * @param array $array array containing skus
     * @return array feed submission result
     * @throws Exception
     */
	public function deleteProductBySKU(array $array) {

		$feed = [
			'MessageType' => 'Product',
			'Message' => []
		];

		foreach ($array as $sku) {
			$feed['Message'][] = [
				'MessageID' => rand(),
				'OperationType' => 'Delete',
				'Product' => [
					'SKU' => $sku
				]
			];
		}

		return $this->SubmitFeed('_POST_PRODUCT_DATA_', $feed);
	}

    /**
     * Update a product's stock quantity
     * @param array $array array containing sku as key and quantity as value
     * @return array feed submission result
     * @throws Exception
     */
    public function updateStock(array $array)
    {
        $feed = [
            'MessageType' => 'Inventory',
            'Message' => []
        ];

        foreach ($array as $sku => $quantity) {
            $feed['Message'][] = [
                'MessageID' => rand(),
                'OperationType' => 'Update',
                'Inventory' => [
                    'SKU' => $sku,
                    'Quantity' => (int) $quantity
                ]
            ];
        }

        return $this->SubmitFeed('_POST_INVENTORY_AVAILABILITY_DATA_', $feed);

    }

    /**
     * Update a product's stock quantity
     *
     * @param array $array array containing arrays with next keys: [sku, quantity, latency]
     * @return array feed submission result
     * @throws Exception
     */
    public function updateStockWithFulfillmentLatency(array $array)
    {
        $feed = [
            'MessageType' => 'Inventory',
            'Message' => []
        ];

        foreach ($array as $item) {
            $feed['Message'][] = [
                'MessageID' => rand(),
                'OperationType' => 'Update',
                'Inventory' => [
                    'SKU' => $item['sku'],
                    'Quantity' => (int) $item['quantity'],
                    'FulfillmentLatency' => $item['latency']
                ]
            ];
        }

        return $this->SubmitFeed('_POST_INVENTORY_AVAILABILITY_DATA_', $feed);
    }

    /**
     * Update a product's price
     * @param array $standardprice an array containing sku as key and price as value
     * @param array $saleprice an optional array with sku as key and value consisting of an array with key/value pairs for SalePrice, StartDate, EndDate
     * Dates in DateTime object
     * Price has to be formatted as XSD Numeric Data Type (http://www.w3schools.com/xml/schema_dtypes_numeric.asp)
     * @return array feed submission result
     * @throws Exception
     */
    public function updatePrice(array $standardprice, array $saleprice = null) {

        $feed = [
            'MessageType' => 'Price',
            'Message' => []
        ];

        foreach ($standardprice as $sku => $price) {
            $feed['Message'][] = [
                'MessageID' => rand(),
                'Price' => [
                    'SKU' => $sku,
                    'StandardPrice' => [
                        '_value' => strval($price),
                        '_attributes' => [
                            'currency' => 'DEFAULT'
                        ]
                    ]
                ]
            ];

            if (isset($saleprice[$sku]) && is_array($saleprice[$sku])) {
                $feed['Message'][count($feed['Message']) - 1]['Price']['Sale'] = [
                    'StartDate' => $saleprice[$sku]['StartDate']->format(self::DATE_FORMAT),
                    'EndDate' => $saleprice[$sku]['EndDate']->format(self::DATE_FORMAT),
                    'SalePrice' => [
                        '_value' => strval($saleprice[$sku]['SalePrice']),
                        '_attributes' => [
                            'currency' => 'DEFAULT'
                        ]]
                ];
            }
        }

        return $this->SubmitFeed('_POST_PRODUCT_PRICING_DATA_', $feed);
    }

    /**
     * Post to create or update a product (_POST_FLAT_FILE_LISTINGS_DATA_)
     * @param object $MWSProduct or array of MWSProduct objects
     * @return array
     * @throws Exception
     */
    public function postProduct($MWSProduct) {

        if (!is_array($MWSProduct)) {
            $MWSProduct = [$MWSProduct];
        }

        $csv = Writer::createFromFileObject(new SplTempFileObject());

        $csv->setDelimiter("\t");
        $csv->setInputEncoding('iso-8859-1');

        $csv->insertOne(['TemplateType=Offer', 'Version=2014.0703']);

        $header = ['sku', 'price', 'quantity', 'product-id',
            'product-id-type', 'condition-type', 'condition-note',
            'ASIN-hint', 'title', 'product-tax-code', 'operation-type',
            'sale-price', 'sale-start-date', 'sale-end-date', 'leadtime-to-ship',
            'launch-date', 'is-giftwrap-available', 'is-gift-message-available',
            'fulfillment-center-id', 'main-offer-image', 'offer-image1',
            'offer-image2', 'offer-image3', 'offer-image4', 'offer-image5'
        ];

        $csv->insertOne($header);
        $csv->insertOne($header);

        foreach ($MWSProduct as $product) {
            $csv->insertOne(
                array_values($product->toArray())
            );
        }

        return $this->SubmitFeed('_POST_FLAT_FILE_LISTINGS_DATA_', $csv);

    }

    /**
     * Returns the feed processing report and the Content-MD5 header.
     * @param string $FeedSubmissionId
     * @return array
     * @throws Exception
     */
    public function GetFeedSubmissionResult($FeedSubmissionId)
    {
        $result = $this->request('GetFeedSubmissionResult', [
            'FeedSubmissionId' => $FeedSubmissionId
        ])->xmlBody;

        if (isset($result['Message']['ProcessingReport'])) {
            return $result['Message']['ProcessingReport'];
        } else {
            return $result;
        }
    }

    /**
     * Uploads a feed for processing by Amazon MWS.
     * @param string $FeedType (http://docs.developer.amazonservices.com/en_US/feeds/Feeds_FeedType.html)
     * @param mixed $feedContent Array will be converted to xml using https://github.com/spatie/array-to-xml. Strings will not be modified.
     * @param boolean $debug Return the generated xml and don't send it to amazon
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function SubmitFeed($FeedType, $feedContent, $debug = false, $options = [])
    {

        if (is_array($feedContent)) {
            $feedContent = $this->arrayToXml(
                array_merge([
                    'Header' => [
                        'DocumentVersion' => 1.01,
                        'MerchantIdentifier' => $this->config['Seller_Id']
                    ]
                ], $feedContent)
            );
        }

        if ($debug === true) {
            return $feedContent;
        } else if ($this->debugNextFeed == true) {
            $this->debugNextFeed = false;
            return $feedContent;
        }

	$purgeAndReplace = isset($options['PurgeAndReplace']) ? $options['PurgeAndReplace'] : false;

        $query = [
            'FeedType' => $FeedType,
            'PurgeAndReplace' => ($purgeAndReplace ? 'true' : 'false'),
            'Merchant' => $this->config['Seller_Id'],
            'MarketplaceId.Id.1' => false,
            'SellerId' => false,
        ];

        //if ($FeedType === '_POST_PRODUCT_PRICING_DATA_') {
        $query['MarketplaceIdList.Id.1'] = $this->config['Marketplace_Id'];
        //}

        $response = $this->request(
            'SubmitFeed',
            $query,
            $feedContent
        )->xmlBody;

        return $response['SubmitFeedResult']['FeedSubmissionInfo'];
    }

    /**
     * Convert an array to xml
     * @param $array array to convert
     * @param $customRoot [$customRoot = 'AmazonEnvelope']
     * @return string
     */
    private function arrayToXml(array $array, $customRoot = 'AmazonEnvelope')
    {
        return ArrayToXml::convert($array, $customRoot);
    }

    /**
     * Convert an xml string to an array
     * @param string $xmlstring
     * @return array
     */
    private function xmlToArray($xmlstring)
    {
        return json_decode(json_encode(simplexml_load_string($xmlstring)), true);
    }

    /**
     * Creates a report request and submits the request to Amazon MWS.
     * @param string $report (http://docs.developer.amazonservices.com/en_US/reports/Reports_ReportType.html)
     * @param DateTime [$StartDate = null]
     * @param DateTime [$EndDate = null]
     * @return string ReportRequestId
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    public function RequestReport(string $report, DateTime $StartDate = null, DateTime $EndDate = null)
    {
        $query = [
            'MarketplaceIdList.Id.1' => $this->config['Marketplace_Id'],
            'ReportType' => $report
        ];

        if (!is_null($StartDate)) {
            if (!is_a($StartDate, 'DateTime')) {
                throw new Exception('StartDate should be a DateTime object');
            } else {
                $query['StartDate'] = gmdate(self::DATE_FORMAT, $StartDate->getTimestamp());
            }
        }

        if (!is_null($EndDate)) {
            if (!is_a($EndDate, 'DateTime')) {
                throw new Exception('EndDate should be a DateTime object');
            } else {
                $query['EndDate'] = gmdate(self::DATE_FORMAT, $EndDate->getTimestamp());
            }
        }

        $result = $this->request(
            'RequestReport',
            $query
        )->xmlBody;

        if (isset($result['RequestReportResult']['ReportRequestInfo']['ReportRequestId'])) {
            return $result['RequestReportResult']['ReportRequestInfo']['ReportRequestId'];
        } else {
            throw new Exception('Error trying to request report');
        }
    }

    /**
     * Get a report's content
     * @param string $ReportId
     * @return GetReportResult data
     * @throws Exception
     */
    public function GetReport($ReportId) : GetReportResult
    {
        $status = $this->GetReportRequestStatus($ReportId);

        if ($status === false) {
            return new GetReportResult(false, $status['ReportProcessingStatus']);
        }

        switch ($status['ReportProcessingStatus']) {
            case '_DONE_':
                $result = $this->request('GetReport', [
                    'ReportId' => $status['GeneratedReportId']
                ]);

                if (is_string($result->body)) {
                    $csv = Reader::createFromString($result->body);
                    $csv->setDelimiter("\t");
                    $headers = $csv->fetchOne();
                    $result = [];
                    foreach ($csv->setOffset(1)->fetchAll() as $row) {
                        $result[] = array_combine($headers, $row);
                    }
                }

                return new GetReportResult(true,  $status['ReportProcessingStatus'], $result);
            default:
                return new GetReportResult(false, $status['ReportProcessingStatus']);
        }
    }

    /**
     * Get a report's processing status
     * @param string $ReportId
     * @return array if the report is found
     * @throws Exception
     */
    public function GetReportRequestStatus($ReportId)
    {
        $result = $this->request('GetReportRequestList', [
            'ReportRequestIdList.Id.1' => $ReportId
        ])->xmlBody;

        if (isset($result['GetReportRequestListResult']['ReportRequestInfo'])) {
            return $result['GetReportRequestListResult']['ReportRequestInfo'];
        }

        return false;

    }

    /**
	 * Get a list's inventory for Amazon's fulfillment
	 *
	 * @param array $sku_array
	 *
	 * @return array
	 * @throws Exception
	 */
    public function ListInventorySupply($sku_array = []){

	    if (count($sku_array) > 50) {
		    throw new Exception('Maximum amount of SKU\'s for this call is 50');
	    }

	    $counter = 1;
	    $query = [
		    'MarketplaceId' => $this->config['Marketplace_Id']
	    ];

	    foreach($sku_array as $key){
		    $query['SellerSkus.member.' . $counter] = $key;
		    $counter++;
	    }

	    $response = $this->request(
		    'ListInventorySupply',
		    $query
	    )->xmlBody;

	    $result = [];
	    if (isset($response['ListInventorySupplyResult']['InventorySupplyList']['member'])) {
		    foreach ($response['ListInventorySupplyResult']['InventorySupplyList']['member'] as $index => $ListInventorySupplyResult) {
			    $result[$index] = $ListInventorySupplyResult;
		    }
	    }

	    return $result;
    }

    /**
     * Get a list's inventory for Amazon's fulfillment
     *
     * @param array $sku_array
     *
     * @return array
     * @throws Exception
     */
    public function GetInboundGuidanceForSKU($sku_array = []){

        if (count($sku_array) > 50) {
            throw new Exception('Maximum amount of SKU\'s for this call is 50');
        }

        $counter = 1;
        $query = [
            'MarketplaceId' => $this->config['Marketplace_Id']
        ];

        foreach($sku_array as $key){
            $query['SellerSKUList.member.' . $counter] = $key;
            $counter++;
        }
        return $this->request(
            'GetInboundGuidanceForSKU',
            $query
        )->body;
    }


    /**
     * Get a list's inventory for Amazon's fulfillment
     *
     * @param array $query
     *
     * @return array
     * @throws Exception
     */
    public function ListInboundShipments($query){
        return $this->request(
            'ListInboundShipments',
            $query
        )->body;
    }

    /**
     * Get a list's inventory for Amazon's fulfillment
     *
     * @param array $query
     *
     * @return array
     * @throws Exception
     */
    public function ListInboundShipmentItems($query){
        return $this->request(
            'ListInboundShipmentItems',
            $query
        )->body;
    }

    /**
     * Request MWS
     * @param $endPoint
     * @param array $query
     * @param null $body
     * @param bool $raw
     * @return MWSResult
     * @throws Exception
     */
    private function request($endPoint, array $query = [], $body = null, $raw = false): MWSResult
    {

        $endPoint = MWSEndPoint::get($endPoint);

        $merge = [
            'Timestamp' => gmdate(self::DATE_FORMAT, time()),
            'AWSAccessKeyId' => $this->config['Access_Key_ID'],
            'Action' => $endPoint['action'],
            //'MarketplaceId.Id.1' => $this->config['Marketplace_Id'],
            'SellerId' => $this->config['Seller_Id'],
            'SignatureMethod' => self::SIGNATURE_METHOD,
            'SignatureVersion' => self::SIGNATURE_VERSION,
            'Version' => $endPoint['date'],
        ];

        $query = array_merge($merge, $query);

        if (!isset($query['MarketplaceId.Id.1'])) {
            $query['MarketplaceId.Id.1'] = $this->config['Marketplace_Id'];
        }

        if (!is_null($this->config['MWSAuthToken']) and $this->config['MWSAuthToken'] != "") {
            $query['MWSAuthToken'] = $this->config['MWSAuthToken'];
        }

        if (isset($query['MarketplaceId'])) {
            unset($query['MarketplaceId.Id.1']);
        }

        if (isset($query['MarketplaceIdList.Id.1'])) {
            unset($query['MarketplaceId.Id.1']);
        }

        try{

            $headers = [
                'Accept' => 'application/xml',
                'x-amazon-user-agent' => $this->config['Application_Name'] . '/' . $this->config['Application_Version']
            ];

            if ($endPoint['action'] === 'SubmitFeed') {
                $headers['Content-MD5'] = base64_encode(md5($body, true));
                $headers['Content-Type'] = 'text/xml; charset=iso-8859-1';
                $headers['Host'] = $this->config['Region_Host'];

                unset(
                    $query['MarketplaceId.Id.1'],
                    $query['SellerId']
                );
            }

            $requestOptions = [
                'headers' => $headers,
                'body' => $body
            ];

            ksort($query);

            $query['Signature'] = base64_encode(
                hash_hmac(
                    'sha256',
                    $endPoint['method']
                    . "\n"
                    . $this->config['Region_Host']
                    . "\n"
                    . $endPoint['path']
                    . "\n"
                    . http_build_query($query, null, '&', PHP_QUERY_RFC3986),
                    $this->config['Secret_Access_Key'],
                    true
                )
            );

            $requestOptions['query'] = $query;

            if($this->client === NULL) {
                $this->client = new Client();
            }

            $response = $this->client->request(
                $endPoint['method'],
                $this->config['Region_Url'] . $endPoint['path'],
                $requestOptions
            );

            return new MWSResult($response);

        } catch (BadResponseException $e) {
            $error = new MWSErrorResult($e);
            switch ($e->getCode()) {
                case 400:
                    throw new InputStreamDisconnectedException($error->message);
                    // throw new InvalidParameterValue();
                case 401:
                    throw new AccessDeniedException($error->message);
                case 403:
                    throw new InvalidAccessKeyIdException($error->message);
                    // throw new SignatureDoesNotMatchException($message);
                case 404:
                    throw new InvalidAddressException($error->message);
                case 500:
                    throw new InternalErrorException($error->message);
                case 503:
                    if ($error->code === 'RequestThrottled') {
                        throw new RequestThrottledException($error->message);
                    } else {
                        throw new QuotaExceededException($error->message);
                    }
                default:
                    throw new MWSException($error->message);
            }
        }
    }

    public function setClient(Client $client) {
        $this->client = $client;
    }
}
