<?php

namespace NC\UnloadingB24;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\ParameterDictionary;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Loader;

class Events
{
	public function __construct($agent = false)
	{
		$this->includeModules();
	}

    public static function onBeforeLeadAdd(&$arFields)
    {
        \Bitrix\Main\Loader::includeModule("crm");
        //$action = "onBeforeLeadAdd";
        //\Bitrix\Main\Diag\Debug::dumpToFile($action, "$action","/log.txt");
        //\Bitrix\Main\Diag\Debug::dumpToFile($arFields, "arFields","/log.txt");
        $leadResult = \CCrmLead::GetListEx(
            [
                'SOURCE_ID' => 'DESC'
            ],
            [
                'TITLE' => $arFields['TITLE'],
                'NAME' => $arFields['NAME'],
                'SECOND_NAME' => $arFields['SECOND_NAME'],
                'LAST_NAME' => $arFields['LAST_NAME'],
                'COMPANY_TITLE' => $arFields['COMPANY_TITLE'],
                'STATUS_ID' => $arFields['STATUS_ID'],
                'POST' => $arFields['POST'],
                'OPPORTUNITY' => $arFields['OPPORTUNITY'],
                'CHECK_PERMISSIONS' => 'N'
            ],
            false,
            false,
            [
                'ID',
                //'TITLE',
            ]
        );

        if ($lead = $leadResult->fetch()) {
            return false;
        }
        return true;
    }

    public static function onBeforeDealAdd(&$arFields)
    {
        \Bitrix\Main\Loader::includeModule("crm");
        //$action = "onBeforeDealAdd";
        //\Bitrix\Main\Diag\Debug::dumpToFile($action, "$action","/log.txt");
        //\Bitrix\Main\Diag\Debug::dumpToFile($arFields, "arFields","/log.txt");
        $entityResult = \CCrmDeal::GetListEx(
            [
                'SOURCE_ID' => 'DESC'
            ],
            [
                'TITLE' => $arFields['TITLE'],
                'TYPE_ID' => $arFields['TYPE_ID'],
                'STAGE_ID' => $arFields['STAGE_ID'],
                'OPPORTUNITY' => $arFields['OPPORTUNITY'],
                'CHECK_PERMISSIONS' => 'N'
            ],
            false,
            false,
            [
                'ID',
                //'TITLE'
            ]
        );

        if ($entity = $entityResult->fetch()) {
            return false;
        }
        return true;
    }
    public static function onBeforeContactAdd(&$arFields)
    {
        \Bitrix\Main\Loader::includeModule("crm");
        //$action = "onBeforeContactAdd";
        //\Bitrix\Main\Diag\Debug::dumpToFile($action, "$action","/log.txt");
        //\Bitrix\Main\Diag\Debug::dumpToFile($arFields, "arFields","/log.txt");
        $contactResult = \CCrmContact::GetListEx(
            [
                'SOURCE_ID' => 'DESC'
            ],
            [
                'NAME' => $arFields['NAME'],
                'SECOND_NAME' => $arFields['SECOND_NAME'],
                'LAST_NAME' => $arFields['LAST_NAME'],
                'POST' => $arFields['POST'],
                'CHECK_PERMISSIONS' => 'N'
            ],
            false,
            false,
            [
                'ID',
                //'FULL_NAME'
            ]
        );

        if ($contact = $contactResult->fetch()) {
            return false;
        }
        return true;
    }

    public static function onBeforeCompanyAdd(&$arFields)
    {
        \Bitrix\Main\Loader::includeModule("crm");
        //$action = "onBeforeCompanyAdd";
        //\Bitrix\Main\Diag\Debug::dumpToFile($action, "$action","/log.txt");
        //\Bitrix\Main\Diag\Debug::dumpToFile($arFields, "arFields","/log.txt");
        $entityResult = \CCrmCompany::GetListEx(
            [
                'SOURCE_ID' => 'DESC'
            ],
            [
                'TITLE' => $arFields['TITLE'],
                'CHECK_PERMISSIONS' => 'N'
            ],
            false,
            false,
            [
                'ID',
                //'TITLE'
            ]
        );

        if ($entity = $entityResult->fetch()) {
            return false;
        }
        return true;
    }

	protected function includeModules()
	{
		//Loader::includeModule("main");
        Loader::includeModule("crm");
	}
}
