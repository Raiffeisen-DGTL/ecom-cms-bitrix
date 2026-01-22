<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
die(); 

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PriceMaths;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag;
use Bitrix\Main\Page\Asset;
use Bitrix\Sale\PaySystem\Manager;
use Bitrix\Sale\PaySystem\Service;

/** @var \Bitrix\Sale\Payment $payment */
/** @var array $params */

Asset::getInstance()->addCss($params['SELLER_STYLES']);

Loc::loadMessages(__FILE__);

$_SERVER["SERVER_NAME"] = 'https://' . SITE_SERVER_NAME;

if (array_key_exists('PAYMENT_SHOULD_PAY', $params)) {
    $params['PAYMENT_SHOULD_PAY'] = PriceMaths::roundPrecision($params['PAYMENT_SHOULD_PAY']);
}

// $formStyles=file_get_contents($_SERVER['DOCUMENT_ROOT'].$params["SELLER_STYLES"]);

$formStyles = Option::get('ruraiffeisen_raiffeisenpay', 'FORM_STYLE');

/*
$formStyles=explode("style: ",$formStyles);
$formStyles=$formStyles[1];
$formStyles=explode("successSbpUrl",$formStyles);
$formStyles=$formStyles[0];
*/


try {
/** @var Sale\Order $order */
    $order = Sale\Order::loadByAccountNumber($params["ACCOUNT_NUMBER"]);
}
catch (ArgumentNullException $e) {
    $this->log('EXCEPTION', ['request_uri' => $_SERVER["REQUEST_URI"], 'request_get' => $_GET, 'exception' => $e]);
    throw $e;
}
$userID    = $order->getUserId();
$userName  = \Bitrix\Main\Engine\CurrentUser::get()->getFullName();
$userEmail = \Bitrix\Main\Engine\CurrentUser::get()->getEmail();
$orderEmail = $order->getPropertyCollection()->getUserEmail()->getValue();
$email = $orderEmail;
$orderID   = $params["ACCOUNT_NUMBER"] . '-' . $params["PAYMENT_ID"];
$accountNumber = $params["ACCOUNT_NUMBER"];
$debug = $params["DEBUG"];

$paySum = 0;
foreach($order->getPaymentCollection()->getIterator() as $payment) {
    /** @var \Bitrix\Sale\Payment $payment */
    if($payment->getId() == $params["PAYMENT_ID"]) {
        $paySum = $payment->getSum() - $payment->getSumPaid();
        break;
    }
    $paySum = $order->getPrice();
}

$receipt = [];

$receipt["receiptNumber"]     = $orderID;
$receipt["customer"]["email"] = $email;
//$receipt["customer"]["name"] = $userName;

// Diag\Debug::dumpToFile($_SERVER["HTTP_USER_AGENT"],  "HTTP_USER_AGENT",  '/raiffeisenpay_logs.log');
Diag\Debug::dumpToFile($orderID,  "orderID",  '/raiffeisenpay_logs.log');
// Diag\Debug::dumpToFile($order,  "Order",  '/raiffeisenpay_logs.log');
// Diag\Debug::dumpToFile($params['SELLER_FISCALIZATION'],  "SELLER_FISCALIZATION",  '/raiffeisenpay_logs.log');
if ($params['SELLER_FISCALIZATION'] === 'on') {
    $basket = $order->getBasket();

    $basketItems = $basket->getBasketItems();
    $bItems      = [];

    $vatType = $params["SELLER_VAT"] === "NONE" ? "NONE" : ("VAT" . $params["SELLER_VAT"]);

    foreach ($basketItems as $item) {
        $bItems[] = [
            "name"            => $item->getField('NAME'),
            "price"           => number_format($item->getField('PRICE'), 2, '.', ''),
            "quantity"        => (int) $item->getField('QUANTITY'),
            "amount"          => number_format($item->getFinalPrice(), 2, '.', ''),
            "paymentObject"   => "COMMODITY",
            "paymentMode"     => "FULL_PAYMENT",
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

    $receipt["items"] = $bItems;

    $receipt["total"] = $order->getPrice();
}

/*
$receipt["payments"][] = [
"type" => "E_PAYMENT",
"amount" => $paySum,
];
*/

// Diag\Debug::dumpToFile($receipt,  "receipt",  '/raiffeisenpay_logs.log');

if (array_key_exists('ORDER_LIFETIME', $params)) {
    $expPeriod = intval($params["ORDER_LIFETIME"]);
}
else {
    $expPeriod = 15;
}

if ($expPeriod > 0) {
    $expTime = (new DateTime())->add(DateInterval::createFromDateString("{$expPeriod} minutes"));
}
else {
    $expTime = null;
}

?>

<style type="text/css">
    H1 {
        font-size: 12pt;
    }

    p,
    ul,
    ol,
    h1 {
        margin-top: 6px;
        margin-bottom: 6px
    }

    td {
        font-size: 9pt;
    }

    small {
        font-size: 7pt;
    }

    body {
        font-size: 10pt;
    }

    .button-pay {
        color: #333;
        border: none;
        background-color: rgb(254, 230, 0);
        border-radius: 8px;
        padding: 6px 50px;
        font-size: 14px;
        margin: 20px 0;
        font-weight: bold;
    }
</style>
<script src="https://pay.raif.ru/pay/sdk/v2/payment.styled.min.js"></script>

<form method="POST" name="redirectToAcsForm" id="form" target="_blank" style="display: none">
    <input name="amount" id="amount" value="<?= $paySum ?>" type="hidden" />
    <input name="orderId" id="orderId" value="<?= $orderID ?>" id="order" type="hidden" />
    <input name="successUrl" id="successUrl" value="<?= ($_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["HTTP_HOST"] . $params['BACK_URI_SUCCESS']) ?>"
        type="hidden" />
    <input name="failUrl" id="failUrl" value="<?= ($_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["HTTP_HOST"] . $params['BACK_URI_FAIL']) ?>"
        type="hidden" />
    <input name="publicId" id="publicId" value="<?= $params["SELLER_PUBLIC_ID"] ?>" type="hidden" />
    <input name="paymentMethod" id="paymentMethod" value="<?= $params["SELLER_METHOD"] ?>" type="hidden" />
    <? if ($formStyles): ?>
        <script>
            const styleForm = <?= $formStyles ?>; 
        </script>
    <? else: ?>
        <script>
            const styleForm = null; 
        </script>
    <? endif ?>
    <input name="expirationDate" id="expirationDate"
        value="<?= $expTime ? $expTime->format('Y-m-d') . "T" . $expTime->format('H:i:sP') : '' ?>" style="width: 200px;" />
    <input name="orderAccountNumber" id="orderAccountNumber" value="<?= $accountNumber ?>" style="width: 200px;" />
    <input id="paymentDetails" name="paymentDetails" value="<?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_ORDER_PAYMENT') ?> <?= $orderID ?>" type="hidden" />
    <textarea type="text" id="receipt" name="receipt" rows="20"
        style="width: 300px"><?= \Bitrix\Main\Web\Json::encode($receipt) ?></textarea>
</form>

<button class="button-pay" id="openPopup"><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_OPEN_PAYMENT_POPUP') ?></button>

<div id="portal"></div>

<script>
    const loadInterval = setInterval(() => {
        if(PaymentPageSdk) {
            clearInterval(loadInterval);
            const processPayment = () => {
                const paymentData = getPaymentData();

                const paymentPage = new PaymentPageSdk(paymentData.publicId, {
                    targetElem: null, url: "<?= $params['TEST_MODE'] === 'yes' ? 'https://pay-test.raif.ru/pay' : 'https://pay.raif.ru/pay' ?>"
                });

                <? if ($params['OPEN_ON_NEW_PAGE'] == 'no'): ?>
                    console.log(paymentData);
                    paymentPage.openPopup({
                        amount: paymentData.amount,
                        publicId: paymentData.publicId,
                        orderId: paymentData.orderId,
                        comment: paymentData.comment,
                        ...(paymentData.style ? {style: paymentData.style} : {}),
                        <? if ($params['SELLER_METHOD'] !== 'both'): ?>
                            paymentMethod: "<?= $params['SELLER_METHOD'] == 'sbp' ? 'ONLY_SBP' : 'ONLY_ACQUIRING' ?>",
                        <? endif; ?>
                        <? if ($params['SELLER_FISCALIZATION'] === 'on' && false): ?>
                            receipt: paymentData.receipt,
                        <? endif; ?>
                        ...(paymentData.expirationDate ? { expirationDate: paymentData.expirationDate } : {}),
                        extra: {
                            email: paymentData.receipt.customer.email,
                            orderAccountNumber: paymentData.orderAccountNumber,
                            apiClient: "Raifpay Bitrix",
                        },
                    })
                        .then(function (result) {
                            console.log('payment result', result);
                            window.location = paymentData.successUrl;
                        })
                        .catch(function (error) {
                            console.error('reject', error);
                        });
                <? endif; ?>
                <? if ($params['OPEN_ON_NEW_PAGE'] == 'yes'): ?>
                    console.log(paymentData);
                    paymentPage.openWindow({
                        amount: paymentData.amount,
                        publicId: paymentData.publicId,
                        orderId: paymentData.orderId,
                        comment: paymentData.comment,
                        ...(paymentData.style ? {style: paymentData.style} : {}),
                        <? if ($params['SELLER_METHOD'] !== 'both'): ?>
                            paymentMethod: "<?= $params['SELLER_METHOD'] == 'sbp' ? 'ONLY_SBP' : 'ONLY_ACQUIRING' ?>",
                        <? endif; ?>
                        <? if ($params['SELLER_FISCALIZATION'] === 'on' && false): ?>
                            receipt: paymentData.receipt,
                        <? endif; ?>
                        ...(paymentData.expirationDate ? { expirationDate: paymentData.expirationDate } : {}),
                        extra: {
                            email: paymentData.receipt.customer.email,
                            orderAccountNumber: paymentData.orderAccountNumber,
                            apiClient: "Raifpay Bitrix",
                        },
                        successUrl: paymentData.successUrl,
                        failUrl: paymentData.failUrl,
                    });
                <? endif; ?>
            }

            const today = new Date();
            today.setDate(today.getDate() + 3);

            const todayISOString = today.toISOString();
            //document.getElementById('expirationDate').value = `${todayISOString.slice(0, todayISOString.length - 1)}+03:00`;

            //document.getElementById('orderId').value = Math.floor(Math.random() * 99999).toString().substr(0, 5);

            const getPaymentData = function () {
                //const extraString = document.getElementById('extra').value; // параметры, которые придут пользователю (любые данные)
                const receiptString = document.getElementById('receipt').value; // нужен для того, чтобы зарегистрировать чек
                //const styleString = document.getElementById('style').value; // мерч может сам настроить стилизацию
                const styleString = styleForm ? JSON.stringify(styleForm) : null;
                
                const encoder = new TextEncoder()
                const decoder = new TextDecoder(document.charset)
                
                const paymentDetails = decoder.decode(encoder.encode(document.getElementById('paymentDetails').value))
                
                const result = {
                    amount: parseFloat(document.getElementById('amount').value), // цена
                    orderId: decodeURI(document.getElementById('orderId').value), // номер заказа
                    successUrl: document.getElementById('successUrl').value,
                    failUrl: document.getElementById('failUrl').value,
                    comment: paymentDetails, // описание товара
                    publicId: document.getElementById('publicId').value,
                    paymentMethod: document.getElementById('paymentMethod').value,
                    locale: "RU",
                    expirationDate: document.getElementById('expirationDate').value,
                    paymentDetails: paymentDetails,
                    orderAccountNumber: document.getElementById('orderAccountNumber').value
                };

                //result.extra = extraString ? JSON.parse(extraString) : '';

                result.receipt = receiptString ? JSON.parse(receiptString, (k, v) => typeof v === 'string' ? decoder.decode(encoder.encode(v)) : v) : '';

                result.style = styleString ? JSON.parse(styleString) : null;
                console.log('getPaymentData', result);
                return result;
            };

            document.querySelector('#openPopup').addEventListener('click', processPayment);
            processPayment();
        }
    }, 100)
</script>