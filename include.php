<?php
use Bitrix\Main;
use Bitrix\Main\Loader;

$lib_path = 'classes';

$classes = array(
    '\Raiffeisen\Ecom\Client' => "$lib_path/Client.php",
    '\Raiffeisen\Ecom\ClientException' => "$lib_path/ClientException.php",
);

Loader::registerAutoLoadClasses(
    "ruraiffeisen.raiffeisenpay",
    $classes
);

?>