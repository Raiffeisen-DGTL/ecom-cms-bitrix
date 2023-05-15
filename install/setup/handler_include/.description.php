<?php
use Bitrix\Main\Loader,
Bitrix\Main\Localization\Loc,
Bitrix\Sale\PaySystem;

Loc::loadMessages(__FILE__);

$isAvailable = PaySystem\Manager::HANDLER_AVAILABLE_TRUE;

$licensePrefix = Loader::includeModule('bitrix24') ? \CBitrix24::getLicensePrefix() : "";
$portalZone    = Loader::includeModule('intranet') ? CIntranetUtils::getPortalZone() : "";

if (Loader::includeModule("bitrix24")) {
    if ($licensePrefix !== 'ru') {
        $isAvailable = PaySystem\Manager::HANDLER_AVAILABLE_FALSE;
    }
} elseif (Loader::includeModule('intranet') && $portalZone !== 'ru') {
    $isAvailable = PaySystem\Manager::HANDLER_AVAILABLE_FALSE;
}

$description = array(
    'MAIN' => 'Изменить стиль формы оплаты можно перейдя в Настройки -> Настройки продукта -> Настройки модулей -> ' . '<a href="/bitrix/admin/settings.php?lang=ru&mid=raiffeizenpay" target="_blank">Прием платежей Raiffeisen</a>'
);

$data = array(
    'NAME'         => Loc::getMessage('SALE_HPS_RAIF_TITLE'),
    'IS_AVAILABLE' => $isAvailable,
    'CODES'        => array(
        "SELLER_SECRET"        => array(
            "NAME"  => Loc::getMessage('SALE_HPS_RAIF_SECRET'),
            "SORT"  => 100,
            'GROUP' => 'SELLER_COMPANY',
            'INPUT' => [
                'TYPE' => 'STRING'
            ],
        ),
        "SELLER_PUBLIC_ID"     => array(
            "NAME"  => Loc::getMessage('SALE_HPS_RAIF_PUBLIC_ID'),
            "SORT"  => 200,
            'GROUP' => 'SELLER_COMPANY',
            'INPUT' => [
                'TYPE' => 'STRING'
            ],
        ),
        "SELLER_CALLBACK"      => array(
            "NAME"     => Loc::getMessage('SALE_HPS_RAIF_CALLBACK'),
            "SORT"     => 300,
            'GROUP'    => 'SELLER_COMPANY',
            'DEFAULT'  => [
                'PROVIDER_VALUE' => 'https://' . $_SERVER['SERVER_NAME'] . '/local/php_interface/include/sale_payment/raiffeizenpay/callback.php',
                'PROVIDER_KEY'   => 'VALUE',
            ],
            'DISABLED' => 'Y',
        ),
        "SELLER_VAT"           => array(
            "NAME"    => Loc::getMessage('SALE_HPS_RAIF_VAT'),
            "SORT"    => 400,
            'GROUP'   => 'SELLER_COMPANY',
            "INPUT"   => [
                "TYPE" => "STRING"
            ],
            'DEFAULT' => [
                'PROVIDER_VALUE' => '20',
                'PROVIDER_KEY'   => 'INPUT',
            ],
        ),

        "SELLER_METHOD"        => array(
            "NAME"    => Loc::getMessage('SALE_HPS_RAIF_METHOD'),
            "SORT"    => 500,
            'GROUP'   => 'SELLER_COMPANY',
            'INPUT'   => [
                'TYPE'    => 'ENUM',
                'OPTIONS' => [
                    'both' => 'Все',
                    'sbp'  => 'СБП',
                    'card' => 'Банковская карта',
                ]
            ],
            'DEFAULT' => array(
                "PROVIDER_VALUE" => "both",
                "PROVIDER_KEY"   => "INPUT"
            )
        ),
        "SELLER_FISCALIZATION" => array(
            "NAME"    => Loc::getMessage('SALE_HPS_RAIF_FISCALIZATION'),
            "SORT"    => 600,
            'GROUP'   => 'SELLER_COMPANY',
            'INPUT'   => [
                'TYPE'    => 'ENUM',
                'OPTIONS' => [
                    'on'  => 'Включить',
                    'off' => 'Выключить'
                ]
            ],
            'DEFAULT' => [
                "PROVIDER_VALUE" => "off",
                "PROVIDER_KEY"   => "INPUT"
            ]

        ),
        "OPEN_ON_NEW_PAGE"     => array(
            "NAME"    => Loc::getMessage('SALE_HPS_RAIF_OPEN_NEW_PAGE'),
            "SORT"    => 600,
            'GROUP'   => 'SELLER_COMPANY',
            'INPUT'   => [
                'TYPE'    => 'ENUM',
                'OPTIONS' => [
                    'yes' => 'Да',
                    'no'  => 'Нет'
                ]
            ],
            'DEFAULT' => [
                "PROVIDER_VALUE" => "no",
                "PROVIDER_KEY"   => "INPUT"
            ]

        ),
        "TEST_MODE"            => array(
            "NAME"    => Loc::getMessage('SALE_HPS_RAIF_TEST_MODE'),
            "SORT"    => 600,
            'GROUP'   => 'SELLER_COMPANY',
            'INPUT'   => [
                'TYPE'    => 'ENUM',
                'OPTIONS' => [
                    'yes' => 'Да',
                    'no'  => 'Нет'
                ]
            ],
            'DEFAULT' => [
                "PROVIDER_VALUE" => "yes",
                "PROVIDER_KEY"   => "INPUT"
            ]
        ),
        "BACK_URI_SUCCESS"     => array(
            "NAME"    => Loc::getMessage('SALE_HPS_RAIF_BACK_URI_SUCCESS'),
            "SORT"    => 300,
            'GROUP'   => 'SELLER_COMPANY',
            'DEFAULT' => [
                'PROVIDER_VALUE' => '/personal/orders/',
                'PROVIDER_KEY'   => 'VALUE',
            ],
            'INPUT'   => [
                'TYPE'  => 'STRING',
                'VALUE' => '',
            ],
        ),
        "BACK_URI_FAIL"        => array(
            "NAME"    => Loc::getMessage('SALE_HPS_RAIF_BACK_URI_FAIL'),
            "SORT"    => 300,
            'GROUP'   => 'SELLER_COMPANY',
            'DEFAULT' => [
                'PROVIDER_VALUE' => '/personal/orders/',
                'PROVIDER_KEY'   => 'VALUE',
            ],
            'INPUT'   => [
                'TYPE'  => 'STRING',
                'VALUE' => '',
            ],
        ),
    )
);