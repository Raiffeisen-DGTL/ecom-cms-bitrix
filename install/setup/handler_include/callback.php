<?php

use \Bitrix\Main\Application;
use \Bitrix\Sale\PaySystem;
use \Bitrix\Sale\PaySystem\ServiceResult;
use Sale\Handlers\PaySystem\ruraiffeisen_raiffeisenpayHandler;
use \Bitrix\Main\HttpRequest;
use \Bitrix\Main\Server;
use \Bitrix\Main\Web\Json;

define("STOP_STATISTICS", true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define("DisableEventsCheck", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

global $APPLICATION;

try {
	if (CModule::IncludeModule("sale")) {
		include_once(dirname(__FILE__) . "/handler.php");

		$context = Application::getInstance()->getContext();
		$input = file_get_contents('php://input');
		try {
			$requestData = Json::decode($input);
		} catch (\Throwable $requestException) {
			http_response_code(400);
			die('Invalid request');
		}

		if (!is_array($requestData)) {
			http_response_code(400);
			die('Invalid request');
		}

		$request = new HttpRequest(new Server($_SERVER), [], $requestData, [], []);
		$item = PaySystem\Manager::searchByRequest($request);
		
		// Проверка HMAC-подписи уведомления Raiffeisen
		try {
			$signature = $_SERVER['HTTP_X_API_SIGNATURE_SHA256'] ?? '';

			if ($item !== false) {
				if (empty($signature)) {
					PaySystem\Logger::addDebugInfo('Raiffeisen Callback - Missing HMAC signature');
					http_response_code(403);
					die('Invalid signature');
				}

				$serviceTmp = new PaySystem\Service($item);
				$consumerName = $serviceTmp->getConsumerName();
				
				$sellerSecret = \Bitrix\Sale\BusinessValue::getMapping('SELLER_SECRET', $consumerName)['PROVIDER_VALUE'];
				$sellerPublicId = \Bitrix\Sale\BusinessValue::getMapping('SELLER_PUBLIC_ID', $consumerName)['PROVIDER_VALUE'];
				$testMode = \Bitrix\Sale\BusinessValue::getMapping('TEST_MODE', $consumerName)['PROVIDER_VALUE'];
				
				$host = $testMode === 'yes' ? \Raiffeisen\Ecom\Client::HOST_TEST : \Raiffeisen\Ecom\Client::HOST_PROD;
				$client = new \Raiffeisen\Ecom\Client($sellerSecret, $sellerPublicId, $host);
				
				if (!$client->checkEventSignature($signature, $requestData)) {
					PaySystem\Logger::addDebugInfo('Raiffeisen Callback - Invalid HMAC signature');
					http_response_code(403);
					die('Invalid signature');
				}
			}
		} catch (\Throwable $signatureException) {
			PaySystem\Logger::addDebugInfo(
				'Raiffeisen Callback - Signature Check Error: ' . get_class($signatureException)
			);
			http_response_code(403);
			die('Invalid signature');
		}
		
		if ($item !== false) {
			$service = new PaySystem\Service($item);
			$handler = new ruraiffeisen_raiffeisenpayHandler(ServiceResult::MONEY_COMING, $service);

			$result = $handler->processRequestCustom($service, $request);
			$data = $result->getData();
			if (
				isset($data['BACK_URL'])
				&& ruraiffeisen_raiffeisenpayHandler::isSafeBackUrl($data['BACK_URL'])
			) {
				LocalRedirect($data['BACK_URL']);
			}
		} else {
			PaySystem\Logger::addDebugInfo('Pay system not found for Raiffeisen callback');
		}
	}

	$APPLICATION->FinalActions();
	die();
}
catch (Exception  $e) {
	PaySystem\Logger::addDebugInfo('Callback failed: ' . get_class($e));
}
