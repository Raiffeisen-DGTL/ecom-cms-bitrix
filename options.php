<?
$module_id = "raiffeizenpay";

if (!$USER->CanDoOperation($module_id)) {
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

CModule::IncludeModule($module_id);

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

$arAllOptions = array(
	array("FORM_STYLE", 'Стили формы оплаты', array("textarea", 20, 100))
);
$aTabs        = array();
$aTabs[]      = array("DIV" => "edit0", "TAB" => "Настройки", "ICON" => "seo_settings", "TITLE" => "Настройки");

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if ($REQUEST_METHOD == "POST" && strlen($Update . $Apply . $RestoreDefaults) > 0 && check_bitrix_sessid()) {
	if (strlen($RestoreDefaults) > 0) {
		COption::RemoveOption($module_id);

		$z = CGroup::GetList($v1 = "id", $v2 = "asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
		while ($zr = $z->Fetch())
			$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
	} else {
		foreach ($arAllOptions as $arOption) {
			$name = $arOption[0];
			$val  = $_POST[$name];
			if ($arOption[2][0] == "checkbox" && $val != "Y")
				$val = "N";
			if ($name == 'FORM_STYLE') {
				$val = preg_replace('/(.*style: )/s', '', $val);
				$val = preg_replace('/(.*?}\s)(.*;)/s', '$1', $val);
				$val = preg_replace('/(\w+):/s', '"$1":', $val);
			}

			COption::SetOptionString($module_id, $name, $val, $arOption[1]);
		}
	}
}

$tabControl->Begin();
?>
<form method="POST"
	action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid) ?>&amp;lang=<? echo LANG ?>"
	name="seo_settings">
	<?= bitrix_sessid_post(); ?>

	<? $tabControl->BeginNextTab(); ?>

	<?
	foreach ($arAllOptions as $arOption):
		$val  = COption::GetOptionString($module_id, $arOption[0], $arOption[3]);
		$type = $arOption[2];

		?>
		<tr>
			<td valign="top" width="40%">
				<?
				if ($type[0] == "checkbox")
					echo "<label for=\"" . htmlspecialcharsbx($arOption[0]) . "\">" . $arOption[1] . "</label>";
				else
					echo $arOption[1];
				?>:
			</td>
			<td valign="top" width="60%">
				<?
				if ($type[0] == "checkbox"):
					?><input type="checkbox" name="<? echo htmlspecialcharsbx($arOption[0]) ?>"
						id="<? echo htmlspecialcharsbx($arOption[0]) ?>" value="Y" <? if ($val == "Y")
							   echo " checked"; ?> />
				<?
				elseif ($type[0] == "text"):
					?><input type="text" size="<? echo $type[1] ?>" maxlength="255" value="<? echo htmlspecialcharsbx($val) ?>"
						name="<? echo htmlspecialcharsbx($arOption[0]) ?>" />
				<?
				elseif ($type[0] == "textarea"):
					?><textarea rows="<? echo $type[1] ?>" cols="<? echo $type[2] ?>"
						name="<? echo htmlspecialcharsbx($arOption[0]) ?>"><? echo htmlspecialcharsbx($val) ?></textarea>
				<?
				endif;
				?>
				<?php if ($arOption[0] == 'FORM_STYLE'): ?>
					</br>
					<i>CSS стили для формы оплаты. Измените внешний вид формы в конструкторе и перенесите код в эту форму.
						(<a href="https://e-commerce.raiffeisen.ru/pay/configurator/#/"
							target="_blank">https://e-commerce.raiffeisen.ru/pay/configurator/#/</a>)</i>
				<?php endif ?>
			</td>
		</tr>
	<?
	endforeach; ?>

	<? $tabControl->Buttons(); ?>
	<script language="JavaScript">
		function confirmRestoreDefaults() {
			return confirm('<? echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>');
		}
	</script>
	<input type="submit" name="Update" value="<? echo GetMessage("MAIN_SAVE") ?>">
	<input type="hidden" name="Update" value="Y">
	<input type="reset" name="reset" value="<? echo GetMessage("MAIN_RESET") ?>">
	<input type="submit" name="RestoreDefaults" title="<? echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
		OnClick="return confirmRestoreDefaults();" value="<? echo GetMessage("MAIN_RESTORE_DEFAULTS") ?>">
</form>
<? $tabControl->End(); ?>