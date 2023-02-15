<?php
$MESS["SBERBANK_PAYMENT_MODULE_TITLE"] = 'Sberbank - Payment by credit card';
$MESS["SBERBANK_PAYMENT_GROUP_GATE"] = 'Payment Gateway Connection Settings';
$MESS["SBERBANK_PAYMENT_GROUP_HANDLER"] = 'Payment Processor Parameters';
$MESS["SBERBANK_PAYMENT_GROUP_ORDER"] = 'Order Settings';
$MESS["SBERBANK_PAYMENT_GROUP_FFD"] = 'FFD Settings';
$MESS["SBERBANK_PAYMENT_GROUP_OFD"] = "Fiscalization";

$MESS["SBERBANK_PAYMENT_API_LOGIN_NAME"] = 'Login';
$MESS["SBERBANK_PAYMENT_API_LOGIN_DESCR"] = '';
$MESS["SBERBANK_PAYMENT_API_PASSWORD_NAME"] = 'Password';
$MESS["SBERBANK_PAYMENT_API_PASSWORD_DESCR"] = '';
$MESS["SBERBANK_PAYMENT_API_TEST_MODE_NAME"] = 'Test mode';
$MESS["SBERBANK_PAYMENT_API_TEST_MODE_DESCR"] = 'If checked, the plugin will work in test mode. If empty, the standard operation mode will be. ';

$MESS["SBERBANK_PAYMENT_HANDLER_AUTO_REDIRECT_NAME"] = 'Automatic redirect to the form of payment';
$MESS["SBERBANK_PAYMENT_HANDLER_AUTO_REDIRECT_DESCR"] = 'If noted, after placing the order, the buyer will be automatically redirected to the payment form page.';
$MESS["SBERBANK_PAYMENT_HANDLER_LOGGING_NAME"] = 'Logging';
$MESS["SBERBANK_PAYMENT_HANDLER_LOGGING_DESCR"] = 'If checked, the plugin will log requests to a file.';
$MESS["SBERBANK_PAYMENT_HANDLER_TWO_STAGE_NAME"] = 'Two Stage Payments';
$MESS["SBERBANK_PAYMENT_HANDLER_TWO_STAGE_DESCR"] = 'If checked, a two-step payment will be made. With an empty value, a one-step payment will be made.';
$MESS["SBERBANK_PAYMENT_HANDLER_SHIPMENT_NAME"] = 'Allow Shipment';
$MESS["SBERBANK_PAYMENT_HANDLER_SHIPMENT_DESCR"] = 'If checked, then after successful payment the order will be automatically shipped.';

$MESS["SBERBANK_PAYMENT_ORDER_NUMBER_NAME"] = 'Unique order identifier in the store';
$MESS["SBERBANK_PAYMENT_ORDER_NUMBER_DESCR"] = '';
$MESS["SBERBANK_PAYMENT_ORDER_AMOUNT_NAME"] = 'Order price';
$MESS["SBERBANK_PAYMENT_ORDER_AMOUNT_DESCR"] = '';
$MESS["SBERBANK_PAYMENT_ORDER_DESCRIPTION_NAME"] = 'Order Description';
$MESS["SBERBANK_PAYMENT_ORDER_DESCRIPTION_DESCR"] = '';


$MESS["SBERBANK_PAYMENT_FFD_VERSION_NAME"] = 'Fiscal Document Format';
$MESS["SBERBANK_PAYMENT_FFD_VERSION_DESCR"] = 'The format of the version is required to be indicated in the personal account of the bank and in the office of the fiscalization service';
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_NAME"] = 'Payment type';
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_DESCR"] = '';
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_1'] = "Full advance payment before the transfer of the subject of calculation";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_2'] = "Partial prepayment until the transfer of the subject of calculation";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_3'] = "Prepaid expense";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_4'] = "Full payment at the time of transfer of the subject of calculation";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_5'] = "Partial payment of the subject of payment at the time of its transfer with subsequent payment on credit";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_6'] = "Transfer of the subject of calculation without its payment at the time of its transfer with subsequent payment on credit";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_7'] = "Payment of the subject of calculation after its transfer with payment on credit";

$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_NAME"] = "Type of goods and services";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_DELIVERY_NAME"] = 'Type of goods and services from delivery';
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_DESCR"] = '';
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_1"]  = "Goods";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_2"]  = "Excised goods";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_3"]  = "Job";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_4"]  = "Service";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_5"]  = "Gambling bet";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_6"]  = "Win in gambling";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_7"]  = "Lottery ticket";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_8"]  = "Lottery win";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_9"]  = "Intellectual property provision";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_10"] = "Payment";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_11"] = "Agent's commission";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_12"] = "Combined";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_13"] = "Other";


$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_NAME"] = "Send cart data(including customer info)";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_DESCR"] = "If this option is enabled order receipts will be created and sent to your customer and to the revenue service.This is a paid option, contact your bank to enable it. If you use it, configure VAT settings. VAT is calculated according to the Russian legislation. VAT amounts calculated by your store may differ from the actual VAT amounts that can be applied.";


$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_0"] = "General";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_1"] = "Simplified, income";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_2"] = "Simplified, income minus expences";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_3"] = "Unified tax on imputed income";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_4"] = "Unified agricultural tax";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_5"] = "Patent taxation system";


$MESS["SBERBANK_PAYMENT_OFD_TAX_SYSTEM_NAME"] = "Taxation system";
$MESS["SBERBANK_PAYMENT_OFD_TAX_SYSTEM_DESCR"] = "";

$MESS["SBERBANK_PAYMENT_RETURN_URL_NAME"] = "Page your customer will be redirected to after a successful payment";
$MESS["SBERBANK_PAYMENT_RETURN_URL_DESCR"] = "Not required. The address must be indicated in full, including the protocol used.";
$MESS["SBERBANK_PAYMENT_FAIL_URL_NAME"] = "Page your customer will be redirected to after an unsuccessful payment";
$MESS["SBERBANK_PAYMENT_FAIL_URL_DESCR"] = "Not required. The address must be indicated in full, including the protocol used.";