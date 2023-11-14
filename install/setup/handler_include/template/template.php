<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PriceMaths;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Page\Asset;

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

$order     = Sale\Order::load($_GET["ORDER_ID"]);
$userID    = $order->getUserId();
$userName  = \Bitrix\Main\Engine\CurrentUser::get()->getFullName();
$userEmail = \Bitrix\Main\Engine\CurrentUser::get()->getEmail();
$paySum    = $order->getPrice();
$orderID   = $order->getId();

$receipt = [];

$receipt["receiptNumber"]     = $orderID;
$receipt["customer"]["email"] = $userEmail;
//$receipt["customer"]["name"] = $userName;

if ($params['SELLER_FISCALIZATION'] === 'on') {
    $basket = $order->getBasket();

    $basketItems = $basket->getBasketItems();
    $bItems      = [];

    $vatType = $params["SELLER_VAT"] === "NONE" ? "NONE" : ("VAT" . $params["SELLER_VAT"]);

    foreach ($basketItems as $item) {
        $bItems[] = [
            "name"            => $item->getField('NAME'),
            "price"           => $item->getField('PRICE'),
            "quantity"        => (int) $item->getField('QUANTITY'),
            "amount"          => $item->getFinalPrice(),
            "paymentObject"   => "COMMODITY",
            "paymentMode"     => "FULL_PAYMENT",
            "measurementUnit" => "OTHER",
            //"nomenclatureCode" => $item->getField('PRODUCT_XML_ID'),
            "vatType"  => $vatType,
        ];
    }

    if ($order->getDeliveryPrice() > 0) {
        $bItems[] = [
            "name"     => Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_SBERBANK_DELIVERY'),
            "price"    => $order->getDeliveryPrice(),
            "quantity" => 1,
            "amount"   => $order->getDeliveryPrice(),
            "vatType"  => $vatType,
        ];
    }

    $receipt["items"] = $bItems;
}

/*
$receipt["payments"][] = [
"type" => "E_PAYMENT",
"amount" => $paySum,
];
*/

$expTime = (new DateTime())->add(DateInterval::createFromDateString('15 minutes'));

?>

<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die(); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1,user-scalable=no,
              shrink-to-fit=no" />
    <meta http-equiv="Content-Type" content="text/html; charset=<?= LANG_CHARSET ?>">
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
    <link rel="stylesheet" href="https://pay.raif.ru/pay/sdk/v2/payment.min.css">
    <script src="https://pay.raif.ru/pay/sdk/v2/payment.styled.min.js"></script>
</head>

<body class="lol q">

    <form method="POST" name="redirectToAcsForm" id="form" target="_blank" style="display: none">
        <input name="amount" id="amount" value="<?= $paySum ?>" type="hidden" />
        <input name="orderId" id="orderId" value="<?= $orderID ?>" id="order" type="hidden" />
        <input name="successUrl" id="successUrl" value="<?= ($_SERVER["SERVER_NAME"] . $params['BACK_URI_SUCCESS']) ?>"
            type="hidden" />
        <input name="failUrl" id="failUrl" value="<?= ($_SERVER["SERVER_NAME"] . $params['BACK_URI_FAIL']) ?>"
            type="hidden" />
        <input name="publicId" id="publicId" value="<?= $params["SELLER_PUBLIC_ID"] ?>" type="hidden" />
        <input name="paymentMethod" id="paymentMethod" value="<?= $params["SELLER_METHOD"] ?>" type="hidden" />
        <? if ($formStyles): ?>
            <script>
                const styleForm = <?= $formStyles ?>; 
            </script>
        <? else: ?>
            <script>
                const styleForm = {}; 
            </script>
        <? endif ?>
        <input name="expirationDate" id="expirationDate"
            value="<?= $expTime->format('Y-m-d') . "T" . $expTime->format('H:i:sP') ?>" style="width: 200px;" />
        <input id="paymentDetails" name="paymentDetails" value="<?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_SBERBANK_ORDER_PAYMENT') ?> <?= $orderID ?>" type="hidden" />
        <textarea type="text" id="receipt" name="receipt" rows="20"
            style="width: 300px"><?= \Bitrix\Main\Web\Json::encode($receipt) ?></textarea>
    </form>

    <button class="button-pay" id="openPopup"><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_SBERBANK_OPEN_PAYMENT_POPUP') ?></button>

    <div id="portal"></div>

    <script>
        window.onload = function () {
            const processPayment = () => {
                const paymentData = getPaymentData();

                const paymentPage = new PaymentPageSdk(getPaymentData().publicId, {
                    targetElem: null, url: "<?= $params['TEST_MODE'] === 'yes' ? 'https://pay-test.raif.ru/pay' : 'https://pay.raif.ru/pay' ?>"
                });

                <? if ($params['OPEN_ON_NEW_PAGE'] == 'no'): ?>
                    paymentPage.openPopup({
                        amount: parseInt(paymentData.amount),
                        publicId: paymentData.publicId,
                        orderId: paymentData.orderId,
                        comment: paymentData.comment,
                        style: paymentData.style,
                        <? if ($params['SELLER_METHOD'] !== 'both'): ?>
                            paymentMethod: "<?= $params['SELLER_METHOD'] == 'sbp' ? 'ONLY_SBP' : 'ONLY_ACQUIRING' ?>",
                        <? endif; ?>
                        <? if ($params['SELLER_FISCALIZATION'] === 'on'): ?>
                            receipt: paymentData.receipt,
                        <? endif; ?>
                        expirationDate: paymentData.expirationDate,
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
                    paymentPage.openWindow({
                        amount: paymentData.amount,
                        orderId: paymentData.orderId,
                        style: paymentData.style,
                        <? if ($params['SELLER_METHOD'] !== 'both'): ?>
                            paymentMethod: "<?= $params['SELLER_METHOD'] == 'sbp' ? 'ONLY_SBP' : 'ONLY_ACQUIRING' ?>",
                        <? endif; ?>
                        successUrl: paymentData.successUrl,
                        failUrl: paymentData.failUrl,
                        extra: {
                            email: paymentData.receipt.email,
                        },
                        <? if ($params['SELLER_FISCALIZATION'] === 'on'): ?>
                            receipt: paymentData.receipt,
                        <? endif; ?>
                        comment: paymentData.comment
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
                const styleString = JSON.stringify(styleForm);
                
                const encoder = new TextEncoder()
                const decoder = new TextDecoder(document.charset)
                
                const paymentDetails = decoder.decode(encoder.encode(document.getElementById('paymentDetails').value))
                
                const result = {
                    amount: parseInt(document.getElementById('amount').value), // цена
                    orderId: document.getElementById('orderId').value, // номер заказа
                    successUrl: document.getElementById('successUrl').value,
                    failUrl: document.getElementById('failUrl').value,
                    comment: paymentDetails, // описание товара
                    publicId: document.getElementById('publicId').value,
                    paymentMethod: document.getElementById('paymentMethod').value,
                    locale: "RU",
                    expirationDate: document.getElementById('expirationDate').value,
                    paymentDetails: paymentDetails
                };

                //result.extra = extraString ? JSON.parse(extraString) : '';

                result.receipt = receiptString ? JSON.parse(receiptString, (k, v) => typeof v === 'string' ? decoder.decode(encoder.encode(v)) : v) : '';

                result.style = styleString ? JSON.parse(styleString) : '';
                console.log('getPaymentData', result);
                return result;
            };

            document.addEventListener('DOMContentLoaded', processPayment);
            document.querySelector('#openPopup').addEventListener('click', processPayment);
        }();
    </script>
</body>

</html>