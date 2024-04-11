<?php
use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile(__FILE__);


Class ruraiffeisen_raiffeisenpay extends CModule
{
	var $MODULE_ID = "ruraiffeisen.raiffeisenpay";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_PATH;
	var $PAYMENT_HANDLER_PATH;

	function __construct()
	{
		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/install/index.php"));

		include($path . "/install/version.php");

		$this->MODULE_PATH          = $path;
		$this->MODULE_NAME          = Loc::getMessage("RAIFFEISEN_PAYMENT_MODULE_NAME");
		$this->MODULE_DESCRIPTION   = Loc::getMessage("RAIFFEISEN_PAYMENT_MODULE_DESCRIPTION");
		$this->PARTNER_NAME         = Loc::getMessage("RAIFFEISEN_PAYMENT_PARTNER_NAME");
		$this->PARTNER_URI          = Loc::getMessage("RAIFFEISEN_PAYMENT_PARTNER_URI");

		$this->MODULE_VERSION       = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE  = $arModuleVersion["VERSION_DATE"];

		$ps_dir_path         = "/local/php_interface/include/sale_payment/";
		$this->PAYMENT_HANDLER_PATH = $_SERVER["DOCUMENT_ROOT"] . $ps_dir_path . str_replace(".", "_", $this->MODULE_ID) . "/";
	}

	function InstallFiles($arParams = array())
	{
		CopyDirFiles($this->MODULE_PATH . "/install/setup/handler_include", $this->PAYMENT_HANDLER_PATH, true, true);
		CopyDirFiles($this->MODULE_PATH . "/install/setup/images/logo", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/sale/sale_payments/");
	}

	function UnInstallFiles()
	{
		// $ps_dir_path = "/local/php_interface/include/sale_payment/";
		// DeleteDirFilesEx($ps_dir_path . str_replace(".", "_", $this->MODULE_ID));
	}

	function DoInstall()
	{
		$this->InstallFiles();
		RegisterModule($this->MODULE_ID);

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('sale', 'OnSalePaySystemUpdate', 'ruraiffeisen.raiffeisenpay', 'Sale\Handlers\PaySystem\ruraiffeisen_raiffeisenpayHandler', 'OnBusinessValueSetMapping');
		$eventManager->registerEventHandler('sale', 'OnBusinessValueSetMapping', 'ruraiffeisen.raiffeisenpay', 'Sale\Handlers\PaySystem\ruraiffeisen_raiffeisenpayHandler', 'OnBusinessValueSetMapping');
		// COption::SetOptionInt($this->MODULE_ID, "delete", false);
	}

	function DoUninstall()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('sale', 'OnSalePaySystemUpdate', 'ruraiffeisen.raiffeisenpay', 'Sale\Handlers\PaySystem\ruraiffeisen_raiffeisenpayHandler', 'OnBusinessValueSetMapping');
		$eventManager->unRegisterEventHandler('sale', 'OnBusinessValueSetMapping', 'ruraiffeisen.raiffeisenpay', 'Sale\Handlers\PaySystem\ruraiffeisen_raiffeisenpayHandler', 'OnBusinessValueSetMapping');

		$ps_dir_path = "/local/php_interface/include/sale_payment/";
		// COption::SetOptionInt($this->MODULE_ID, "delete", true);
		DeleteDirFilesEx($ps_dir_path . str_replace(".", "_", $this->MODULE_ID));
		DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/" . $this->MODULE_ID . "/");
		DeleteDirFilesEx($this->MODULE_ID);
		UnRegisterModule($this->MODULE_ID);
		return true;
	}
}

?>