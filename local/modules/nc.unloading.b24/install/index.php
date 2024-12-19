<?php

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class nc_unloading_b24 extends CModule
{
	public function __construct()
	{
		if (file_exists(__DIR__ . "/version.php")) {
			$arModuleVersion = [];
            include_once(dirname(__DIR__) . "/include.php");
			include_once(__DIR__ . "/version.php");

			$this->MODULE_ID = str_replace('_', '.', get_class($this));
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
			$this->MODULE_NAME = Loc::getMessage("NC_UNLOADING_B24_NAME");
			$this->MODULE_DESCRIPTION = Loc::getMessage("NC_UNLOADING_B24_DESCRIPTION");
			$this->PARTNER_NAME = Loc::getMessage("NC_UNLOADING_B24_PARTNER_NAME");
			$this->PARTNER_URI = Loc::getMessage("NC_UNLOADING_B24_PARTNER_URI");
		}
	}

	public function doInstall()
	{
		global $APPLICATION;

		if (CheckVersion(ModuleManager::getVersion("main"), "14.00.00")) {
			ModuleManager::registerModule($this->MODULE_ID);
		} else {
			$APPLICATION->ThrowException(
				Loc::getMessage("NC_UNLOADING_B24_INSTALL_ERROR_VERSION")
			);
            return false;
		}

        CopyDirFiles(
            dirname(__DIR__) . "/api/",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/api/"
        );

        $this->InstallEvents();

		$APPLICATION->IncludeAdminFile(
			Loc::getMessage("NC_UNLOADING_B24_INSTALL_TITLE") . " \"" . Loc::getMessage("NC_UNLOADING_B24_NAME") . "\"",
			__DIR__ . "/step.php"
		);
        return true;
	}

	public function doUninstall()
	{
		global $APPLICATION;

        Option::delete($this->MODULE_ID);
		ModuleManager::unRegisterModule($this->MODULE_ID);

        DeleteDirFiles(
            dirname(__DIR__) . "/api/",
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/api/",
            []
        );

        $this->UninstallEvents();

		$APPLICATION->IncludeAdminFile(
			Loc::getMessage('NC_UNLOADING_B24_UNINSTALL_TITLE') . " \"" . Loc::getMessage('NC_UNLOADING_B24_NAME') . "\"",
			__DIR__ . '/unstep.php'
		);
        return true;
	}

    public function InstallEvents()
    {
        // регистрируем обработчик события
        RegisterModuleDependences("crm", "OnBeforeCrmLeadAdd",
            $this->MODULE_ID, "\NC\UnloadingB24\Events", "onBeforeLeadAdd");
        RegisterModuleDependences("crm", "OnBeforeCrmDealAdd",
            $this->MODULE_ID, "\NC\UnloadingB24\Events", "onBeforeDealAdd");
        RegisterModuleDependences("crm", "OnBeforeCrmContactAdd",
            $this->MODULE_ID, "\NC\UnloadingB24\Events", "onBeforeContactAdd");
        RegisterModuleDependences("crm", "OnBeforeCrmCompanyAdd",
            $this->MODULE_ID, "\NC\UnloadingB24\Events", "onBeforeCompanyAdd");

        $eventManager = EventManager::getInstance();
        $eventManager->addEventHandlerCompatible(
            'crm',
            'OnBeforeCrmLeadAdd',
             $this->MODULE_ID,
            '\NC\UnloadingB24\Events',
            'onBeforeLeadAdd'
        );
        $eventManager->addEventHandlerCompatible(
            'crm',
            'OnBeforeCrmDealAdd',
            $this->MODULE_ID,
            '\NC\UnloadingB24\Events',
            'onBeforeDealAdd'
        );
        $eventManager->addEventHandlerCompatible(
            'crm',
            'OnBeforeCrmContactAdd',
            $this->MODULE_ID,
            '\NC\UnloadingB24\Events',
            'onBeforeContactAdd'
        );
        $eventManager->addEventHandlerCompatible(
            'crm',
            'OnBeforeCrmCompanyAdd',
            $this->MODULE_ID,
            '\NC\UnloadingB24\Events',
            'onBeforeCompanyAdd'
        );

        return true;
    }

    public function UninstallEvents()
    {
        // удаление событий, аналогично установке
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            "crm",
            "OnBeforeCrmLeadAdd",
            $this->MODULE_ID,
            "\NC\UnloadingB24\Events",
            'onBeforeLeadAdd'
        );
        $eventManager->unRegisterEventHandler(
            'crm',
            'OnBeforeCrmDealAdd',
            $this->MODULE_ID,
            '\NC\UnloadingB24\Events',
            'onBeforeDealAdd'
        );
        $eventManager->unRegisterEventHandler(
            'crm',
            'OnBeforeCrmContactAdd',
            $this->MODULE_ID,
            '\NC\UnloadingB24\Events',
            'onBeforeContactAdd'
        );
        $eventManager->unRegisterEventHandler(
            'crm',
            'OnBeforeCrmCompanyAdd',
            $this->MODULE_ID,
            '\NC\UnloadingB24\Events',
            'onBeforeCompanyAdd'
        );

        UnRegisterModuleDependences("crm", "OnBeforeCrmLeadAdd",
            $this->MODULE_ID, "\NC\UnloadingB24\Events", "onBeforeLeadAdd");
        UnRegisterModuleDependences("crm", "OnBeforeCrmDealAdd",
            $this->MODULE_ID, "\NC\UnloadingB24\Events", "onBeforeDealAdd");
        UnRegisterModuleDependences("crm", "OnBeforeCrmContactAdd",
            $this->MODULE_ID, "\NC\UnloadingB24\Events", "onBeforeContactAdd");
        UnRegisterModuleDependences("crm", "OnBeforeCrmCompanyAdd",
            $this->MODULE_ID, "\NC\UnloadingB24\Events", "onBeforeCompanyAdd");

        return true;
    }
}
