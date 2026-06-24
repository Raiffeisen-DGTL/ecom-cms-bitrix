<?php

use \Bitrix\Main\Application;
use \Bitrix\Sale\PaySystem;
use \Bitrix\Sale\PaySystem\ServiceResult;
use Sale\Handlers\PaySystem\ruraiffeisen_raiffeisenpayHandler;
use \Bitrix\Main\HttpRequest;
use \Bitrix\Main\Server;
use \Bitrix\Main\Web\Json;

use \Bitrix\Main\Diag;

define("STOP_STATISTICS", true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define("DisableEventsCheck", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
include_once(dirname(__FILE__) . "/handler.php");

global $APPLICATION;

try {
	if (CModule::IncludeModule("sale")) {
		$context = Application::getInstance()->getContext();
		$input = file_get_contents('php://input');
		$request = new HttpRequest(new Server($_SERVER), [], Json::decode(file_get_contents('php://input')), [], []);
		
		// Проверка HMAC-подписи уведомления Raiffeisen
		try {
			$signature = $_SERVER['HTTP_X_API_SIGNATURE_SHA256'] ?? '';
			
			if (!empty($signature)) {
				$itemTmp = PaySystem\Manager::searchByRequest($request);
				if ($itemTmp !== false) {
					$serviceTmp = new PaySystem\Service($itemTmp);
					$consumerName = $serviceTmp->getConsumerName();
					
					$sellerSecret = \Bitrix\Sale\BusinessValue::getMapping('SELLER_SECRET', $consumerName)['PROVIDER_VALUE'];
					$sellerPublicId = \Bitrix\Sale\BusinessValue::getMapping('SELLER_PUBLIC_ID', $consumerName)['PROVIDER_VALUE'];
					$testMode = \Bitrix\Sale\BusinessValue::getMapping('TEST_MODE', $consumerName)['PROVIDER_VALUE'];
					
					$host = $testMode === 'yes' ? \Raiffeisen\Ecom\Client::HOST_TEST : \Raiffeisen\Ecom\Client::HOST_PROD;
					$client = new \Raiffeisen\Ecom\Client($sellerSecret, $sellerPublicId, $host);
					
					if (!$client->checkEventSignature($signature, $input)) {
						Diag\Debug::dumpToFile([
							'signature' => $signature,
							'body' => $input,
							'reason' => 'Invalid HMAC signature'
						], 'Raiffeisen Callback - Invalid Signature', '/raiffeisenpay_logs.log');
					}
				}
			}
		} catch (Exception $signatureException) {
			Diag\Debug::dumpToFile($signatureException, 'Raiffeisen Callback - Signature Check Error', '/raiffeisenpay_logs.log');
		}
		
		$item    = PaySystem\Manager::searchByRequest($request);
		if ($item !== false) {
			$service = new PaySystem\Service($item);
			$handler = new ruraiffeisen_raiffeisenpayHandler(ServiceResult::MONEY_COMING, $service);

			$result = $handler->processRequestCustom($service, $request);
			$data   = $result->getData()['BACK_URL'];
			if (!empty($data) && isset($data['BACK_URL'])) {
				LocalRedirect($data['BACK_URL']);
			}
		} else {
			$debugInfo = http_build_query($request->toArray(), "", "\n");
			if (empty($debugInfo)) {
				$debugInfo = file_get_contents('php://input');
			}
			PaySystem\Logger::addDebugInfo('Pay system not found. Request: ' . ($debugInfo ? $debugInfo : "empty"));
		}
	}

	$APPLICATION->FinalActions();
	die();
}
catch (Exception  $e) {
	PaySystem\Logger::addDebugInfo('Callback failed: ' . $e);
}
