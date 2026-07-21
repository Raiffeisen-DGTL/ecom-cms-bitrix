<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Sale\PaySystem\ServiceResult;
use Bitrix\Sale;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\PaySystem;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\Payment;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\PaySystem\Manager;
use Bitrix\Sale\PaySystem\Service;

use Raiffeisen\Ecom\ClientException;
use Exception;


include_once(dirname(__FILE__) . "/classes/Client.php");
include_once(dirname(__FILE__) . "/classes/ClientException.php");

Loc::loadMessages(__FILE__);

class ruraiffeisen_raiffeisenpayHandler extends PaySystem\ServiceHandler implements PaySystem\IRefund
{
    private $vat;
    private $debug;
    private $secretKey;
    private $publicKey;

    public function OnBusinessValueSetMapping()
    {
        
    }

	/**
	 * @param Request $request
	 * @param $paySystemId
	 * @return bool
	 */
	protected static function isMyResponseExtended(Request $request, $paySystemId)
	{
        $orderId = $request->get("transaction")['extra']['orderAccountNumber'];
        $order = Sale\Order::loadByAccountNumber($orderId);
		return $order->getField('PAY_SYSTEM_ID') == $paySystemId;
	}

    /**
     * Process request after payment.
     *
     * @param  Payment  $payment
     * @param  Request  $request
     *
     * @return ServiceResult
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\ObjectException
     * @throws \ErrorException
     */
    public function processRequest(\Bitrix\Sale\Payment $payment, Request $request)
    {
        $result = new ServiceResult();
        // $action = $request->get('qiwi');
        // $this->log('NOTIFY_PROCESS_REQUEST', ['method' => 'processRequest', 'data' => $result->getData()]);
        // switch ($action) {
        //     case 'success':
        //         $result = $this->processSuccessAction($payment, $request);
        //         break;
        //     case 'notify':
        //         $result = $this->processNotifyAction($payment);
        //         break;
        // }

        return $result;
    }

    /**
     * Refundable.
     *
     * @return bool
     */
    public function isRefundableExtended()
    {
        return true;
    }


    /**
     * Identifies paysystem by GET parameter.
     *
     * @return array
     */
    public static function getIndicativeFields()
    {
        return ['transaction'];
    }
    /**
     * @param Sale\Payment $payment
     * @param Request|null $request
     * @return PaySystem\ServiceResult
     */
    public function initiatePay(Sale\Payment $payment, Request $request = null)
    {
        $accountNumber = $payment->getOrder()->getFieldValues()['ACCOUNT_NUMBER'];
        $paySystem = Manager::getObjectById($payment->getPaymentSystemId());
        $consumerName = $paySystem->getConsumerName();
        $this->setExtraParams([
            'ACCOUNT_NUMBER' => $accountNumber,
            'PAYMENT_ID' => $payment->getId(),
            'DEBUG' => $this->debug,
        ]);

        try {
            $sellerSecret   = BusinessValue::getMapping('SELLER_SECRET',    $consumerName)['PROVIDER_VALUE'];
            $sellerPublicId = BusinessValue::getMapping('SELLER_PUBLIC_ID', $consumerName)['PROVIDER_VALUE'];
            $testMode       = BusinessValue::getMapping('TEST_MODE',        $consumerName)['PROVIDER_VALUE'];
            $sellerCallback = BusinessValue::getMapping('SELLER_CALLBACK',  $consumerName)['PROVIDER_VALUE'];
            if($sellerSecret && $sellerPublicId && $sellerCallback) {
                $host = $testMode === 'yes' ? \Raiffeisen\Ecom\Client::HOST_TEST : \Raiffeisen\Ecom\Client::HOST_PROD;
                $client = new \Raiffeisen\Ecom\Client($sellerSecret, $sellerPublicId, $host);
                $result = $client->postCallbackUrl($sellerCallback);
            }
        }
        catch (Exception $e) {
            $this->log('EXCEPTION', ['exception' => $e]);
        }

        return $this->showTemplate($payment, "template");
    }

    public function getPaymentIdFromRequest(Request $request)
    {
        $pid = $request->get('id');
        if ($pid) {
            return $pid;
        }
        $body = file_get_contents('php://input');
        if ($body) {
            $reqData = Json::decode($body);
        }
        // Log only non-personal notification fields. Never write raw callback JSON.
        $this->log('NOTIFY', [
            'pid' => $pid,
            'transaction_id' => isset($reqData['transaction']['id']) ? $reqData['transaction']['id'] : null,
            'transaction_status' => isset($reqData['transaction']['status']['value']) ? $reqData['transaction']['status']['value'] : null,
        ]);
        if (isset($reqData) && isset($reqData['transaction']['id'])) {
            $pid = $reqData['transaction']['id'];
            if (!$pid) {
                http_response_code(404);
                die();
            }

            return $pid;
        }

        http_response_code(404);
        die();
    }

    /**
     * Check order status.
     *
     * @param Payment $payment
     * @return ServiceResult
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\ObjectException
     * @throws \ErrorException
     */
    public function check(Payment $payment)
    {
        $result = new ServiceResult();
        $this->initialise($payment);
        $billInfo = true;

        if ($result->isSuccess()) {
            if ($billInfo) {
                switch ($billInfo['status']['value']) {
                    case 'PAID':
                        $result->setOperationType(ServiceResult::MONEY_COMING);
                        break;
                    case 'WAITING':
                    case 'REJECTED':
                    case 'EXPIRED':
                        $result->setOperationType(ServiceResult::MONEY_LEAVING);
                        break;
                }
                //$psData['PS_STATUS_CODE'] = $billInfo['status']['value'];
            }
        }

        if (isset($psData)) {
            $result->setPsData($psData);
        }
        if (isset($data)) {
            $result->setData($data);
        }

        return $result;
    }

    /**
     * Process request after payment.
     *
     * @param  Payment  $payment
     * @param  Request  $request
     *
     * @return ServiceResult
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\ObjectException
     * @throws \ErrorException
     */
    public function processRequestCustom(\Bitrix\Sale\PaySystem\Service $payment, Request $request)
    {
        $result = new ServiceResult();
        $action = $request->get('transaction')['status']['value'];

        switch ($action) {
            case 'SUCCESS':
                $result = $this->processSuccessAction($payment, $request);
                break;
            case 'notify':
                $result = $this->processNotifyAction($payment);
                break;
        }

        return $result;
    }

    /**
     * @param  \Bitrix\Sale\PaySystem\Service  $payment
     * @param  Request  $request
     *
     * @return ServiceResult
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\ObjectException
     * @throws \ErrorException
     */
    public function processSuccessAction(\Bitrix\Sale\PaySystem\Service $payment, Request $request)
    {
        $result = new ServiceResult();
        //$this->initialise($payment);


        $billInfo = true; //$this->checkBill($payment->getField('PS_INVOICE_ID'), $result);
        if ( /*$result->isSuccess()*/true) {
            //if ($billInfo) {
            switch ($request->get("transaction")['status']['value']) {
                case 'SUCCESS':
                    $email = $request->get("transaction")['extra']['email'];
                    $orderId = $request->get("transaction")['extra']['orderAccountNumber'];
                    $this->log('CALLBACK_SUCCESS', [
                        'transaction_status' => $request->get("transaction")['status']['value'],
                    ]);
                    $order = Sale\Order::loadByAccountNumber($orderId);
                    $paymentCollection = $order->getPaymentCollection();
                    foreach ($paymentCollection as $_payment_) {
                        $sum  = $_payment_->getSum(); // сумма к оплате
                        $paymentAmount = (int)round((float)$sum * 100);
                        $requestAmount = (int)round((float)$request->get("transaction")['amount'] * 100);

                        $psID = $_payment_->getPaymentSystemId();

                        if ($psID == $payment->getField('PAY_SYSTEM_ID') && $paymentAmount === $requestAmount) {
                            try {
                                $_payment_->setPaid("Y");
                                //$setField = $_payment_->setField('PS_INVOICE_ID', $request->get("transaction")['id']);
                                //                    if ($setField->isSuccess()) {
                                //                        $payment->save();
                                //                    }
                                $order->setField('STATUS_ID', 'P');
                                $order_save_result = $order->save();

                                $consumerName = $payment->getConsumerName();

                                $sellerSecret   = BusinessValue::getMapping('SELLER_SECRET',        $consumerName)['PROVIDER_VALUE'];
                                $sellerPublicId = BusinessValue::getMapping('SELLER_PUBLIC_ID',     $consumerName)['PROVIDER_VALUE'];
                                $testMode       = BusinessValue::getMapping('TEST_MODE',            $consumerName)['PROVIDER_VALUE'];
                                $sellerVat         = BusinessValue::getMapping('SELLER_VAT',           $consumerName)['PROVIDER_VALUE'];
                                $fiscalization     = BusinessValue::getMapping('SELLER_FISCALIZATION', $consumerName)['PROVIDER_VALUE'];
                                $sellerPaymentMode = BusinessValue::getMapping('SELLER_PAYMENT_MODE', $consumerName)['PROVIDER_VALUE'] ?: 'FULL_PAYMENT';

                                $this->log('PAYMENT_MATCHED', [
                                    'payment_system_id' => $psID,
                                    'fiscalization_enabled' => $fiscalization === 'on' ? 'Y' : 'N',
                                ]);

                                if ($fiscalization === 'on') {
                    
                                    $host = $testMode === 'yes' ? \Raiffeisen\Ecom\Client::HOST_TEST : \Raiffeisen\Ecom\Client::HOST_PROD;

                                    $client = new \Raiffeisen\Ecom\Client($sellerSecret, $sellerPublicId, $host);


                                    /// Items
                                    $basket = $order->getBasket();

                                    $basketItems = $basket->getBasketItems();
                                    $bItems      = [];
                                
                                    $vatType = $sellerVat === "NONE" ? "NONE" : ("VAT" . $sellerVat);
                                
                                    foreach ($basketItems as $item) {
                                        $bItems[] = [
                                            "name"            => $item->getField('NAME'),
                                            "price"           => number_format($item->getField('PRICE'), 2, '.', ''),
                                            "quantity"        => (int) $item->getField('QUANTITY'),
                                            "amount"          => number_format($item->getFinalPrice(), 2, '.', ''),
                                            "paymentObject"   => "COMMODITY",
                                            "paymentMode"     => $sellerPaymentMode,
                                            "measurementUnit" => "OTHER",
                                            //"nomenclatureCode" => $item->getField('PRODUCT_XML_ID'),
                                            "vatType"  => $vatType,
                                        ];
                                    }
                                
                                    if ($order->getDeliveryPrice() > 0) {
                                        $bItems[] = [
                                            "name"     => Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_DELIVERY'),
                                            "price"    => number_format($order->getDeliveryPrice(), 2, '.', ''),
                                            "quantity" => 1,
                                            "amount"   => number_format($order->getDeliveryPrice(), 2, '.', ''),
                                            "vatType"  => $vatType,
                                        ];
                                    }
                                    /// /Items

                                    $receiptNumber     = $orderId . '-' . $_payment_->getField('ID');
                                    $raiffeisenOrderId = $request->get("transaction")['orderId'];

                                    $postReceiptResult = $client->postReceiptSell($receiptNumber, $email, $bItems, number_format($order->getPrice(), 2, '.', ''), null, $raiffeisenOrderId);
                                    $this->log('POST_RECEIPT_RESULT', $this->getSafeReceiptResultLog($postReceiptResult));
                                    $registerReceiptResult = $client->registerReceiptSell($receiptNumber);
                                    $this->log('REGISTER_RECEIPT_RESULT', $this->getSafeReceiptResultLog($registerReceiptResult));
                                    
                                    $_payment_->setField('PAY_VOUCHER_NUM', $receiptNumber);
                                    $_payment_->setField('PAY_VOUCHER_DATE', Date::createFromTimestamp(time()));
                                    $order_save_result = $order->save();
                                }
                            }
                            catch (\Exception $e) {
                                $this->log('CALLBACK_EXCEPTION', [
                                    'exception' => get_class($e),
                                    'code' => $e->getCode(),
                                ]);
                            }
                        }
                    }
                    break;
                case 'WAITING':
                case 'REJECTED':
                case 'EXPIRED':
                    $result->setOperationType(ServiceResult::MONEY_LEAVING);
                    break;
            }
            //$psData['PS_STATUS_CODE'] = $billInfo['status']['value'];
            $backUrl = $request->get('back');
            if (is_string($backUrl) && self::isSafeBackUrl($backUrl)) {
                $data['BACK_URL'] = $backUrl;
            } elseif ($backUrl) {
                $this->log('INVALID_BACK_URL', [
                    'reason' => 'Unsafe callback back URL',
                ]);
            }
            //}
        }
        if (isset($psData)) {
            $result->setPsData($psData);
        }
        if (isset($data)) {
            $result->setData($data);
        }

        return $result;
    }

    /**
     * @param  Payment  $payment
     *
     * @return ServiceResult
     * @throws \ErrorException
     */
    public function processNotifyAction(Bitrix\Sale\PaySystem\Service $payment)
    {
        /*$this->initialise($payment);
        $body = file_get_contents('php://input');
        if ($body) {
        $billData = json_decode($body, true);
        }
        $result = new ServiceResult();
        if (! isset($billData) || ! $this->checkNotifySignature($billData)) {
        $result->setData(['NOTIFY' => ['CODE' => 403]]);
        return $result;
        }
        $this->log('NOTIFY_STEP_2', ['method' => 'processNotifyAction', 'data' => $billData]);
        $billData = $billData['bill'];
        switch ($billData['status']['value']) {
        case 'PAID':
        $result->setOperationType(ServiceResult::MONEY_COMING);
        break;
        case 'WAITING':
        case 'REJECTED':
        case 'EXPIRED':
        case 'PARTIAL':
        case 'FULL':
        $result->setOperationType(ServiceResult::MONEY_LEAVING);
        $psData['PAID'] = 'N';
        break;
        }
        $psData['PS_STATUS_CODE'] = $billData['status']['value'];
        $result->setPsData($psData);
        $result->setData(['NOTIFY' => ['CODE' => 200]]);
        $this->log('NOTIFY_STEP_3', ['method' => 'processNotifyAction', 'data' => $result->isSuccess()]);
        return $result;*/
    }

    /**
     * Sets header and prints json encoded data, then dies.
     *
     * @param  array  $data
     * @param  int  $code
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    public function sendJsonResponse($data = [], $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        header('Pragma: no-cache');
        die(Json::encode($data));
    }

    /**
     * Init api.
     *
     * @param  Payment  $payment
     *
     * @throws \ErrorException
     */
    protected function initialise($orderPpayment)
    {
        $sellerVat = $this->getBusinessValue($orderPpayment, 'SELLER_VAT');

        $this->debug     = $this->getBusinessValue($orderPpayment, 'TEST_MODE');
        $this->secretKey = $this->getBusinessValue($orderPpayment, 'SELLER_SECRET');
        $this->publicKey = $this->getBusinessValue($orderPpayment, 'SELLER_PUBLIC_ID');
        $this->vat       = $sellerVat === "NONE" ? "NONE" : ("VAT" . $sellerVat);
    }

    /**
     * Final function that sends response or redirects user to payment page.
     *
     * @param  ServiceResult  $result
     * @param  Request  $request
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    public function sendResponse(ServiceResult $result, Request $request)
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        $data = $result->getData();
        if ($data['NOTIFY']['CODE']) {
            switch ($data['NOTIFY']['CODE']) {
                case 403:
                    $this->sendJsonResponse(['error' => 403], 403);
                    break;
                default:
                    $this->sendJsonResponse(['error' => 0]);
                    break;
            }
        } elseif (isset($data['BACK_URL']) && self::isSafeBackUrl($data['BACK_URL'])) {
            LocalRedirect($data['BACK_URL']);
        } else {
            echo 'SUCCESS';
        }

        return;
    }

    public static function isSafeBackUrl($url)
    {
        return is_string($url)
            && $url !== ''
            && strpos($url, '/') === 0
            && strpos($url, '//') === false
            && strpos($url, '\\') === false
            && preg_match('/[\x00-\x1F\x7F]/', $url) === 0;
    }

    /**
     * @return array
     */
    public function getCurrencyList()
    {
        return ['RUB'];
    }

    /**
     * Log event.
     *
     * @param  string  $type
     * @param  array  $desc
     */
    protected function log($type, array $desc)
    {
        if ($this->debug) {
            $description = json_encode($desc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($description === false) {
                $description = 'Unable to encode log description';
            }

            \CEventLog::Add([
                'SEVERITY'      => 'DEBUG',
                'AUDIT_TYPE_ID' => 'PAYMENT_RAIF_' . $type,
                'MODULE_ID'     => 'ruraiffeisen_raiffeisenpay',
                'ITEM_ID'       => 1,
                'DESCRIPTION'   => $description,
            ]);
        }
    }

    protected function getSafeReceiptResultLog($receiptResult)
    {
        if (!is_array($receiptResult)) {
            return [
                'result_type' => is_object($receiptResult) ? get_class($receiptResult) : gettype($receiptResult),
            ];
        }

        $safeResult = [];
        foreach (['status', 'code', 'errorCode', 'message', 'errorMessage'] as $key) {
            if (isset($receiptResult[$key]) && !is_array($receiptResult[$key]) && !is_object($receiptResult[$key])) {
                $safeResult[$key] = $receiptResult[$key];
            }
        }

        return $safeResult ?: ['result_keys' => implode(',', array_keys($receiptResult))];
    }

    /**
     * Sends request on rfzn server for refund payment.
     *
     * @param  Payment  $payment
     * @param  int  $refundableSum
     *
     * @return ServiceResult
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \ErrorException
     * @throws \Exception
     */
    public function refund(Payment $payment, $refundableSum)
    {
        $this->initialise($payment);

        $result = new ServiceResult();

        $body   = [];
        $items  = [];
        $order  = $payment->getOrder();
        $basket = $order->getBasket();
        foreach ($basket as $basketItem) {
            $items[] = [
                'name'        => $basketItem->getField('NAME'),
                'price'       => number_format($basketItem->getPrice(), 2, '.', ''),
                'quantity'    => $basketItem->getQuantity(),
                'amount'      => number_format($basketItem->getFinalPrice(), 2, '.', ''),
                "paymentMode" => "FULL_PREPAYMENT",
                "vatType"     => $this->vat,
            ];
        }

        if ($order->getDeliveryPrice() > 0) {
            $items[] = [
                "name"     => Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_DELIVERY'),
                "price"    => number_format($order->getDeliveryPrice(), 2, '.', ''),
                "quantity" => 1,
                "amount"   => number_format($order->getDeliveryPrice(), 2, '.', ''),
                "vatType"  => $this->vat
            ];
        }

        $rsUser = \CUser::GetByID($order->getUserId())->GetNext();

        Loader::includeModule("ruraiffeisen_raiffeisenpay");

        $orderId  = $order->getField('ID');
        $refundId = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffffffff),
            random_int(0, 0xffffffff),
            random_int(0, 0xffffffff)
        );
        $amount   = $refundableSum;
        $host     = $this->debug === 'yes' ? \Raiffeisen\Ecom\Client::HOST_TEST : \Raiffeisen\Ecom\Client::HOST_PROD;
        $client   = new \Raiffeisen\Ecom\Client($this->secretKey, $this->publicKey, $host);

        $response = $client->postOrderRefund($orderId, $refundId, number_format($amount, 2, '.', ''), array("customer" => ["email" => $rsUser['EMAIL'],], "items" => $items));
        if ($response['refundStatus'] == "COMPLETED") {
            $result->setOperationType(ServiceResult::MONEY_LEAVING);
            $payment->setPaid("N");
            $saved = $payment->save()->isSuccess();
        }

        return $result;
    }
}
