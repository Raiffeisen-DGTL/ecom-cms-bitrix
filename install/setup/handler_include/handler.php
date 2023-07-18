<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Request;
use Bitrix\Sale\PaySystem\ServiceResult;
use Bitrix\Sale;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\PaySystem;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Diag;
use Bitrix\Sale\Payment;
use Bitrix\Main\Loader;

class raiffeizenpayHandler extends PaySystem\ServiceHandler implements PaySystem\IRefund
{
    private $vat;
    private $debug;
    private $secretKey;
    private $publicKey;

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
        $action = $request->get('qiwi');
        $this->log('NOTIFY_PROCESS_REQUEST', ['method' => 'processRequest', 'data' => $result->getData()]);
        switch ($action) {
            case 'success':
                $result = $this->processSuccessAction($payment, $request);
                break;
            case 'notify':
                $result = $this->processNotifyAction($payment);
                break;
        }

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
        // Log notifies from ...
        $this->log('NOTIFY', ['pid' => $pid, 'reqData' => $body]);
        if (isset($reqData) && isset($reqData['transaction']['i	d'])) {
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
        Diag\Debug::dumpToFile($payment, "PS", '/upload/logs.log');
        Diag\Debug::dumpToFile($request->get('transaction'), "GET_0", '/upload/logs.log');

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
    public function processSuccessAction(\Bitrix\Sale\PaySystem\Service $payment, Request $request)
    {
        $result = new ServiceResult();
        //$this->initialise($payment);
        Diag\Debug::dumpToFile($request->get("transaction"), "GET", '/upload/logs.log');


        $billInfo = true; //$this->checkBill($payment->getField('PS_INVOICE_ID'), $result);
        if ( /*$result->isSuccess()*/true) {
            //if ($billInfo) {

            switch ($request->get("transaction")['status']['value']) {
                case 'SUCCESS':
                    $order = Sale\Order::load($request->get("transaction")['orderId']);
                    $paymentCollection = $order->getPaymentCollection();
                    foreach ($paymentCollection as $_payment_) {
                        $sum  = $_payment_->getSum(); // сумма к оплате

                        $psID = $_payment_->getPaymentSystemId();

                        if ($psID == $payment->getField('PAY_SYSTEM_ID') && $sum == $request->get("transaction")['amount']) {
                            $_payment_->setPaid("Y");
                            //$setField = $_payment_->setField('PS_INVOICE_ID', $request->get("transaction")['id']);
                            //                    if ($setField->isSuccess()) {
                            //                        $payment->save();
                            //                    }
                            $order->setField('STATUS_ID', 'P');
                            $order->save();
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
            if ($request->get('back')) {
                $data['BACK_URL'] = urldecode($request->get('back'));
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
        } elseif ($data['BACK_URL']) {
            LocalRedirect($data['BACK_URL']);
        } else {
            echo 'SUCCESS';
        }

        return;
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
            CEventLog::Add([
                'SEVERITY'      => 'DEBUG',
                'AUDIT_TYPE_ID' => 'PAYMENT_RAIF_' . $type,
                'MODULE_ID'     => 'raiffeizenpay',
                'ITEM_ID'       => 1,
                'DESCRIPTION'   => $desc,
            ]);
        }
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

        file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/log_paid.php", "\n" . Date("H:i:s: ") . print_r("refund", 1), FILE_APPEND);

        $result = new ServiceResult();

        $body   = [];
        $items  = [];
        $order  = $payment->getOrder();
        $basket = $order->getBasket();
        foreach ($basket as $basketItem) {
            $items[] = [
                'name'        => $basketItem->getField('NAME'),
                'price'       => $basketItem->getPrice(),
                'quantity'    => $basketItem->getQuantity(),
                'amount'      => $basketItem->getFinalPrice(),
                "paymentMode" => "FULL_PREPAYMENT",
                "vatType"     => $this->vat,
            ];
        }

        if ($order->getDeliveryPrice() > 0) {
            $items[] = [
                "name"     => 'Доставка',
                "price"    => $order->getDeliveryPrice(),
                "quantity" => 1,
                "amount"   => $order->getDeliveryPrice(),
                "vatType"  => $this->vat
            ];
        }

        $rsUser = \CUser::GetByID($order->getUserId())->GetNext();

        Loader::includeModule("raiffeizenpay");

        $orderId  = $order->getField('ID');
        $refundId = sha1(time() * rand(1, 99));
        $amount   = $refundableSum;
        $client   = new \Raiffeisen\Ecom\Client($this->secretKey, $this->publicKey, \Raiffeisen\Ecom\Client::HOST_TEST);

        $response = $client->postOrderRefund($orderId, $refundId, $amount, array("customer" => ["email" => $rsUser['EMAIL'],], "items" => $items));
        if ($response['refundStatus'] == "COMPLETED") {
            $result->setOperationType(ServiceResult::MONEY_LEAVING);
            $payment->setPaid("N");
            $saved = $payment->save()->isSuccess();
        }

        file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/log_paid.php", "\n" . Date("H:i:s: ") . print_r($response, 1), FILE_APPEND);

        file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/log_paid.php", "\n" . Date("H:i:s: ") . print_r("refund end", 1), FILE_APPEND);

        return $result;
    }
}
