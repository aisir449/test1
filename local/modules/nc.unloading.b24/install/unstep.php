<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $APPLICATION;

if (!check_bitrix_sessid()) {
	return;
}

echo(CAdminMessage::ShowNote(
		Loc::getMessage("NC_UNLOADING_B24_UNSTEP_BEFORE") . " " . Loc::getMessage("NC_UNLOADING_B24_UNSTEP_AFTER")
));
?>

<form action="<?= $APPLICATION->GetCurPage() ?>">
	<input type="hidden" name="lang" value="<?= defined("LANG") ? LANG : 'ru' ?>"/>
	<input type="submit" value="<?= Loc::getMessage("NC_UNLOADING_B24_UNSTEP_SUBMIT_BACK") ?>">
</form>
