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

global $APPLICATION;

try {
	if (CModule::IncludeModule("sale")) {
		$context = Application::getInstance()->getContext();
		$input = file_get_contents('php://input');
		Diag\Debug::dumpToFile($input, "input", '/raiffeisenpay_logs.log');
		$request = new HttpRequest(new Server($_SERVER), [], Json::decode(file_get_contents('php://input')), [], []);
		$item    = PaySystem\Manager::searchByRequest($request);

		Diag\Debug::dumpToFile($request, "request", '/raiffeisenpay_logs.log');
		Diag\Debug::dumpToFile($item, "item", '/raiffeisenpay_logs.log');
		if ($item !== false) {
			$service = new PaySystem\Service($item);
			$handler = new ruraiffeisen_raiffeisenpayHandler(ServiceResult::MONEY_COMING, $service);
			Diag\Debug::dumpToFile($service, "service", '/raiffeisenpay_logs.log');
			Diag\Debug::dumpToFile($handler, "handler", '/raiffeisenpay_logs.log');

			$result = $handler->processRequestCustom($service, $request);
			Diag\Debug::dumpToFile($result, "result", '/raiffeisenpay_logs.log');
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
	Diag\Debug::dumpToFile($e, "", '/raiffeisenpay_logs.log');
}
