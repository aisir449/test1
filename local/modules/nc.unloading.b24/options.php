<?php

/** @var Bitrix\Main\ $APPLICATION */

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$request = Application::getInstance()->getContext()->getRequest();
$module_id = $request->get("mid");
$uri = new Uri($request->getRequestUri());

Loader::includeModule($module_id);

$aTabs = [
    [
        'DIV'     => 'edit1',
        'TAB'     => Loc::getMessage('NC_UNLOADING_B24_OPTIONS_TAB_SITE'),
        'TITLE'   => Loc::getMessage('NC_UNLOADING_B24_OPTIONS_TAB_SITE'),
        'OPTIONS' => [
            [
                'module_on',                                           // имя элемента формы
                Loc::getMessage('NC_UNLOADING_B24_OPTIONS_MODULE_ON'), // поясняющий текст — «Включить выгрузку из Б24»
                'Y',                                                   // значение по умолчанию «да»
                ['checkbox'],                                          // тип элемента формы — checkbox
            ],
            [
                "portal_domain",
                Loc::getMessage("NC_UNLOADING_B24_OPTIONS_PORTAL_DOMAIN"),
                "portal1",
                ["text", 60]
            ],
        ]
    ]
];
/*
 * Создаем форму для редактирвания параметров модуля
 */
$tabControl = new CAdminTabControl(
    'tabControl',
    $aTabs
);

$tabControl->begin();?>
    <form action="<?= $APPLICATION->getCurPage(); ?>?mid=<?=$module_id; ?>&lang=<?= LANGUAGE_ID; ?>" method="post">
        <?= bitrix_sessid_post(); ?>
        <?php
        foreach ($aTabs as $aTab) { // цикл по вкладкам
            if ($aTab['OPTIONS']) {
                $tabControl->beginNextTab();
                __AdmSettingsDrawList($module_id, $aTab['OPTIONS']);
            }
        }
        $tabControl->buttons();
        ?>
        <input type="submit" name="apply"
               value="<?= Loc::GetMessage('NC_UNLOADING_B24_OPTIONS_INPUT_APPLY'); ?>" class="adm-btn-save" />
        <input type="submit" name="default"
               value="<?= Loc::GetMessage('NC_UNLOADING_B24_OPTIONS_INPUT_DEFAULT'); ?>" />
        <input type="submit" name="transfer"
               value="Запустить обмен" />
    </form>
<?php $tabControl->end();?>

<?php $message = new CAdminMessage("Пояснение");
/*
 * Обрабатываем данные после отправки формы
 */
if ($request->isPost() && check_bitrix_sessid()) {
    foreach ($aTabs as $aTab) { // цикл по вкладкам
        foreach ($aTab['OPTIONS'] as $arOption) {
            if (!is_array($arOption)) { // если это название секции
                continue;
            }
            if ($arOption['note']) { // если это примечание
                continue;
            }
            if ($request['apply']) { // сохраняем введенные настройки
                $optionValue = $request->getPost($arOption[0]);
                if ($arOption[0] == 'module_on') {
                    if ($optionValue == '') {
                        $optionValue = 'N';
                    } else {
                        $optionValue = 'Y';
                    }
                }
                Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(',', $optionValue) : $optionValue);
            } elseif ($request['default']) { // устанавливаем по умолчанию
                Option::set($module_id, $arOption[0], $arOption[2]);
            } elseif ($request['transfer']) {
                $route = new \NC\UnloadingB24\Route();
            }
        }
    }
    LocalRedirect($APPLICATION->getCurPage().'?mid='.$module_id.'&lang='.LANGUAGE_ID);
}
?>




